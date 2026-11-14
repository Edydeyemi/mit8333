<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\ServiceRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateServiceRequestApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var ServiceRequest|null $serviceRequest */
        $serviceRequest = $this->route('serviceRequest');

        return $serviceRequest !== null && ($this->user()?->can('update', $serviceRequest) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('service_categories', 'id')->where('is_active', true),
            ],
            'priority' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'emergency']),
            ],
            'building' => ['sometimes', 'required', 'string', 'max:150'],
            'wing_floor' => ['sometimes', 'required', 'string', 'max:100'],
            'room_number' => ['sometimes', 'required', 'string', 'max:100'],
            'title' => ['sometimes', 'required', 'string', 'min:5', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:10', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:10240', // 10MB per file
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
}
