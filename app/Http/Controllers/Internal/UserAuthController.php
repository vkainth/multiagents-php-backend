<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeUserMail;
use App\Models\Agent;
use App\Models\AgentSettings;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\LeadPipeline;
use Illuminate\Support\Str;

/**
 * Token-based user auth for the Next.js pixilink-web microsite.
 *
 * All endpoints live at /api-internal/auth/*.
 * Protected endpoints require: Authorization: Bearer {plain_token}
 * On login/register, a plain token is returned; the SHA-256 hash is stored in user_tokens.
 *
 * Users are scoped to the agent site they registered on via agent_id.
 * The same email can register independently on different agent sites.
 * Agent resolution is mandatory for all email-based auth endpoints — if the
 * current agent cannot be determined, a 422 is returned rather than falling
 * back to unscoped global queries (which would violate per-site isolation).
 */
class UserAuthController extends Controller
{
    /* ─────────────── helpers ─────────────── */

    private function userFromToken(Request $request): ?User
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $plain  = substr($header, 7);
        $hashed = hash('sha256', $plain);

        $row = UserToken::where('token', $hashed)->first();
        if (! $row) {
            return null;
        }
        if ($row->isExpired()) {
            $row->delete();
            return null;
        }
        $user = User::find($row->user_id);
        if (! $user) {
            return null;
        }

        // Cross-site token check: if the request identifies an agent site,
        // ensure the token owner belongs to that site. When no agent context
        // is present (e.g. internal phone/profile endpoints that omit agent_slug)
        // the Bearer token alone is sufficient proof of identity.
        $agent = $this->resolveAgentFromRequest($request);
        if ($agent && (int) $user->agent_id !== (int) $agent->id) {
            return null;
        }

