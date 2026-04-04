<?php

namespace App\Modules\Client\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agency_id'                => 'required|uuid|exists:agencies,id',
            'first_name'               => 'required|string|max:255',
            'last_name'                => 'required|string|max:255',
            'email'                    => 'required|email|unique:clients,email',
            'phone'                    => 'required|string|max:20',
            'date_of_birth'            => 'required|date|before:-18 years',
            'nationality'              => 'nullable|string|max:100',
            'id_type'                  => 'required|in:cin,passport,residence_permit',
            'id_number'                => 'required|string|max:50',
            'id_expiry_date'           => 'required|date|after:today',
            'driving_license_number'   => 'required|string|max:50',
            'driving_license_category' => 'nullable|string|max:10',
            'driving_license_expiry'   => 'required|date|after:today',
            'address'                  => 'nullable|string',
            'city'                     => 'nullable|string|max:100',
            'country'                  => 'nullable|string|max:100',
            'notes'                    => 'nullable|string',
        ];
    }
}

