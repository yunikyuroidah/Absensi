<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Http\Middleware\AdminApiToken;

function sessionExpiredResponse(Request $request)
{
    if ($request->expectsJson()) {
        return response()->json([
            'message' => 'Sesi kadaluarsa. Silakan refresh lalu coba lagi.',
        ], 419);
    }

    $targetRoute = $request->is('admin/*') ? 'admin.login' : 'public.attendance.index';

    return redirect()
        ->route($targetRoute)
        ->with('error', 'Sesi halaman sudah habis (419). Silakan muat ulang lalu coba lagi.');
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.api' => AdminApiToken::class,
        ]);

        $middleware->redirectTo(
            guests: fn (Request $request) => route('admin.login'),
            users: fn (Request $request) => route('admin.dashboard'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            return sessionExpiredResponse($request);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            return sessionExpiredResponse($request);
        });
    })->create();