        return $user;
    }

    private function createToken(User $user, bool $remember = false): string
    {
        $plain  = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $plain);
        UserToken::create([
            'user_id'    => $user->id,
            'token'      => $hashed,
            'expires_at' => $remember ? now()->addYear() : now()->addDays(30),
        ]);
        return $plain;
    }

    private function formatUser(User $user): array
    {
        return [
            'id'               => $user->id,
            'email'            => $user->email,
            'first_name'       => $user->first_name,
            'last_name'        => $user->last_name,
            'name'             => $user->fullName(),
            'initials'         => $user->initials(),
            'phone'            => $user->phone,
            'phone_country_code' => $user->phone_country_code ?? '+1',
            'email_verified'   => (bool) $user->email_verified_at,
            'profile_complete' => $user->hasCompleteProfile(),
            'phone_verified'   => $user->hasVerifiedPhone(),
            'terms_accepted'   => $user->hasAcceptedTerms(),
            'next_step'        => $this->nextStep($user),
        ];
    }

    private function nextStep(User $user): string
    {
        if (! $user->email_verified_at) {
            return 'verify_email';
        }
        if (! $user->first_name || ! $user->last_name) {
            return 'complete_profile';
        }
        if (! $user->phone_verified_at && ! $user->google_id) {
            return 'verify_phone';
        }
        if (! $user->hasAcceptedTerms()) {
            return 'accept_terms';
        }
        return 'done';
    }

    /**
     * Look up agent branding from slug or app_url for email templates.
     * Falls back silently — emails are sent even if agent is not found.
     */
    private function agentBranding(?string $slug): array
    {
        $fallbackFrom = config('mail.from.address');
        if (! $slug) {
            return ['name' => '', 'brokerage' => '', 'from_name' => '', 'accent' => '#c9a96e', 'from_email' => $fallbackFrom];
        }
        try {
            $agent     = Agent::where('slug', $slug)->with('settings')->first();
            $name      = $agent?->name ?? '';
            $brokerage = $agent?->brokerage ?? '';
            $fromName  = $brokerage ? $name . ' - ' . $brokerage : $name;
            return [
                'name'       => $name,
                'brokerage'  => $brokerage,
                'from_name'  => $fromName,
                'accent'     => $agent?->theme_color ?? '#c9a96e',
                'from_email' => $fallbackFrom,
            ];
        } catch (\Exception) {
            return ['name' => '', 'brokerage' => '', 'from_name' => '', 'accent' => '#c9a96e', 'from_email' => $fallbackFrom];
        }
    }

    /**
     * Resolve the current agent from agent_slug in the request body (preferred)
     * or from the Host header via agent_settings.custom_domain (fallback).
     * Returns null if no agent can be resolved.
     */
    private function resolveAgentFromRequest(Request $request): ?Agent
    {
        $slug = trim($request->input('agent_slug', ''));
        if ($slug) {
            return Agent::where('slug', $slug)->where('status', 'active')->with('settings')->first();
        }

        $host = strtolower(trim($request->getHost() ?? ''));
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        if ($host) {
            $settings = DB::table('agent_settings')->where('custom_domain', $host)->first();
            if ($settings) {
                return Agent::where('id', $settings->agent_id)
                    ->where('status', 'active')
                    ->with('settings')
                    ->first();
            }
        }

        return null;
    }

    /**
     * Resolve the current agent, returning a 422 JSON error if unresolvable.
     * Use this in all email-based auth endpoints to enforce per-site isolation.
     * If agent_slug is always sent by the frontend (as documented in replit.md),
     * this should never fail in production.
     */
    private function requireAgent(Request $request): Agent|JsonResponse
    {
        $agent = $this->resolveAgentFromRequest($request);
        if (! $agent) {
            return response()->json([
                'error' => 'Unable to identify agent site. Please include agent_slug in your request.',
            ], 422);
        }
        return $agent;
    }

    private function requireToken(Request $request): User|JsonResponse
    {
        $user = $this->userFromToken($request);
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $user;
    }

    /* ─────────────── register ─────────────── */

    public function register(Request $request): JsonResponse
    {
        // Registration rate-limit: 5 per 10 minutes per IP (CF-Connecting-IP → $request->ip()).
        $regIp  = $request->header('CF-Connecting-IP') ?: ($request->ip() ?? '0.0.0.0');
        $regKey = 'register:' . sha1($regIp);
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($regKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($regKey);
            return response()->json([
                'message' => 'Too many registration attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($regKey, 600); // 10-minute window

        $request->validate([
            'email'              => 'required|email',
            'password'           => 'required|min:8|confirmed',
            'terms'              => 'required|accepted',
            'app_url'            => 'nullable|string|max:300',
            'agent_slug'         => 'nullable|string|max:80',
            'first_name'         => 'nullable|string|max:50',
            'last_name'          => 'nullable|string|max:50',
            'phone'              => 'required|string|min:7|max:30',
            'phone_country_code' => 'nullable|string|max:10',
        ], [
            'password.min'       => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'terms.required'     => 'You must agree to the terms to continue.',
            'terms.accepted'     => 'You must agree to the terms to continue.',
            'phone.required'     => 'A phone number is required to create an account.',
        ]);

        // Server-side digit guard, matching registerPasswordless(): the max:30
        // rule counts characters, so formatting could satisfy it with too few
        // actual digits.
        $rawPhone   = $request->input('phone', '');
        $regPhone   = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($regPhone) < 7) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['phone' => ['Please enter a valid phone number (at least 7 digits).']],
            ], 422);
        }
        $regCountryCode = $request->input('phone_country_code') ?: '+1';

        // Resolve agent — mandatory for per-site isolation.
        $agent = $this->requireAgent($request);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        // Check email uniqueness scoped to this agent site.
        $emailTaken = User::where('email', $request->email)
            ->where('agent_id', $agent->id)
            ->exists();
        if ($emailTaken) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['email' => ['An account with this email already exists.']],
            ], 422);
        }

        $verificationToken = Str::random(64);

        $firstName = $request->first_name;
        $lastName  = $request->last_name;
        $fullName  = trim("{$firstName} {$lastName}") ?: $request->email;

        $user = User::create([
            'agent_id'                  => $agent->id,
            'name'                      => $fullName,
            'email'                     => $request->email,
            'password'                  => $request->password,
            'first_name'                => $firstName ?: null,
            'last_name'                 => $lastName  ?: null,
            'phone'                     => $regPhone ?: null,
            'phone_country_code'        => $regPhone ? $regCountryCode : null,
            'terms_agreed_at'           => now(),
            'email_verification_token'  => $verificationToken,
        ]);

        $appUrl    = rtrim($request->app_url ?? config('app.url'), '/');
        $agentSlugParam = $request->agent_slug ? '&agent_slug=' . urlencode($request->agent_slug) : '';
        $verifyUrl = $appUrl . '/verify-email?token=' . $verificationToken . $agentSlugParam;

        $branding = $this->agentBranding($request->agent_slug);

        try {
            $vMail = new VerifyEmailMail(
                verifyUrl:       $verifyUrl,
                agentName:       $branding['name'],
                agentBrokerage:  $branding['brokerage'],
                accentColor:     $branding['accent'],
            );
            $vMail->from($branding['from_email'], $branding['from_name']);
            Mail::to($user->email)->send($vMail);
        } catch (\Exception $e) {
            logger()->warning('VerifyEmailMail failed: ' . $e->getMessage());
        }

        // Lead pipeline: save to agent_leads, push FUB/GHL, notify agent.
        try {
            $hasUidCol = \Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'user_id');
            $leadRow = [
                'agent_id'   => $agent->id,
                'form_type'  => 'registration',
                'name'       => $fullName,
                'first_name' => $firstName ?: null,
                'last_name'  => $lastName  ?: null,
                'email'      => $user->email,
                'source_url' => $request->headers->get('referer'),
                'ip_hash'    => hash('sha256', $request->ip() ?? ''),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasUidCol) {
                $leadRow['user_id'] = $user->id;
            }
            // Without this the lead row lands with a blank phone even though the
            // user supplied one -- the agent's whole reason for wanting the field.
            if (\Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'phone')) {
                $leadRow['phone'] = $regPhone;
                if (\Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'phone_country_code')) {
                    $leadRow['phone_country_code'] = $regCountryCode;
                }
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'message')) {
                $leadRow['message'] = 'Phone: ' . $regCountryCode . ' ' . $regPhone;
            }
            \Illuminate\Support\Facades\DB::table('agent_leads')->insert($leadRow);
            $regData = [
                'name'       => $fullName,
                'first_name' => $firstName ?: null,
                'last_name'  => $lastName  ?: null,
                'email'      => $user->email,
                'form_type'  => 'registration',
                'source_url' => $request->headers->get('referer'),
            ];
            LeadPipeline::pushToFollowUpBoss($agent, $regData);
            LeadPipeline::pushToGoHighLevel($agent, $regData);

            $regNotifyEmail = $agent->settings?->notification_email ?: $agent->email ?? null;
            if ($regNotifyEmail) {
                try {
                    $regDomain = $agent->settings?->custom_domain ?? ($agent->slug . '.pixilink.com');
                    Mail::raw(
                        "New registration on {$regDomain}\n"
                        . str_repeat('-', 44) . "\n"
                        . "Name:   " . ($fullName ?: '—') . "\n"
                        . "Email:  {$user->email}\n"
                        . "Source: " . ($request->headers->get('referer') ?? '—') . "\n"
                        . str_repeat('-', 44) . "\n"
                        . "View leads: https://website.pixilink.com/admin/agents/{$agent->id}/leads\n",
                        fn ($m) => $m->to($regNotifyEmail)
                            ->subject('[New Registration] ' . ($fullName ?: $user->email))
                    );
                } catch (\Throwable $notifErr) {
                    logger()->warning('register: agent notify mail failed: ' . $notifErr->getMessage());
                }
            }
        } catch (\Throwable $regErr) {
            logger()->warning('register: lead/CRM push failed: ' . $regErr->getMessage());
        }

        $token = $this->createToken($user);

        return response()->json([
            'token'     => $token,
            'user'      => $this->formatUser($user),
            'next_step' => 'verify_email',
        ], 201);
    }

    /* ─────────────── login ─────────────── */

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'      => 'required|email',
            'password'   => 'required',
            'remember'   => 'nullable|boolean',
            'agent_slug' => 'nullable|string|max:80',
        ]);

        // Agent resolution is mandatory — prevents global fallback.
        $agent = $this->requireAgent($request);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        $user = User::where('email', $request->email)
            ->where('agent_id', $agent->id)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'These credentials do not match our records.'], 401);
        }

        $token = $this->createToken($user, (bool) $request->remember);

        return response()->json([
            'token'     => $token,
            'user'      => $this->formatUser($user),
            'next_step' => $this->nextStep($user),
        ]);
    }

    /* ─────────────── logout ─────────────── */

    public function logout(Request $request): JsonResponse
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            $hashed = hash('sha256', substr($header, 7));
            UserToken::where('token', $hashed)->delete();
        }
        return response()->json(['ok' => true]);
    }

    /* ─────────────── me ─────────────── */

    public function me(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        return response()->json(['user' => $this->formatUser($user)]);
    }

    /* ─────────────── email verification ─────────────── */

    public function emailResend(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        if ($user->email_verified_at) {
            return response()->json(['ok' => true, 'already_verified' => true]);
        }

        $request->validate(['app_url' => 'nullable|string|max:300', 'agent_slug' => 'nullable|string|max:80']);

        $token = $user->email_verification_token ?: Str::random(64);
        $user->update(['email_verification_token' => $token]);

        $appUrl    = rtrim($request->app_url ?? config('app.url'), '/');
        $agentSlugParamResend = $request->agent_slug ? '&agent_slug=' . urlencode($request->agent_slug) : '';
        $verifyUrl = $appUrl . '/verify-email?token=' . $token . $agentSlugParamResend;
        $branding  = $this->agentBranding($request->agent_slug);

        try {
            $vMailR = new VerifyEmailMail(
                verifyUrl:      $verifyUrl,
                agentName:      $branding['name'],
                agentBrokerage: $branding['brokerage'],
                accentColor:    $branding['accent'],
            );
            $vMailR->from($branding['from_email'], $branding['from_name']);
            Mail::to($user->email)->send($vMailR);
        } catch (\Exception $e) {
            logger()->warning('VerifyEmailMail resend failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    public function checkVerified(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        return response()->json([
            'verified'   => (bool) $user->email_verified_at,
            'next_step'  => $this->nextStep($user),
        ]);
    }

    /** Called when user clicks the link in their email. */
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query('token', '');
        if (! $token) {
            return response()->json(['error' => 'Missing token.'], 422);
        }

        $user = User::where('email_verification_token', $token)->first();
        if (! $user) {
            return response()->json(['error' => 'Invalid or expired verification link.'], 422);
        }

        $user->update([
            'email_verified_at'        => now(),
            'email_verification_token' => null,
        ]);

        // Send welcome email after successful verification
        $agentSlug = $request->query('agent_slug', '');
        $branding  = $this->agentBranding($agentSlug ?: null);
        try {
            Mail::to($user->email)->send(new WelcomeUserMail(
                firstName:      $user->first_name ?? '',
                agentName:      $branding['name'],
                agentBrokerage: $branding['brokerage'],
                accentColor:    $branding['accent'],
            ));
        } catch (\Exception $e) {
            logger()->warning('WelcomeUserMail failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true, 'next_step' => $this->nextStep($user->fresh())]);
    }

    /* ─────────────── complete profile ─────────────── */

    public function completeProfile(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $request->validate([
            'first_name'         => 'required|string|max:50',
            'last_name'          => 'required|string|max:50',
            'phone'              => 'required|string|max:30',
            'phone_country_code' => 'nullable|string|max:10',
        ]);

        $user->update([
            'first_name'         => $request->first_name,
            'last_name'          => $request->last_name,
            'name'               => trim("{$request->first_name} {$request->last_name}"),
            'phone'              => preg_replace('/[^0-9]/', '', $request->phone),
            'phone_country_code' => $request->phone_country_code ?? '+1',
            'phone_verified_at'  => null,
        ]);

        return response()->json([
            'user'      => $this->formatUser($user->fresh()),
            'next_step' => $this->nextStep($user->fresh()),
        ]);
    }

    /* ─────────────── phone save (phone entry step) ─────────────── */

    /**
     * Save the user's phone number without requiring name fields.
     * Used by the new /verify-phone page to store the phone before sending OTP.
     */
    public function phoneSave(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $request->validate([
            'phone'              => 'required|string|max:30',
            'phone_country_code' => 'nullable|string|max:10',
        ]);

        $user->update([
            'phone'             => preg_replace('/[^0-9]/', '', $request->phone),
            'phone_country_code' => $request->phone_country_code ?? '+1',
            'phone_verified_at' => null, // reset verification on phone change
        ]);

        return response()->json([
            'ok'   => true,
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    /* ─────────────── phone OTP ─────────────── */

    public function phoneSend(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        if (! $user->phone) {
            return response()->json(['error' => 'No phone number on file. Please enter your phone number first.'], 422);
        }

        $e164 = ($user->phone_country_code ?? '+1') . $user->phone;

        // Per-user send cap. The route throttle is 10/min PER IP and the 60s resend
        // countdown is client-side only, so a held session could bill Twilio for an
        // unbounded number of SMS. The verify side already has a lockout; the send side
        // had none. Keyed on the user so one abusive session cannot spend on behalf of
        // everyone behind a shared NAT, and on the number so changing phones does not
        // reset the budget.
        $sendKey = 'otp_sends:' . $user->id . ':' . $e164;
        $sends   = (int) cache()->get($sendKey, 0);
        if ($sends >= self::OTP_MAX_SENDS) {
            return response()->json([
                'error'  => 'Too many codes requested. Please try again later or contact support.',
                'locked' => true,
            ], 429);
        }

        try {
            $sid    = config('services.twilio.sid');
            $token  = config('services.twilio.token');
            $vsid   = config('services.twilio.verify_sid');

            if (! $sid || ! $token || ! $vsid) {
                return response()->json(['error' => 'Phone verification is not configured.'], 503);
            }

            $client = new \Twilio\Rest\Client($sid, $token);
            $client->verify->v2->services($vsid)->verifications->create($e164, 'sms');

            // Count only sends Twilio actually accepted - a failed send costs nothing,
            // so it should not consume the user's budget.
            cache()->put($sendKey, $sends + 1, now()->addMinutes(self::OTP_SEND_WINDOW_MINUTES));

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            logger()->warning('Twilio OTP send failed: ' . $e->getMessage());
            // Twilio would not accept the number at all - a mistyped or unreachable
            // phone, distinct from a wrong code.
            $this->logFunnelEvent('phone_invalid');
            return response()->json(['error' => 'Failed to send verification code. Please try again.'], 503);
        }
    }

    /**
     * Record a funnel event. Deliberately fire-and-forget: this is reporting, and must
     * never turn a working (or already-failing) auth response into a 500.
     */
    private function logFunnelEvent(string $event, ?string $agentSlug = null): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('sold_gate_events')->insert([
                'event_type' => $event,
                'agent_slug' => $agentSlug,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // swallowed on purpose
        }
    }

    private const OTP_MAX_SENDS = 5;
    private const OTP_SEND_WINDOW_MINUTES = 60;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_LOCKOUT_MINUTES = 10;

    public function phoneVerify(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $request->validate(['code' => 'required|string|min:4|max:8']);

        // --- Lockout check ---
        $lockKey    = "otp_lock:{$user->id}";
        $attemptsKey = "otp_attempts:{$user->id}";

        if (cache()->has($lockKey)) {
            $lockedUntil = cache($lockKey);
            $secsLeft    = max(0, (int) ceil($lockedUntil - now()->timestamp));
            return response()->json([
                'error'             => 'Too many failed attempts. Please wait before trying again.',
                'locked'            => true,
                'locked_seconds'    => $secsLeft,
                'attempts_remaining' => 0,
            ], 429);
        }

        $e164 = ($user->phone_country_code ?? '+1') . $user->phone;

        $verified = false;
        $twilioErr = null;

        try {
            $sid   = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $vsid  = config('services.twilio.verify_sid');

            if (! $sid || ! $token || ! $vsid) {
                return response()->json(['error' => 'Phone verification is not configured.'], 503);
            }

            $client = new \Twilio\Rest\Client($sid, $token);
            $result = $client->verify->v2->services($vsid)
                ->verificationChecks
                ->create(['to' => $e164, 'code' => $request->code]);

            $verified = ($result->status === 'approved');
        } catch (\Exception $e) {
            logger()->warning('Twilio OTP verify failed: ' . $e->getMessage());
            $twilioErr = $e->getMessage();
        }

        if (! $verified) {
            // Increment attempt counter (TTL = lockout window + buffer)
            $attempts = (int) cache()->get($attemptsKey, 0) + 1;
            $remaining = self::OTP_MAX_ATTEMPTS - $attempts;
            $ttlSeconds = self::OTP_LOCKOUT_MINUTES * 60 + 60;

            if ($remaining <= 0) {
                cache([$attemptsKey => $attempts], $ttlSeconds);
                $lockedUntil = now()->addMinutes(self::OTP_LOCKOUT_MINUTES)->timestamp;
                cache([$lockKey => $lockedUntil], self::OTP_LOCKOUT_MINUTES * 60);

                return response()->json([
                    'error'             => 'Too many failed attempts. Please wait 10 minutes before trying again.',
                    'locked'            => true,
                    'locked_seconds'    => self::OTP_LOCKOUT_MINUTES * 60,
                    'attempts_remaining' => 0,
                ], 429);
            }

            cache([$attemptsKey => $attempts], $ttlSeconds);

            // Wrong or expired code - the clearest signal a real person tried and the
            // number or code did not work.
            $this->logFunnelEvent('otp_failed');

            $message = $twilioErr
                ? 'Incorrect or expired code.'
                : "Incorrect code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . ' remaining.';

            return response()->json([
                'error'             => $message,
                'locked'            => false,
                'attempts_remaining' => $remaining,
            ], 422);
        }

        // Success — clear attempt tracking
        cache()->forget($attemptsKey);
        cache()->forget($lockKey);

        $user->update(['phone_verified_at' => now()]);

        // Mark the agent_lead(s) this user created as verified. Uses the Eloquent model,
        // not a query-builder update, because AgentLead::booted() dispatches
        // AgentLeadVerifiedJob on the `updated` event - a DB::table() update bypasses it
        // silently. email_verified_at is set alongside because registerPasswordless marks
        // the email verified at creation, and the hook requires both.
        try {
            if (! empty($user->email)) {
                $leads = \App\Models\AgentLead::where('email', $user->email)
                    ->whereNull('phone_verified_at')
                    ->get();
                foreach ($leads as $lead) {
                    $lead->phone_verified_at = now();
                    if (! $lead->email_verified_at && $user->email_verified_at) {
                        $lead->email_verified_at = $user->email_verified_at;
                    }
                    $lead->save();
                }
            }
        } catch (\Throwable $e) {
            // Never fail a successful verification because lead bookkeeping broke -
            // the user has verified, and that must return 200 regardless.
            \Illuminate\Support\Facades\Log::warning('lead verify-stamp failed: ' . $e->getMessage());
        }

        $fresh = $user->fresh();

        return response()->json([
            'ok'        => true,
            'user'      => $this->formatUser($fresh),
            'next_step' => $this->nextStep($fresh),
        ]);
    }

    /* ─────────────── accept terms ─────────────── */

    public function acceptTerms(Request $request): JsonResponse
    {
        $user = $this->requireToken($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $request->validate([
            'terms'   => 'required|accepted',
            'privacy' => 'required|accepted',
        ], [
            'terms.accepted'   => 'You must agree to the Terms of Service.',
            'privacy.accepted' => 'You must agree to the Privacy Policy.',
        ]);

        $ip = $request->ip();

        $user->update([
            'terms_accepted_at'   => now(),
            'privacy_accepted_at' => now(),
            'terms_accepted_ip'   => $ip,
        ]);

        $fresh = $user->fresh();

        return response()->json([
            'ok'        => true,
            'user'      => $this->formatUser($fresh),
            'next_step' => $this->nextStep($fresh),
        ]);
    }

    /* ─────────────── password reset ─────────────── */

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'      => 'required|email',
            'app_url'    => 'nullable|string|max:300',
            'agent_slug' => 'nullable|string|max:80',
        ]);

        // Agent resolution is mandatory — prevents global fallback.
        $agent = $this->requireAgent($request);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        // Scope user lookup to this agent site.
        $user = User::where('email', $request->email)
            ->where('agent_id', $agent->id)
            ->first();

        // Always return ok — don't reveal whether email exists.
        if (! $user) {
            return response()->json(['ok' => true]);
        }

        $plain  = Str::random(64);
        $hashed = hash('sha256', $plain);

        DB::table('user_password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $hashed, 'created_at' => now()]
        );

        $appUrl   = rtrim($request->app_url ?? config('app.url'), '/');
        $resetUrl = $appUrl . '/reset-password?token=' . $plain . '&email=' . urlencode($request->email)
            . ($request->agent_slug ? '&agent_slug=' . urlencode($request->agent_slug) : '');
        $branding = $this->agentBranding($request->agent_slug);

        try {
            $pMail = new PasswordResetMail(
                email:          $user->email,
                resetUrl:       $resetUrl,
                agentName:      $branding['name'],
                agentBrokerage: $branding['brokerage'],
                accentColor:    $branding['accent'],
            );
            $pMail->from($branding['from_email'], $branding['from_name']);
            Mail::to($user->email)->send($pMail);
        } catch (\Exception $e) {
            logger()->warning('PasswordResetMail failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'      => 'required|email',
            'token'      => 'required|string',
            'password'   => 'required|min:8|confirmed',
            'agent_slug' => 'nullable|string|max:80',
        ], [
            'password.min'       => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        $row = DB::table('user_password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $row) {
            return response()->json(['error' => 'Invalid or expired reset link.'], 422);
        }

        // Expire after 1 hour
        if (now()->diffInMinutes($row->created_at, false) < -60) {
            DB::table('user_password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['error' => 'This reset link has expired. Please request a new one.'], 422);
        }

        if (! hash_equals($row->token, hash('sha256', $request->token))) {
            return response()->json(['error' => 'Invalid or expired reset link.'], 422);
        }

        // Agent resolution is mandatory — prevents global fallback.
        $agent = $this->requireAgent($request);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        $user = User::where('email', $request->email)
            ->where('agent_id', $agent->id)
            ->first();
        if (! $user) {
            return response()->json(['error' => 'Account not found.'], 422);
        }

        $user->update(['password' => $request->password]);

        DB::table('user_password_reset_tokens')->where('email', $request->email)->delete();

        // Revoke all existing tokens
        $user->tokens()->delete();

        return response()->json(['ok' => true]);
    }

    /* ─────────────── Google OAuth ─────────────── */

    /* ─── Google OAuth (direct HTTP, no Socialite) ─── */

    private function resolveAgentCallbackOrigin(?Agent $agent): string
    {
        $customDomain = $agent?->settings?->custom_domain;
        if ($customDomain) {
            return 'https://' . rtrim($customDomain, '/');
        }
        return rtrim(config('app.url'), '/');
    }

    /** GET /api-internal/auth/google/redirect?slug={slug} */
    public function googleRedirect(Request $request): JsonResponse
    {
        $request->validate(['slug' => 'required|string']);

        $clientId = config('services.google.client_id');
        if (! $clientId) {
            return response()->json(['error' => 'Google sign-in is not configured.'], 503);
        }

        $slug     = $request->slug;
        $hmac     = hash_hmac('sha256', $slug, config('app.key'));
        $state    = base64_encode(json_encode(['slug' => $slug, 'hmac' => $hmac]));
        $redirect = config('services.google.redirect');

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
        ]);

        return response()->json(['url' => $url]);
    }

    /** GET /api-internal/auth/google/callback */
    public function googleCallback(Request $request): RedirectResponse
    {
        $stateRaw  = $request->input('state', '');
        $stateData = json_decode(base64_decode($stateRaw), true) ?? [];
        $slug      = (string) ($stateData['slug'] ?? '');
        $hmac      = (string) ($stateData['hmac'] ?? '');

        if (! hash_equals(hash_hmac('sha256', $slug, config('app.key')), $hmac)) {
            \Log::warning('Google OAuth: invalid state signature', ['ip' => $request->ip()]);
            return redirect(config('app.url'));
        }

        $agent = Agent::where('slug', $slug)->where('status', 'active')->with('settings')->first();

        // Mandatory agent check — reject before any user lookup or creation.
        // A crafted slug that passes HMAC but doesn't map to a real agent must not
        // produce a user row with agent_id = null.
        if (! $agent) {
            \Log::warning('Google OAuth: unknown or inactive agent slug', ['slug' => $slug, 'ip' => $request->ip()]);
            return redirect(config('app.url') . '?google_error=invalid_agent');
        }

        $origin    = $this->resolveAgentCallbackOrigin($agent);
        $errorBase = "{$origin}/agent/{$slug}/sign-in";

        if ($request->input('error')) {
            return redirect($errorBase . '?google_error=' . urlencode($request->input('error', 'cancelled')));
        }

        $code = $request->input('code');
        if (! $code) {
            return redirect($errorBase . '?google_error=no_code');
        }

        try {
            $tokenResponse = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code'          => $code,
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri'  => config('services.google.redirect'),
                'grant_type'    => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                \Log::warning('Google OAuth token exchange failed', ['body' => $tokenResponse->body()]);
                return redirect($errorBase . '?google_error=token_failed');
            }

            $tokens      = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;
            if (! $accessToken) {
                return redirect($errorBase . '?google_error=no_access_token');
            }

            $userResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if (! $userResponse->successful()) {
                return redirect($errorBase . '?google_error=userinfo_failed');
            }

            $googleUser = $userResponse->json();
        } catch (\Throwable $e) {
            \Log::error('Google OAuth callback failed: ' . $e->getMessage());
            return redirect($errorBase . '?google_error=auth_failed');
        }

        $email = $googleUser['email'] ?? null;
        if (! $email) {
            return redirect($errorBase . '?google_error=no_email');
        }

        $googleId = $googleUser['id'] ?? $googleUser['sub'] ?? null;

        // Scope user lookup to this agent site — agent guaranteed non-null by check above.
        $existing = User::where('email', $email)
            ->where('agent_id', $agent->id)
            ->first();

        if ($existing) {
            $existing->update([
                'google_id'         => $googleId,
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ]);
            $user = $existing;
        } else {
            $firstName = $googleUser['given_name'] ?? null;
            $lastName  = $googleUser['family_name'] ?? null;
            $fullName  = trim(($firstName ?? '') . ' ' . ($lastName ?? '')) ?: $email;

            $user = User::create([
                'agent_id'          => $agent->id,
                'name'              => $fullName,
                'email'             => $email,
                'google_id'         => $googleId,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email_verified_at' => now(),
                'terms_agreed_at'   => now(),
                'password'          => Hash::make(Str::random(32)),
            ]);

            $googleBranding = $this->agentBranding($slug ?: null);
            try {
                Mail::to($user->email)->send(new WelcomeUserMail(
                    firstName:      $user->first_name ?? '',
                    agentName:      $googleBranding['name'],
                    agentBrokerage: $googleBranding['brokerage'],
                    accentColor:    $googleBranding['accent'],
                ));
            } catch (\Exception $e) {
                logger()->warning('WelcomeUserMail (Google) failed: ' . $e->getMessage());
            }
        }

        $plain    = $this->createToken($user);
        $nextStep = $this->nextStep($user);

        $exchCode = Str::random(48);
        cache(["google_oauth:{$exchCode}" => [
            'token'     => $plain,
            'next_step' => $nextStep,
            'slug'      => $slug,
        ]], now()->addMinutes(5));

        return redirect("{$origin}/api/auth/google/complete?" . http_build_query(['code' => $exchCode]));
    }

    /** POST /api-internal/auth/google/exchange */
    public function googleExchange(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:100']);

        $data = cache()->pull("google_oauth:{$request->code}");

        if (! $data) {
            return response()->json(['error' => 'Invalid or expired OAuth code.'], 422);
        }

        return response()->json([
            'token'     => $data['token'],
            'next_step' => $data['next_step'],
            'slug'      => $data['slug'],
        ]);
    }

    /* ─────────────── Apple Sign In With Apple (SIWA) OAuth ─────────────── */

    /**
     * Generate a signed client_secret JWT for Apple API calls (ES256, no library needed).
     * Apple requires a short-lived JWT signed with the developer's .p8 EC private key.
     */
    private function generateAppleClientSecret(): string
    {
        $teamId   = config('services.apple.team_id');
        $keyId    = config('services.apple.key_id');
        $clientId = config('services.apple.client_id');
        $keyPath  = config('services.apple.private_key_path');

        if (!$teamId || !$keyId || !$clientId || !$keyPath || !file_exists($keyPath)) {
            throw new \RuntimeException('Apple SIWA credentials not configured');
        }

        $now     = time();
        $header  = $this->base64UrlEncode(json_encode(['alg' => 'ES256', 'kid' => $keyId]));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $teamId,
            'iat' => $now,
            'exp' => $now + 3600,
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ]));
        $signingInput = "{$header}.{$payload}";

        $key = openssl_pkey_get_private(file_get_contents($keyPath));
        if (!$key) {
            throw new \RuntimeException('Failed to load Apple private key');
        }
        openssl_sign($signingInput, $derSig, $key, OPENSSL_ALGO_SHA256);

        return "{$signingInput}." . $this->base64UrlEncode($this->derToRawEcSignature($derSig));
    }

    /**
     * Convert DER-encoded ECDSA signature to raw r||s (64 bytes for P-256).
     * PHP's openssl_sign produces DER; Apple's JWT spec expects raw.
     */
    private function derToRawEcSignature(string $der): string
    {
        $offset = 2; // skip 0x30 (SEQUENCE) + length
        // Handle long-form length encoding
        if (ord($der[1]) & 0x80) {
            $offset += ord($der[1]) & 0x7f;
        }
        // Read r
        $offset++; // skip 0x02 (INTEGER)
        $rLen = ord($der[$offset++]);
        $r    = substr($der, $offset, $rLen);
        $offset += $rLen;
        // Read s
        $offset++; // skip 0x02 (INTEGER)
        $sLen = ord($der[$offset++]);
        $s    = substr($der, $offset, $sLen);
        // Pad or trim each component to exactly 32 bytes (P-256 = 256 bits)
        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
        return $r . $s;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** GET /api-internal/auth/apple/redirect?slug={slug} */
    public function appleRedirect(Request $request): JsonResponse
    {
        $request->validate(['slug' => 'required|string']);

        $clientId = config('services.apple.client_id');
        if (!$clientId || !config('services.apple.team_id') || !config('services.apple.key_id')) {
            return response()->json(['error' => 'Apple sign-in is not configured.'], 503);
        }

        $slug    = $request->slug;
        $hmac    = hash_hmac('sha256', $slug, config('app.key'));
        $state   = base64_encode(json_encode(['slug' => $slug, 'hmac' => $hmac]));
        $redirect = config('services.apple.redirect');

        $url = 'https://appleid.apple.com/auth/authorize?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope'         => 'name email',
            'state'         => $state,
        ]);

        return response()->json(['url' => $url]);
    }

    /** GET /api-internal/auth/apple/callback */
    public function appleCallback(Request $request): RedirectResponse
    {
        $stateRaw  = $request->input('state', '');
        $stateData = json_decode(base64_decode($stateRaw), true) ?? [];
        $slug      = (string) ($stateData['slug'] ?? '');
        $hmac      = (string) ($stateData['hmac'] ?? '');

        if (!hash_equals(hash_hmac('sha256', $slug, config('app.key')), $hmac)) {
            \Log::warning('Apple SIWA: invalid state signature', ['ip' => $request->ip()]);
            return redirect(config('app.url'));
        }

        $agent = Agent::where('slug', $slug)->where('status', 'active')->with('settings')->first();

        // Mandatory agent check — reject before any user lookup or creation.
        // A crafted slug that passes HMAC but doesn't map to a real agent must not
        // produce a user row with agent_id = null.
        if (!$agent) {
            \Log::warning('Apple SIWA: unknown or inactive agent slug', ['slug' => $slug, 'ip' => $request->ip()]);
            return redirect(config('app.url') . '?apple_error=invalid_agent');
        }

        $origin    = $this->resolveAgentCallbackOrigin($agent);
        $errorBase = "{$origin}/agent/{$slug}/sign-in";

        if ($request->input('error')) {
            return redirect($errorBase . '?apple_error=' . urlencode($request->input('error', 'cancelled')));
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect($errorBase . '?apple_error=no_code');
        }

        try {
            $clientSecret = $this->generateAppleClientSecret();
        } catch (\Throwable $e) {
            \Log::error('Apple SIWA: client_secret generation failed: ' . $e->getMessage());
            return redirect($errorBase . '?apple_error=not_configured');
        }

        try {
            $tokenResponse = \Illuminate\Support\Facades\Http::asForm()->post(
                'https://appleid.apple.com/auth/token',
                [
                    'client_id'     => config('services.apple.client_id'),
                    'client_secret' => $clientSecret,
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => config('services.apple.redirect'),
                ]
            );

            if (!$tokenResponse->successful()) {
                \Log::warning('Apple SIWA token exchange failed', ['body' => $tokenResponse->body()]);
                return redirect($errorBase . '?apple_error=token_failed');
            }

            $tokens  = $tokenResponse->json();
            $idToken = $tokens['id_token'] ?? null;
            if (!$idToken) {
                return redirect($errorBase . '?apple_error=no_id_token');
            }

            // Decode id_token payload (Apple has already verified it server-to-server)
            $parts   = explode('.', $idToken);
            $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4 === 0 ? strlen($parts[1]) : strlen($parts[1]) + 4 - strlen($parts[1]) % 4, '=')), true) ?? [];

            $appleId = $payload['sub']   ?? null;
            $email   = $payload['email'] ?? null;

            if (!$appleId) {
                return redirect($errorBase . '?apple_error=no_apple_id');
            }
        } catch (\Throwable $e) {
            \Log::error('Apple SIWA callback failed: ' . $e->getMessage());
            return redirect($errorBase . '?apple_error=auth_failed');
        }

        // Find or create user — agent guaranteed non-null by check above.
        $existing = $appleId
            ? User::where('apple_id', $appleId)->where('agent_id', $agent->id)->first()
            : null;
        if (!$existing && $email) {
            $existing = User::where('email', $email)->where('agent_id', $agent->id)->first();
        }

        if ($existing) {
            $updates = ['apple_id' => $appleId];
            if (!$existing->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            $existing->update($updates);
            $user = $existing;
        } else {
            if (!$email) {
                return redirect($errorBase . '?apple_error=no_email');
            }
            // First-time sign-in: Apple may provide name in user JSON param (form_post)
            // For query-mode callbacks, name is not available - use email prefix as fallback
            $firstName = null;
            $lastName  = null;
            if ($request->input('user')) {
                $userJson  = json_decode($request->input('user'), true) ?? [];
                $firstName = $userJson['name']['firstName'] ?? null;
                $lastName  = $userJson['name']['lastName']  ?? null;
            }
            $displayName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
            if (!$displayName) {
                $displayName = explode('@', $email)[0];
            }

            $user = User::create([
                'agent_id'          => $agent->id,
                'name'              => $displayName,
                'email'             => $email,
                'apple_id'          => $appleId,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email_verified_at' => now(),
                'terms_agreed_at'   => now(),
                'password'          => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            ]);

            $appleBranding = $this->agentBranding($slug ?: null);
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\WelcomeUserMail(
                    firstName:      $user->first_name ?? '',
                    agentName:      $appleBranding['name'],
                    agentBrokerage: $appleBranding['brokerage'],
                    accentColor:    $appleBranding['accent'],
                ));
            } catch (\Exception $e) {
                logger()->warning('WelcomeUserMail (Apple) failed: ' . $e->getMessage());
            }
        }

        $plain    = $this->createToken($user);
        $nextStep = $this->nextStep($user);

        $exchCode = \Illuminate\Support\Str::random(48);
        cache(["apple_oauth:{$exchCode}" => [
            'token'     => $plain,
            'next_step' => $nextStep,
            'slug'      => $slug,
        ]], now()->addMinutes(5));

        return redirect("{$origin}/api/auth/apple/complete?" . http_build_query(['code' => $exchCode]));
    }

    /** POST /api-internal/auth/apple/exchange */
    public function appleExchange(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:100']);

        $data = cache()->pull("apple_oauth:{$request->code}");

        if (!$data) {
            return response()->json(['error' => 'Invalid or expired Apple OAuth code.'], 422);
        }

        return response()->json([
            'token'     => $data['token'],
            'next_step' => $data['next_step'],
            'slug'      => $data['slug'],
        ]);
    }



    /* -- passwordless register -- */

    public function registerPasswordless(Request $request): JsonResponse
    {
        // Registration rate-limit: 5 per 10 minutes per IP (CF-Connecting-IP → $request->ip()).
        $regIp  = $request->header('CF-Connecting-IP') ?: ($request->ip() ?? '0.0.0.0');
        $regKey = 'register-pl:' . sha1($regIp);
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($regKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($regKey);
            return response()->json([
                'message' => 'Too many registration attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($regKey, 600); // 10-minute window

        $request->validate([
            'email'         => 'required|email',
            'terms'         => 'required|accepted',
            'app_url'       => 'nullable|string|max:300',
            'agent_slug'    => 'nullable|string|max:80',
            'first_name'    => 'nullable|string|max:50',
            'last_name'     => 'nullable|string|max:50',
            'source_url'    => 'nullable|string|max:500',
            'source_type'   => 'nullable|string|max:20',
            'listing_id'    => 'nullable|string|max:40',
            'building_slug' => 'nullable|string|max:100',
            'min_beds'      => 'nullable|integer|min:0',
            'min_price'     => 'nullable|integer|min:0',
            'max_price'     => 'nullable|integer|min:0',
            'subarea'       => 'nullable|string|max:120',
            'property_type' => 'nullable|string|max:60',
            'phone'              => 'required|string|min:7|max:30',
            'phone_country_code' => 'nullable|string|max:10',
        ], [
            'terms.required'  => 'You must agree to the terms to continue.',
            'terms.accepted'  => 'You must agree to the terms to continue.',
            'phone.required'  => 'A phone number is required to create an account.',
            'phone.min'       => 'Please enter a valid phone number.',
        ]);

        // Resolve agent — mandatory for per-site isolation.
        $agent = $this->requireAgent($request);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        // Check email uniqueness scoped to this agent site.
        $emailTaken = User::where('email', $request->email)
            ->where('agent_id', $agent->id)
            ->exists();
        if ($emailTaken) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['email' => ['An account with this email already exists.']],
            ], 422);
        }

        $firstName = $request->first_name;
        $lastName  = $request->last_name;
        $fullName  = trim("{$firstName} {$lastName}") ?: $request->email;

        // Server-side digit guard: even if the rule passes, ensure ≥7 numeric digits.
        $rawPhone = $request->input('phone', '');
        $regPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($regPhone) < 7) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['phone' => ['Please enter a valid phone number (at least 7 digits).']],
            ], 422);
        }
        $regCountryCode  = $request->input('phone_country_code') ?: '+1';
        $user = User::create([
            'agent_id'          => $agent->id,
            'name'              => $fullName,
            'email'             => $request->email,
            'password'          => Hash::make(bin2hex(random_bytes(24))),
            'first_name'        => $firstName ?: null,
            'last_name'         => $lastName  ?: null,
            'terms_agreed_at'   => now(),
            'email_verified_at' => now(),
            'phone'             => $regPhone ?: null,
            'phone_country_code' => $regPhone ? $regCountryCode : null,
        ]);

        // Create a lead record so this registration appears in the agent CRM.
        try {
            $regSourceUrl = $request->input('source_url') ?: $request->headers->get('referer');
            $hasUidColPwl = \Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'user_id');
            $leadRowPwl = [
                'agent_id'   => $agent->id,
                'form_type'  => 'registration',
                'name'       => $fullName,
                'first_name' => $firstName ?: null,
                'last_name'  => $lastName  ?: null,
                'email'      => $user->email,
                'source_url' => $regSourceUrl,
                'ip_hash'    => hash('sha256', $request->ip() ?? ''),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasUidColPwl) {
                $leadRowPwl['user_id'] = $user->id;
            }
            // Include phone in the lead row when the column exists.
            if ($regPhone) {
                $hasPhoneColPwl = \Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'phone');
                if ($hasPhoneColPwl) {
                    $leadRowPwl['phone'] = $regPhone;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'phone_country_code')) {
                        $leadRowPwl['phone_country_code'] = $regCountryCode;
                    }
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'message')) {
                    $leadRowPwl['message'] = 'Phone: ' . $regCountryCode . ' ' . $regPhone;
                }
            }
            \Illuminate\Support\Facades\DB::table('agent_leads')->insert($leadRowPwl);
            $regData = [
                'name'          => $fullName,
                'first_name'    => $firstName ?: null,
                'last_name'     => $lastName  ?: null,
                'email'         => $user->email,
                'form_type'     => 'registration',
                'source_url'    => $regSourceUrl,
                'source_type'   => $request->input('source_type') ?: null,
                'listing_id'    => $request->input('listing_id') ?: null,
                'building_slug' => $request->input('building_slug') ?: null,
                'min_beds'      => $request->input('min_beds') ? (int) $request->input('min_beds') : null,
                'min_price'     => $request->input('min_price') ? (int) $request->input('min_price') : null,
                'max_price'     => $request->input('max_price') ? (int) $request->input('max_price') : null,
                'subarea'       => $request->input('subarea') ?: null,
                'property_type' => $request->input('property_type') ?: null,
                'phone'             => $regPhone ?: null,
                'phone_country_code' => $regPhone ? $regCountryCode : null,
            ];
            LeadPipeline::pushToFollowUpBoss($agent, $regData);
            LeadPipeline::pushToGoHighLevel($agent, $regData);
            LeadPipeline::pushToLofty($agent, $regData);

            // Agent notification email for new registration.
            $regNotifyEmail = $agent->settings?->notification_email ?: $agent->email ?? null;
            if ($regNotifyEmail) {
                try {
                    $regDomain = $agent->settings?->custom_domain ?? ($agent->slug . '.pixilink.com');
                    $regSrcLabel = (static function ($req) {
                        $sType = $req->input('source_type');
                        $lid   = $req->input('listing_id');
                        $bslug = $req->input('building_slug');
                        $area  = $req->input('subarea');
                        $ptype = $req->input('property_type');
                        $beds  = $req->input('min_beds') ? (int) $req->input('min_beds') : null;
                        $minP  = $req->input('min_price') ? (int) $req->input('min_price') : null;
                        $maxP  = $req->input('max_price') ? (int) $req->input('max_price') : null;
                        $sUrl  = $req->input('source_url') ?: $req->headers->get('referer');
                        if ($sType === 'listing' && $lid)    return "Listing page: {$lid}";
                        if ($sType === 'building' && $bslug) return 'Building page: ' . ucwords(str_replace('-', ' ', $bslug));
                        if ($sType === 'search') {
                            $parts = array_filter([
                                $area,
                                $beds  ? "{$beds}+ bed" : null,
                                $ptype ?: null,
                                ($minP || $maxP) ? trim(($minP ? '$' . number_format((int) ($minP / 1000)) . 'k' : '') . '–' . ($maxP ? '$' . number_format((int) ($maxP / 1000)) . 'k' : ''), '–') : null,
                            ]);
                            return $parts ? implode(' · ', $parts) : ($sUrl ?? '—');
                        }
                        return $sUrl ?? '—';
                    })($request);
                    \Illuminate\Support\Facades\Mail::raw(
                        "New registration on {$regDomain}\n"
                        . str_repeat('-', 44) . "\n"
                        . "Name:   " . ($fullName ?: '—') . "\n"
                        . "Phone:  " . ($regPhone ? ($regCountryCode . ' ' . $regPhone) : '—') . "\n"
                        . "Email:  {$user->email}\n"
                        . "Source: {$regSrcLabel}\n"
                        . str_repeat('-', 44) . "\n"
                        . "View leads: https://website.pixilink.com/admin/agents/{$agent->id}/leads\n",
                        fn ($m) => $m->to($regNotifyEmail)
                            ->subject('[New Registration] ' . ($fullName ?: $user->email))
                    );
                } catch (\Throwable $notifErr) {
                    \Illuminate\Support\Facades\Log::warning('registerPasswordless: agent notify mail failed: ' . $notifErr->getMessage());
                }
            }
        } catch (\Throwable $regErr) {
            \Illuminate\Support\Facades\Log::warning('registerPasswordless: lead/CRM push failed: ' . $regErr->getMessage());
        }

        $branding = $this->agentBranding($request->agent_slug);

        try {
            Mail::raw(
                "Hi {$firstName},\n\n" .
                "Your account has been created. Whenever you want to sign back in, just enter your email and we'll send you a secure link.\n\n" .
                "\xe2\x80\x94 {$branding['name']}",
                function ($m) use ($user, $branding) {
                    $m->to($user->email)
                      ->from($branding['from_email'], $branding['from_name'])
                      ->subject("Welcome to {$branding['name']}'s website");
                }
            );
        } catch (\Exception $e) {
            logger()->warning('WelcomeMail failed: ' . $e->getMessage());
        }

        $token = $this->createToken($user);

        return response()->json([
            'token'     => $token,
            'user'      => $this->formatUser($user),
            'next_step' => $this->nextStep($user),
        ], 201);
    }

    /* ─────────────── magic link auth ─────────────── */

    private function ensureMagicLinksTable(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS magic_links (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_magic_token (token),
            KEY idx_magic_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    /**
     * POST /api-internal/auth/magic-link/send
     * Send a 15-minute single-use sign-in link to the user's email.
     * Always returns {"ok":true} — never reveals whether the email exists.
     */
    public function sendMagicLink(Request $request): JsonResponse
    {
        $this->ensureMagicLinksTable();

        $email     = strtolower(trim($request->input('email', '')));
        $agentSlug = $request->input('agent_slug', '');
        $appUrl    = rtrim($request->input('app_url', config('app.url')), '/');
        $returnTo  = $request->input('return_to', '');

        // Agent resolution is mandatory — prevents global fallback.
        $agent = $this->requireAgent($request);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        // Scope user lookup to this agent site.
        $user = User::where('email', $email)
            ->where('agent_id', $agent->id)
            ->first();
        if (! $user) {
            return response()->json(['ok' => true]);
        }

        // Rate-limit: one link per minute per email.
        $recent = DB::table('magic_links')
            ->where('email', $email)
            ->where('created_at', '>', now()->subMinute())
            ->exists();
        if ($recent) {
            return response()->json(['ok' => true]);
        }

        $plain  = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $plain);

        DB::table('magic_links')->insert([
            'email'      => $email,
            'token'      => $hashed,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        $branding    = $this->agentBranding($agentSlug ?: null);
        $returnParam = $returnTo ? '&return_to=' . urlencode($returnTo) : '';
        $link        = "{$appUrl}/verify-magic?token={$plain}{$returnParam}";

        try {
            Mail::raw(
                "Hi {$user->first_name},\n\n" .
                "Click the link below to sign in to {$branding['name']}'s website:\n\n" .
                "{$link}\n\n" .
                "This link expires in 15 minutes and can only be used once.\n\n" .
                "If you didn't request this, you can safely ignore this email.",
                function ($m) use ($email, $branding) {
                    $m->to($email)
                      ->from($branding['from_email'], $branding['from_name'])
                      ->subject("Sign in to {$branding['name']}'s website");
                }
            );
        } catch (\Exception $e) {
            logger()->warning('MagicLinkMail failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api-internal/auth/magic-link/verify
     * Consume a magic-link token and return a Bearer token on success.
     */
    public function verifyMagicLink(Request $request): JsonResponse
    {
        $this->ensureMagicLinksTable();

        $plain = $request->input('token', '');
        if (! $plain) {
            return response()->json(['error' => 'Missing token.'], 422);
        }

        $hashed = hash('sha256', $plain);

        $row = DB::table('magic_links')
            ->where('token', $hashed)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $row) {
            return response()->json([
                'error' => 'This sign-in link has expired or already been used. Please request a new one.',
            ], 401);
        }

        // Resolve agent BEFORE consuming the token — if resolution fails,
        // the link stays valid so the user can retry without burning their attempt.
        $agent = $this->requireAgent($request);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        // Atomic mark-as-used to prevent replay.
        $updated = DB::table('magic_links')
            ->where('token', $hashed)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        if (! $updated) {
            return response()->json([
                'error' => 'This sign-in link has already been used. Please request a new one.',
            ], 401);
        }

        $user = User::where('email', $row->email)
            ->where('agent_id', $agent->id)
            ->first();
        if (! $user) {
            return response()->json(['error' => 'Account not found.'], 404);
        }

        $token = $this->createToken($user, true);

        return response()->json([
            'token'     => $token,
            'user'      => $this->formatUser($user),
            'next_step' => $this->nextStep($user),
        ]);
    }
}
