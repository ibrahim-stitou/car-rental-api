<?php

namespace App\Modules\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'           => 'sometimes|uuid|exists:vehicles,id',
            'type'                 => 'sometimes|in:oil_change,tire_change,brake_service,engine_repair,body_repair,electrical,cleaning,other',
            'description'          => 'sometimes|string',
            'maintenance_date'     => 'sometimes|date',
            'completion_date'      => 'nullable|date',
            'mileage_at_service'   => 'nullable|integer|min:0',
            'next_service_mileage' => 'nullable|integer|min:0',
            'next_service_date'    => 'nullable|date',
            'cost'                 => 'nullable|numeric|min:0',
            'service_provider'     => 'nullable|string|max:255',
            'status'               => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'priority'             => 'nullable|in:low,medium,high,urgent',
        ];
    }
}

