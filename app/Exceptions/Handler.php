<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Exceptions\TooManyOtpRequestsException;
use App\Exceptions\InvalidOtpCodeException;
use App\Exceptions\OtpMaxAttemptsException;
use App\Exceptions\OtpNotFoundOrExpiredException;
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

        if ($e instanceof OtpNotFoundOrExpiredException) {
            return response()->json([
                'status' => 'error',
                'data'   => null,
                'meta'   => new stdClass(),
                'errors' => [
                    [
                        'code'    => 'E2001_OTP_NOT_FOUND_OR_EXPIRED',
                        'message' => 'OTP not found or expired. Please request a new one.',
                    ],
                ],
            ], 400);
        }

        if ($e instanceof OtpMaxAttemptsException) {
            return response()->json([
                'status' => 'error',
                'data'   => null,
                'meta'   => new stdClass(),
                'errors' => [
                    [
                        'code'    => 'E2002_OTP_MAX_ATTEMPTS_REACHED',
                        'message' => 'Maximum OTP attempts reached. Please request a new OTP.',
                    ],
                ],
            ], 400);
        }

        if ($e instanceof InvalidOtpCodeException) {
            return response()->json([
                'status' => 'error',
                'data'   => null,
                'meta'   => new stdClass(),
                'errors' => [
                    [
                        'code'    => 'E2003_INVALID_OTP',
                        'message' => 'Invalid OTP code.',
                    ],
                ],
            ], 400);
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
