<?php

namespace App\Modules\Insurance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'vehicle'           => $this->whenLoaded('vehicle', fn() => [
                'id'        => $this->vehicle->id,
                'full_name' => $this->vehicle->full_name,
                'registration_number' => $this->vehicle->registration_number,
            ]),
            'insurance_company' => $this->insurance_company,
            'policy_number'     => $this->policy_number,
            'type'              => $this->type,
            'start_date'        => $this->start_date?->toDateString(),
            'end_date'          => $this->end_date?->toDateString(),
            'premium_amount'    => $this->premium_amount,
            'deductible_amount' => $this->deductible_amount,
            'coverage_details'  => $this->coverage_details,
            'is_active'         => $this->is_active,
            'agent_name'        => $this->agent_name,
            'agent_phone'       => $this->agent_phone,
            'notes'             => $this->notes,
            'policy_document'   => $this->getFirstMediaUrl('policy_document'),
            'green_card'        => $this->getFirstMediaUrl('green_card'),
            'attachments'       => $this->getMediaByCollection('attachments'),
            'creator'           => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id, 'full_name' => $this->creator->full_name,
            ]),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}

