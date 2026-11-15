<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateServiceRequestStatusApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('tasks.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(['in_progress', 'on_hold', 'resolved']),
            ],
            'notes' => [
                Rule::requiredIf(in_array($this->input('status'), ['on_hold', 'resolved'], true)),
                'nullable',
                'string',
                'max:2500',
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx',
                'max:10240', // 10MB
            ],
        ];
    }

    /**
     * Handle failed validation for API response format.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Handle failed authorization for API response format.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. You do not hold permissions to update technician task statuses.',
        ], 403));
    }
}

