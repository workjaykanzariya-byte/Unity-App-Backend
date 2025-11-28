<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use stdClass;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:email,sms'],
            'purpose' => ['required', 'in:login,signup,verify,forgot'],
            'code' => ['required', 'string', 'min:4', 'max:10'],
            'device_info' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            $identifier = trim((string) $this->input('identifier'));
            $channel = $this->input('channel');

            if ($channel === 'email') {
                $emailValidator = ValidatorFacade::make(
                    ['identifier' => $identifier],
                    ['identifier' => 'email:rfc,dns']
                );

                if ($emailValidator->fails()) {
                    $validator->errors()->add('identifier', 'The identifier field must be a valid email address.');
                }
            } elseif ($channel === 'sms') {
                if (!preg_match('/^\+?[0-9]{8,20}$/', $identifier)) {
                    $validator->errors()->add('identifier', 'The identifier field must be a valid phone number.');
                }
            } else {
                $validator->errors()->add('identifier', 'The identifier field must be a valid email or phone number.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'The identifier field is required.',
            'identifier.string' => 'The identifier field must be a string.',
            'identifier.max' => 'The identifier field may not be greater than 255 characters.',
            'channel.required' => 'The channel field is required.',
            'channel.in' => 'The channel field must be either email or sms.',
            'purpose.required' => 'The purpose field is required.',
            'purpose.in' => 'The purpose field must be one of login, signup, verify, or forgot.',
            'code.required' => 'The code field is required.',
            'code.string' => 'The code field must be a string.',
            'code.min' => 'The code field must be at least 4 characters.',
            'code.max' => 'The code field may not be greater than 10 characters.',
            'device_info.array' => 'The device info field must be an array.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = collect($validator->errors()->all())->map(fn ($message) => [
            'code' => 'VALIDATION_FAILED',
            'message' => $message,
        ])->values();

        $response = response()->json([
            'status' => 'error',
            'data' => null,
            'meta' => new stdClass(),
            'errors' => $errors,
        ], 422);

        throw new HttpResponseException($response);
    }
}
