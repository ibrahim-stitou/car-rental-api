<?php

namespace App\Modules\Insurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInsuranceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'        => 'sometimes|uuid|exists:vehicles,id',
            'insurance_company' => 'sometimes|string|max:255',
            'policy_number'     => 'sometimes|string|unique:insurances,policy_number,' . $this->route('id'),
            'type'              => ['sometimes', Rule::exists('parameters', 'value')->where('category', 'insurance_type')->where('is_active', true)],
            'start_date'        => 'sometimes|date',
            'end_date'          => 'sometimes|date|after:start_date',
            'premium_amount'    => 'sometimes|numeric|min:0',
            'deductible_amount' => 'nullable|numeric|min:0',
            'coverage_details'  => 'nullable|array',
            'is_active'         => 'nullable|boolean',
            'agent_name'        => 'nullable|string|max:255',
            'agent_phone'       => 'nullable|string|max:20',
            'notes'             => 'nullable|string',
        ];
    }
}

