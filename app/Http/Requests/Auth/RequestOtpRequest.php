<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RequestOtpRequest extends FormRequest
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
        ];
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
            'purpose.in' => 'The purpose field must be login, signup, verify, or forgot.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $identifier = trim((string) $this->input('identifier'));
            $channel = $this->input('channel');

            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
            $isPhone = preg_match('/^\+?[0-9]{8,20}$/', $identifier) === 1;

            if (! $isEmail && ! $isPhone) {
                $validator->errors()->add('identifier', 'The identifier field must be a valid email or phone number.');
                return;
            }

            if ($channel === 'email' && ! $isEmail) {
                $validator->errors()->add('identifier', 'The identifier field must be a valid email address.');
                return;
            }

            if ($channel === 'sms' && ! $isPhone) {
                $validator->errors()->add('identifier', 'The identifier field must be a valid phone number.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'status' => 'error',
            'data' => null,
            'meta' => new \stdClass(),
            'errors' => [
                [
                    'code' => 'VALIDATION_FAILED',
                    'message' => $validator->errors()->first() ?? 'Validation failed.',
                ],
            ],
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
