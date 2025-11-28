<?php

use App\Exceptions\TooManyOtpRequestsException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TooManyOtpRequestsException $exception, Request $request) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'meta' => new stdClass(),
                'errors' => [
                    [
                        'code' => 'E1006_TOO_MANY_OTP_REQUESTS',
                        'message' => $exception->getMessage(),
                    ],
                ],
            ], 429);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            $errors = collect($exception->errors())
                ->map(function (array $messages) {
                    return [
                        'code' => 'VALIDATION_FAILED',
                        'message' => $messages[0] ?? 'Validation failed.',
                    ];
                })
                ->values();

            return response()->json([
                'status' => 'error',
                'data' => null,
                'meta' => new stdClass(),
                'errors' => $errors,
            ], 422);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

            return response()->json([
                'status' => 'error',
                'data' => null,
                'meta' => new stdClass(),
                'errors' => [
                    [
                        'code' => $status === 500 ? 'SERVER_ERROR' : 'HTTP_ERROR',
                        'message' => $status === 500 ? 'An unexpected error occurred.' : $exception->getMessage(),
                    ],
                ],
            ], $status);
        });
    })->create();
