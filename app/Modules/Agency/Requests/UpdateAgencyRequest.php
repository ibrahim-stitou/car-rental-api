<?php

namespace App\Modules\Agency\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgencyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => 'sometimes|string|max:255',
            'address'    => 'nullable|string',
            'city'       => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:100',
            'phone'      => 'nullable|string|max:20',
            'phone2'     => 'nullable|string|max:20',
            'email'      => 'sometimes|email|unique:agencies,email,' . $this->route('id'),
            'is_active'  => 'nullable|boolean',
            'manager_id' => 'nullable|uuid|exists:users,id',
            'legal_form' => 'nullable|string|max:255',
            'capital'    => 'nullable|string|max:255',
            'rc'         => 'nullable|string|max:255',
            'tax_id'     => 'nullable|string|max:255',
            'patente'    => 'nullable|string|max:255',
            'ice'        => 'nullable|string|max:255',
        ];
    }
}

