<?php

declare(strict_types=1);

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
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
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please select a valid target progress state.',
            'status.in' => 'Permitted technician statuses are In Progress, On Hold, or Resolved.',
            'notes.required' => 'Please provide technical notes explaining parts replaced, tests performed, or reasons for hold.',
            'attachments.*.max' => 'Each attached file must not exceed 10MB.',
            'attachments.*.mimes' => 'Attachments must be images (JPG, PNG, WEBP) or documents (PDF, DOC).',
        ];
    }
}

