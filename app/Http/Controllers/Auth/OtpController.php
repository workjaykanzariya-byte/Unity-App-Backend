<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\TooManyOtpRequestsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use stdClass;

class OtpController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, OtpService $otpService): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $otpService->requestOtp(
                $validated['identifier'],
                $validated['channel'],
                $validated['purpose']
            );

            return response()->json([
                'status' => 'success',
                'data'   => $result,
                'meta'   => new stdClass(),
                'errors' => null,
            ], 200);
        } catch (TooManyOtpRequestsException $e) {
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
    }

    public function verifyOtp(VerifyOtpRequest $request, OtpService $otpService): JsonResponse
    {
        $identifier = $request->input('identifier');
        $channel = $request->input('channel');
        $purpose = $request->input('purpose');
        $code = $request->input('code');
        $deviceInfo = $request->input('device_info', []);
        $ip = $request->ip();

        $result = $otpService->verifyOtp(
            $identifier,
            $channel,
            $purpose,
            $code,
            $deviceInfo,
            $ip
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
            'meta' => new stdClass(),
            'errors' => null,
        ], 200);
    }
}
