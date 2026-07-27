<?php

namespace App\Modules\Client\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'agency_ids'               => 'required|array|min:1',
            'agency_ids.*'             => 'uuid|exists:agencies,id',
            'first_name'               => 'required|string|max:255',
            'last_name'                => 'required|string|max:255',
            'email'                    => 'nullable|email|unique:clients,email',
            'phone'                    => 'required|string|max:20',
            'date_of_birth'            => 'nullable|date|before:-18 years',
            'birth_place'              => 'nullable|string|max:255',
            'nationality'              => 'nullable|string|max:100',
            'id_type'                  => 'nullable|in:cin,passport,residence_permit',
            'id_number'                => 'nullable|string|max:50',
            'id_expiry_date'           => 'nullable|date',
            'driving_license_number'   => 'nullable|string|max:50',
            'driving_license_category' => 'nullable|string|max:10',
            'driving_license_expiry'   => 'nullable|date',
            'license_issue_date'       => 'nullable|date',
            'license_issue_place'      => 'nullable|string|max:255',
            'address'                  => 'nullable|string',
            'city'                     => 'nullable|string|max:100',
            'country'                  => 'nullable|string|max:100',
            'notes'                    => 'nullable|string',
        ];
    }
}

