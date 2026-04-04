<?php

namespace App\Modules\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'           => 'required|uuid|exists:vehicles,id',
            'type'                 => 'required|in:oil_change,tire_change,brake_service,engine_repair,body_repair,electrical,cleaning,other',
            'description'          => 'required|string',
            'maintenance_date'     => 'required|date',
            'completion_date'      => 'nullable|date|after_or_equal:maintenance_date',
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

