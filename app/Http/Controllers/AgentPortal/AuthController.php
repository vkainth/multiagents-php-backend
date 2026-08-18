<?php

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('agent-portal.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts('agent-login:' . $throttleKey, 10)) {
            $seconds = RateLimiter::availableIn('agent-login:' . $throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::guard('agent')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit('agent-login:' . $throttleKey, 900);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $agent = Auth::guard('agent')->user();

        if (!$agent->isActive()) {
            Auth::guard('agent')->logout();
            throw ValidationException::withMessages([
                'email' => 'Your account is suspended. Please contact support.',
            ]);
        }

        RateLimiter::clear('agent-login:' . $throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('agent-portal.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('agent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agent-portal.login');
    }

    public function showForgotPassword()
    {
        return view('agent-portal.auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('agents')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'We have emailed your password reset link!');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('agent-portal.auth.reset-password', [
            'token' => $token,
            'email' => $request->get('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::broker('agents')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Agent $agent, string $password) {
                $agent->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('agent-portal.login')
                ->with('status', 'Password reset successfully. Please log in.');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
