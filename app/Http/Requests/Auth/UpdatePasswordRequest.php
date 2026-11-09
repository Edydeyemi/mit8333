<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'code' => ['nullable', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please provide your current account password.',
            'password.required' => 'Please provide a new password.',
            'password.confirmed' => 'New password confirmation does not match.',
            'password.different' => 'The new password must be different from your current password.',
        ];
    }
}

