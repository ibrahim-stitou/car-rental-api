<?php

namespace App\Modules\Claim\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'claim_number'               => $this->claim_number,
            'vehicle_id'                 => $this->vehicle_id,
            'reservation_id'             => $this->reservation_id,
            'client_id'                  => $this->client_id,
            'maintenance_id'             => $this->maintenance_id,
            'created_by'                 => $this->created_by,

            'claim_date'                 => $this->claim_date?->toDateString(),
            'title'                      => $this->title,
            'description'                => $this->description,
            'agent_notes'                => $this->agent_notes,
            'accident_type'              => $this->accident_type,
            'is_client_responsible'      => $this->is_client_responsible,
            'responsible_notes'          => $this->responsible_notes,
            'status'                     => $this->status,

            'total_damage_amount'        => $this->total_damage_amount,
            'insurance_amount_recovered' => $this->insurance_amount_recovered,
            'client_paid_amount'         => $this->client_paid_amount,
            'company_expense_amount'     => $this->company_expense_amount,
            'net_company_loss'           => $this->net_company_loss,

            'insurance_reference'        => $this->insurance_reference,
            'insurance_claim_date'       => $this->insurance_claim_date?->toDateString(),
            'settlement_date'            => $this->settlement_date?->toDateString(),

            'vehicle'     => $this->whenLoaded('vehicle', fn() => [
                'id'                  => $this->vehicle->id,
                'brand'               => $this->vehicle->brand,
                'model'               => $this->vehicle->model,
                'registration_number' => $this->vehicle->registration_number,
            ]),
            'client'      => $this->whenLoaded('client', fn() => [
                'id'        => $this->client->id,
                'full_name' => $this->client->full_name,
                'phone'     => $this->client->phone,
            ]),
            'reservation' => $this->whenLoaded('reservation', fn() => [
                'id'                 => $this->reservation->id,
                'reservation_number' => $this->reservation->reservation_number,
            ]),
            'maintenance' => $this->whenLoaded('maintenance', fn() => [
                'id'    => $this->maintenance->id,
                'title' => $this->maintenance->title ?? $this->maintenance->description,
                'type'  => $this->maintenance->type,
            ]),
            'creator'     => $this->whenLoaded('creator', fn() => [
                'id'        => $this->creator->id,
                'full_name' => $this->creator->first_name . ' ' . $this->creator->last_name,
            ]),

            'photos'              => $this->getMediaByCollection('photos'),
            'documents'           => $this->getMediaByCollection('documents'),
            'insurance_documents' => $this->getMediaByCollection('insurance_documents'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
