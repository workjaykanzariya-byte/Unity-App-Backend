<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use stdClass;

class MarkAllNotificationsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.uuid' => 'The user id field must be a valid UUID.',
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
