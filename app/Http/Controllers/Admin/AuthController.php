<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LoginCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly LoginCodeService $codes) {}

    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $clientIp = $request->header('CF-Connecting-IP') ?: $request->ip();
        $throttleKey = Str::lower($request->input('email')) . '|' . $clientIp;

        if (RateLimiter::tooManyAttempts('admin-login:' . $throttleKey, 5)) {
            $seconds = RateLimiter::availableIn('admin-login:' . $throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit('admin-login:' . $throttleKey, 900);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear('admin-login:' . $throttleKey);

        // Password verified. Second factor next — so log the guard straight back OUT and
        // hold the identity in the session instead. Auth::attempt() has already
        // established a session; leaving it in place would mean a correct password alone
        // grants access to every admin route, and the code screen would be decoration
        // anyone could skip by typing a URL.
        $admin = Auth::guard('admin')->user();
        Auth::guard('admin')->logout();

        if ($this->codes->deviceIsTrusted('admin', $admin->id, $request->cookie(LoginCodeService::DEVICE_COOKIE))) {
            return $this->completeLogin($request, $admin->id);
        }

        $request->session()->put('admin_2fa', [
            'id'          => $admin->id,
            'email'       => $admin->email,
            'remember'    => $request->boolean('remember'),
            'started_at'  => now()->timestamp,
        ]);

        $this->codes->issue('admin', $admin->id, $admin->email, $clientIp);

        return redirect()->route('admin.login.verify');
    }

    /** Step two: the emailed code. */
    public function showVerify(Request $request)
    {
        if (! $request->session()->has('admin_2fa')) {
            return redirect()->route('admin.login');
        }

        $pending = $request->session()->get('admin_2fa');

        return view('admin.auth.verify', [
            'email' => $this->maskEmail($pending['email'] ?? ''),
            'ttl'   => 10,
        ]);
    }

    public function verify(Request $request)
    {
        $pending = $request->session()->get('admin_2fa');
        if (! $pending) {
            return redirect()->route('admin.login');
        }

        // The challenge itself expires. Without this a half-finished login could sit in
        // a session for as long as the browser kept it and be completed later.
        if (now()->timestamp - ($pending['started_at'] ?? 0) > 900) {
            $request->session()->forget('admin_2fa');
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'That sign-in attempt expired. Please start again.']);
        }

        $request->validate(['code' => 'required|string|max:12']);

        $clientIp = $request->header('CF-Connecting-IP') ?: $request->ip();
        $key = 'admin-2fa:' . $pending['id'] . '|' . $clientIp;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $request->session()->forget('admin_2fa');
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Too many attempts. Please sign in again.']);
        }

        if (! $this->codes->verify('admin', (int) $pending['id'], $request->input('code'))) {
            RateLimiter::hit($key, 900);
            return back()->withErrors(['code' => 'That code is not valid. Check the latest email, or request a new code.']);
        }

        RateLimiter::clear($key);

        return $this->completeLogin($request, (int) $pending['id'], $request->boolean('trust_device'));
    }

    public function resend(Request $request)
    {
        $pending = $request->session()->get('admin_2fa');
        if (! $pending) {
            return redirect()->route('admin.login');
        }

        $clientIp = $request->header('CF-Connecting-IP') ?: $request->ip();
        $this->codes->issue('admin', (int) $pending['id'], $pending['email'], $clientIp);

        // Same message whether or not a code was actually sent — the issuing limiter
        // must not become a way to probe how often an account is being targeted.
        return back()->with('status', 'If that account needs a code, a new one has been sent.');
    }

    /** Establish the real session, and optionally remember the device. */
    private function completeLogin(Request $request, int $adminId, bool $trustDevice = false)
    {
        $pending = $request->session()->get('admin_2fa', []);

        Auth::guard('admin')->loginUsingId($adminId, (bool) ($pending['remember'] ?? false));

        $request->session()->forget('admin_2fa');
        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();
        $admin->update(['last_login_at' => now()]);

        $response = redirect()->intended(route('admin.agents.index'));

        if ($trustDevice) {
            $raw = $this->codes->trustDevice(
                'admin',
                $adminId,
                $request->userAgent(),
                $request->header('CF-Connecting-IP') ?: $request->ip(),
            );

            $response->cookie(
                LoginCodeService::DEVICE_COOKIE,
                $raw,
                60 * 24 * $this->codes->deviceTtlDays(),
                '/',
                null,
                true,   // secure — never sent over plain http
                true,   // httpOnly — unreadable to JavaScript, so XSS cannot lift it
                false,
                'Lax',
            );
        }

        return $response;
    }

    /** v****r@pixilink.com — enough to recognise, not enough to harvest. */
    private function maskEmail(string $email): string
    {
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($user === '' || $domain === '') return '';

        $visible = mb_strlen($user) <= 2
            ? mb_substr($user, 0, 1)
            : mb_substr($user, 0, 1) . str_repeat('*', max(1, mb_strlen($user) - 2)) . mb_substr($user, -1);

        return $visible . '@' . $domain;
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
