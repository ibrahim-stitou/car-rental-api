<?php

namespace App\Modules\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'              => 'sometimes|uuid|exists:vehicles,id',
            'title'                   => 'nullable|string|max:255',
            'type'                    => ['sometimes', Rule::exists('parameters', 'value')->where('category', 'maintenance_type')->where('is_active', true)],
            'sub_type'                => ['nullable', Rule::exists('parameters', 'value')->where('category', 'maintenance_sub_type')->where('is_active', true)],
            'description'             => 'sometimes|string',
            'agent_notes'             => 'nullable|string',
            'maintenance_date'        => 'sometimes|date',
            'completion_date'         => 'nullable|date',
            'mileage_at_service'      => 'nullable|integer|min:0',
            'next_service_mileage'    => 'nullable|integer|min:0',
            'next_oil_change_mileage' => 'nullable|integer|min:0',
            'tire_position'           => 'nullable|in:front_left,front_right,rear_left,rear_right,front,rear,all,spare',
            'next_service_date'       => 'nullable|date',
            'cost'                    => 'nullable|numeric|min:0',
            'actual_cost'             => 'nullable|numeric|min:0',
            'service_provider'        => 'nullable|string|max:255',
            'status'                  => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'priority'                => 'nullable|in:low,medium,high,urgent',
        ];
    }
}

