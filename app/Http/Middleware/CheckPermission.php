<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Usage on routes:  ->middleware('perm:catalog.vehicle-types.view')
     *
     * The middleware accepts one or more comma-separated permission keys.
     * The user needs AT LEAST ONE of the supplied keys (OR logic).
     * Users whose role has 'all' always pass.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // 'all' permission = admin bypass
        if ($user->hasPermission('all')) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        // AJAX / JSON requests get a 403 JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Forbidden. You do not have permission to perform this action.'], 403);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
