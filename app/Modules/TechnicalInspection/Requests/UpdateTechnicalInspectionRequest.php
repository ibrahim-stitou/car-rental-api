<?php

namespace App\Modules\TechnicalInspection\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicalInspectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'           => 'sometimes|uuid|exists:vehicles,id',
            'inspection_date'      => 'sometimes|date',
            'expiry_date'          => 'sometimes|date|after:inspection_date',
            'result'               => 'sometimes|in:passed,failed,pending',
            'inspection_center'    => 'sometimes|string|max:255',
            'inspector_name'       => 'nullable|string|max:255',
            'observations'         => 'nullable|string',
            'cost'                 => 'nullable|numeric|min:0',
            'next_inspection_date' => 'nullable|date',
        ];
    }
}

