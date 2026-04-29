<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Unauthorized: token tidak ditemukan.',
            ], 401);
        }

        $admin = Admin::query()
            ->where('api_token_hash', hash('sha256', $token))
            ->first();

        if (! $admin) {
            return response()->json([
                'message' => 'Unauthorized: token tidak valid.',
            ], 401);
        }

        $request->setUserResolver(static fn () => $admin);

        return $next($request);
    }
}
