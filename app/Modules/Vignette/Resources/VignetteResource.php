<?php

namespace App\Modules\Vignette\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VignetteResource extends JsonResource
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
            'year'              => $this->year,
            'issue_date'        => $this->issue_date?->toDateString(),
            'expiry_date'       => $this->expiry_date?->toDateString(),
            'amount'            => $this->amount,
            'payment_method'    => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'is_paid'           => $this->is_paid,
            'paid_at'           => $this->paid_at?->toISOString(),
            'agent_notes'       => $this->agent_notes,
            'document'          => $this->getFirstMediaUrl('vignette_document'),
            'payment_proof'     => $this->getFirstMediaUrl('payment_proof'),
            'creator'           => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id, 'full_name' => $this->creator->full_name,
            ]),
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}

