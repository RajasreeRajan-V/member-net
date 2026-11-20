<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'unit-admin/login',
            'admin/login',
            'district-admin/login',
            'admin/unit-admin/login',
        ]);
        $middleware->alias([
            'super_admin'    => \App\Http\Middleware\SuperAdminMiddleware::class,
            'unit_admin'     => \App\Http\Middleware\UnitAdminMiddleware::class,
            'district_admin' => \App\Http\Middleware\DistrictAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $allowed = implode(', ', $e->getHeaders()['Allow'] ?? ['POST']);
                return response()->json([
                    'status'      => '0',
                    'status_code' => '405',
                    'message'     => "The {$request->method()} method is not supported for this endpoint. Supported methods: {$allowed}.",
                    'data'        => (object) [],
                ], 405);
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            // API / JSON
            if ($request->expectsJson()) {
                return \App\Helpers\ApiResponse::error(
                    'Unauthenticated. Please login to continue.',
                    401
                );
            }

            // Web: redirect to the correct login page
            if (str_starts_with($request->path(), 'district-admin')) {
                return redirect()->route('district-admin.login');
            }
            if (str_starts_with($request->path(), 'unit-admin')) {
                return redirect()->route('unit-admin.login');
            }
            return redirect()->route('admin.login');
        });

    })->create();