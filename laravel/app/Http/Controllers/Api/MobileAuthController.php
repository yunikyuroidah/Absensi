<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\LoginAttemptApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $attemptLog = $this->resolveAttemptLog($request->ip());
        $admin = Admin::query()->where('email', $credentials['email'])->first();

        if ($admin && Hash::check($credentials['password'], (string) $admin->password)) {
            $attemptLog->update([
                'attempts' => 0,
                'blocked_until' => null,
            ]);

            $plainToken = Str::random(60);

            $admin->forceFill([
                'api_token_hash' => hash('sha256', $plainToken),
            ])->save();

            return response()->json([
                'token_type' => 'Bearer',
                'access_token' => $plainToken,
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ]);
        }

        if ($attemptLog->blocked_until && $attemptLog->blocked_until->isFuture()) {
            return response()->json([
                'message' => 'Akses sementara diblokir. Coba lagi setelah '.$attemptLog->blocked_until->translatedFormat('d M Y H:i').'.',
                'blocked_until' => $attemptLog->blocked_until->toIso8601String(),
            ], 423);
        }

        $attempts = $attemptLog->attempts + 1;
        $blockedUntil = $attempts >= 5 ? now()->addMinutes(15) : null;

        $attemptLog->update([
            'attempts' => $attempts,
            'blocked_until' => $blockedUntil,
        ]);

        return response()->json([
            'message' => $blockedUntil
                ? 'Email/password salah. IP diblokir sementara selama 15 menit.'
                : 'Email atau password admin tidak sesuai.',
        ], 422);
    }

    public function logout(Request $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin instanceof Admin) {
            $admin->forceFill([
                'api_token_hash' => null,
            ])->save();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
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
