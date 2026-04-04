<?php

namespace App\Http\Middleware;

use App\Core\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized('Unauthenticated');
        }

        $rolesArray = [];
        foreach ($roles as $role) {
            $rolesArray = array_merge($rolesArray, explode('|', $role));
        }

        if (!$user->hasAnyRole($rolesArray)) {
            return $this->forbidden('You do not have the required role');
        }

        return $next($request);
    }
}
