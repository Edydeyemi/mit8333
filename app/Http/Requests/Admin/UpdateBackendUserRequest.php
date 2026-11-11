<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateBackendUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetUser = $this->route('user');
        $userId = is_object($targetUser) ? $targetUser->id : $targetUser;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'mobile')->ignore($userId),
            ],
            'role' => ['required', 'string', 'in:super_admin,admin,officer,data_entry,user'],
            'status' => ['required', 'string', 'in:active,unverified,banned'],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }
}

