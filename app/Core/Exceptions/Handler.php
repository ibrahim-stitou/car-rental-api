<?php

namespace App\Core\Exceptions;

use App\Core\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return $this->validationError($e->errors());
        }

        if ($e instanceof AuthenticationException) {
            return $this->unauthorized('Unauthenticated');
        }

        if ($e instanceof AccessDeniedHttpException) {
            return $this->forbidden('Forbidden');
        }

        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return $this->notFound("{$model} not found");
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->notFound('Route not found');
        }

        if ($e instanceof TokenExpiredException) {
            return $this->error('Token has expired', 401);
        }

        if ($e instanceof TokenInvalidException) {
            return $this->error('Token is invalid', 401);
        }

        if ($e instanceof JWTException) {
            return $this->error('Token is absent or malformed', 401);
        }

        if (config('app.debug')) {
            return $this->error($e->getMessage(), 500, [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => collect($e->getTrace())->take(5)->toArray(),
            ]);
        }

        return $this->error('Server Error', 500);
    }
}

