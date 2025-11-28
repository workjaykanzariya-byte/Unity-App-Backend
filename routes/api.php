<?php

use App\Http\Controllers\Auth\OtpController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/request-otp', [OtpController::class, 'requestOtp'])
        ->middleware('throttle:5,1');
    Route::post('auth/verify-otp', [OtpController::class, 'verifyOtp']);
});
