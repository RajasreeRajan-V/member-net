<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('super_admin');

        if (!$guard->check()) {
            return redirect()->route('admin.login');
        }

        $user = $guard->user();

        if ($user->role !== 'super_admin' || !$user->is_active) {
            $guard->logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Access denied.']);
        }

        Auth::shouldUse('super_admin');

        return $next($request);
    }
}