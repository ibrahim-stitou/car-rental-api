<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agency_id'  => 'nullable|uuid|exists:agencies,id',
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $this->route('id'),
            'password'   => 'nullable|string|min:8|confirmed',
            'phone'      => 'nullable|string|max:20',
            'is_active'  => 'nullable|boolean',
        ];
    }
}

