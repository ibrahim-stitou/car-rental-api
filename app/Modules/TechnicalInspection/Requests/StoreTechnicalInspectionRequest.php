<?php

namespace App\Modules\TechnicalInspection\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicalInspectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'           => 'required|uuid|exists:vehicles,id',
            'inspection_date'      => 'required|date',
            'expiry_date'          => 'required|date|after:inspection_date',
            'result'               => 'required|in:passed,failed,pending',
            'inspection_center'    => 'required|string|max:255',
            'inspector_name'       => 'nullable|string|max:255',
            'observations'         => 'nullable|string',
            'cost'                 => 'nullable|numeric|min:0',
            'next_inspection_date' => 'nullable|date|after:expiry_date',
        ];
    }
}

