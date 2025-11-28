<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Exceptions\TooManyOtpRequestsException;
use stdClass;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
    }

    public function render($request, Throwable $e): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($e instanceof TooManyOtpRequestsException) {
            return response()->json([
                'status' => 'error',
                'data'   => null,
                'meta'   => new stdClass(),
                'errors' => [
                    [
                        'code'    => 'E1006_TOO_MANY_OTP_REQUESTS',
                        'message' => $e->getMessage(),
                    ],
                ],
            ], 429);
        }

        if ($e instanceof ValidationException && $request->is('api/*')) {
            $errors = collect($e->errors())
                ->flatten()
                ->map(fn ($message) => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => $message,
                ])->values();

            return response()->json([
                'status' => 'error',
                'data'   => null,
                'meta'   => new stdClass(),
                'errors' => $errors,
            ], 422);
        }

        if ($request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'data'   => null,
                'meta'   => new stdClass(),
                'errors' => [
                    [
                        'code'    => 'INTERNAL_SERVER_ERROR',
                        'message' => 'Internal server error',
                    ],
                ],
            ], 500);
        }

        return parent::render($request, $e);
    }
}
