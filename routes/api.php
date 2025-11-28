<?php

use App\Http\Controllers\Auth\OtpController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')
    ->prefix('api')
    ->group(function (): void {
        Route::prefix('v1')->group(function (): void {
            Route::post('auth/request-otp', [OtpController::class, 'requestOtp'])
                ->middleware('throttle:5,1');
        });
    });
