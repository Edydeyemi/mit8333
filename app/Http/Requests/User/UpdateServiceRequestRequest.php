<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequestRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('service_categories', 'id')->where('is_active', true),
            ],
            'priority' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'emergency']),
            ],
            'building' => [
                'required',
                'string',
                'max:150',
            ],
            'wing_floor' => [
                'required',
                'string',
                'max:100',
            ],
            'room_number' => [
                'required',
                'string',
                'max:100',
            ],
            'title' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:5',
            ],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:10240', // 10MB per file
            ],
        ];
    }

    /**
     * Custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_category_id' => 'maintenance category',
            'priority' => 'urgency level',
            'building' => 'building complex',
            'wing_floor' => 'wing / floor',
            'room_number' => 'room or lab number',
            'title' => 'complaint title',
            'description' => 'complaint description',
            'attachments' => 'additional fault photos',
            'attachments.*' => 'evidence photo',
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_category_id.exists' => 'The selected maintenance category is currently disabled or invalid.',
            'attachments.max' => 'You cannot attach more than 5 additional photos.',
            'attachments.*.mimes' => 'Each evidence photo must be a JPEG, PNG, or WebP image.',
            'attachments.*.max' => 'Each evidence photo must not exceed 10 megabytes.',
        ];
    }
}
