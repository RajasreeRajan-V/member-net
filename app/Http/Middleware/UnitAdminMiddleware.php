<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UnitAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('unit_admin');

        if (!$guard->check()) {
            return redirect()->route('unit-admin.login');
        }

        $user = $guard->user();

        if ($user->role !== 'unit_admin' || !$user->is_active) {
            $guard->logout();   // only this guard, other panels stay logged in
            return redirect()->route('unit-admin.login')
                ->withErrors(['email' => $user->is_active ? 'Access denied.' : 'Your account has been deactivated.']);
        }

        Auth::shouldUse('unit_admin');

        return $next($request);
    }
}