<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DistrictAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('district_admin');

        if (!$guard->check()) {
            return redirect()->route('district-admin.login');
        }

        $user = $guard->user();

        if ($user->role !== 'district_admin' || !$user->is_active) {
            $guard->logout();
            return redirect()->route('district-admin.login')
                ->withErrors(['email' => $user->is_active ? 'Access denied.' : 'Your account has been deactivated.']);
        }

        Auth::shouldUse('district_admin');

        return $next($request);
    }
}