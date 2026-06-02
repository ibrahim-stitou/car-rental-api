<?php

namespace App\Modules\Claim\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClaimRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'                 => 'sometimes|uuid|exists:vehicles,id',
            'reservation_id'             => 'nullable|uuid|exists:reservations,id',
            'client_id'                  => 'nullable|uuid|exists:clients,id',
            'maintenance_id'             => 'nullable|uuid|exists:maintenances,id',
            'claim_date'                 => 'sometimes|date',
            'title'                      => 'sometimes|string|max:255',
            'description'                => 'nullable|string',
            'agent_notes'                => 'nullable|string',
            'accident_type'              => 'sometimes|in:collision,theft,vandalism,natural_disaster,fire,glass_damage,parking,other',
            'is_client_responsible'      => 'boolean',
            'responsible_notes'          => 'nullable|string',
            'status'                     => 'sometimes|in:open,under_review,insurance_claimed,settled,closed,rejected',
            'total_damage_amount'        => 'sometimes|numeric|min:0',
            'insurance_amount_recovered' => 'sometimes|numeric|min:0',
            'client_paid_amount'         => 'sometimes|numeric|min:0',
            'company_expense_amount'     => 'sometimes|numeric|min:0',
            'insurance_reference'        => 'nullable|string|max:255',
            'insurance_claim_date'       => 'nullable|date',
            'settlement_date'            => 'nullable|date',
        ];
    }
}
