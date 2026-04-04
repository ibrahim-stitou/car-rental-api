<?php

namespace App\Http\Middleware;

use App\Core\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->unauthorized('User not found');
            }

            if (!$user->is_active) {
                return $this->error('Account is deactivated', 403);
            }
        } catch (TokenExpiredException $e) {
            return $this->error('Token has expired', 401);
        } catch (TokenInvalidException $e) {
            return $this->error('Token is invalid', 401);
        } catch (JWTException $e) {
            return $this->error('Token is absent', 401);
        }

        return $next($request);
    }
}
