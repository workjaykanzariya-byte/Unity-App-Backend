<?php

use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Notifications\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/request-otp', [OtpController::class, 'requestOtp'])
        ->middleware('throttle:5,1');

    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead']);
});
