<?php

namespace App\Http\Controllers;

use App\Models\LoginAttemptApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $attemptLog = $this->resolveAttemptLog($request->ip());

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $attemptLog->update([
                'attempts' => 0,
                'blocked_until' => null,
            ]);

            return redirect()->intended(route('admin.dashboard'));
        }

        if ($attemptLog->blocked_until && $attemptLog->blocked_until->isFuture()) {
            return back()
                ->withErrors([
                    'email' => 'Akses sementara diblokir. Coba lagi setelah '.$attemptLog->blocked_until->translatedFormat('d M Y H:i').'.',
                ])
                ->withInput($request->only('email'));
        }

        $attempts = $attemptLog->attempts + 1;
        $blockedUntil = $attempts >= 5 ? now()->addMinutes(15) : null;

        $attemptLog->update([
            'attempts' => $attempts,
            'blocked_until' => $blockedUntil,
        ]);

        return back()
            ->withErrors([
                'email' => $blockedUntil
                    ? 'Email/password salah. IP diblokir sementara selama 15 menit.'
                    : 'Email atau password admin tidak sesuai.',
            ])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logout berhasil.');
    }

    private function resolveAttemptLog(?string $ipAddress): LoginAttemptApi
    {
        $ip = $ipAddress ?: '0.0.0.0';

        return LoginAttemptApi::query()->firstOrCreate(
            ['ip_address' => $ip],
            ['attempts' => 0, 'blocked_until' => null]
        );
    }
}
