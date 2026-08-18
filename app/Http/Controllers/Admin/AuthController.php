<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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
        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();
        $admin->update(['last_login_at' => now()]);

        return redirect()->route('admin.agents.index');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
