<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been suspended. Please contact the administrator.',
            ]);
        }

        if (!$user->is_super_admin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Super Admin access required.'], 403);
            }

            return Inertia::render('Error', [
                'status' => 403,
                'message' => 'Unauthorized. Super Admin privileges required to access this area.',
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
