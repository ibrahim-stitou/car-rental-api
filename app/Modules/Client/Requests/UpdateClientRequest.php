<?php

namespace App\Modules\Client\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->filled('id_type')) {
            $map = [
                'CIN'              => 'cin',
                'Passport'         => 'passport',
                'Residence Permit' => 'residence_permit',
            ];
            $normalized = $map[$this->id_type] ?? strtolower(str_replace(' ', '_', $this->id_type));
            $this->merge(['id_type' => $normalized]);
        }
    }

    public function rules(): array
    {
        return [
            'agency_id'                => 'sometimes|uuid|exists:agencies,id',
            'first_name'               => 'sometimes|string|max:255',
            'last_name'                => 'sometimes|string|max:255',
            'email'                    => 'sometimes|email|unique:clients,email,' . $this->route('id'),
            'phone'                    => 'sometimes|string|max:20',
            'date_of_birth'            => 'sometimes|date|before:-18 years',
            'birth_place'              => 'nullable|string|max:255',
            'nationality'              => 'nullable|string|max:100',
            'id_type'                  => 'sometimes|in:cin,passport,residence_permit',
            'id_number'                => 'sometimes|string|max:50',
            'id_expiry_date'           => 'sometimes|date',
            'driving_license_number'   => 'sometimes|string|max:50',
            'driving_license_category' => 'nullable|string|max:10',
            'driving_license_expiry'   => 'sometimes|date',
            'license_issue_date'       => 'nullable|date',
            'license_issue_place'      => 'nullable|string|max:255',
            'address'                  => 'nullable|string',
            'city'                     => 'nullable|string|max:100',
            'country'                  => 'nullable|string|max:100',
            'notes'                    => 'nullable|string',
        ];
    }
}

