<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validated();

        $result = $otpService->requestOtp(
            $data['identifier'],
            $data['channel'],
            $data['purpose']
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
            'meta' => new \stdClass(),
            'errors' => null,
        ]);
    }
}
