<?php

namespace App\Modules\Maintenance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'vehicle'              => $this->whenLoaded('vehicle', fn() => [
                'id' => $this->vehicle->id, 'full_name' => $this->vehicle->full_name,
                'registration_number' => $this->vehicle->registration_number,
                'mileage' => $this->vehicle->mileage,
            ]),
            'title'                => $this->title,
            'type'                 => $this->type,
            'sub_type'             => $this->sub_type,
            'description'          => $this->description,
            'agent_notes'          => $this->agent_notes,
            'maintenance_date'     => $this->maintenance_date?->toDateString(),
            'completion_date'      => $this->completion_date?->toDateString(),
            'mileage_at_service'   => $this->mileage_at_service,
            'next_service_mileage' => $this->next_service_mileage,
            'next_oil_change_mileage' => $this->next_oil_change_mileage,
            'tire_position'        => $this->tire_position,
            'next_service_date'    => $this->next_service_date?->toDateString(),
            'cost'                 => $this->cost,
            'actual_cost'          => $this->actual_cost,
            'service_provider'     => $this->service_provider,
            'status'               => $this->status,
            'priority'             => $this->priority,
            'documents'            => $this->getMediaByCollection('documents'),
            'invoices'             => $this->getMediaByCollection('invoices'),
            'photos_before'        => $this->getMediaByCollection('photos_before'),
            'photos_after'         => $this->getMediaByCollection('photos_after'),
            'creator'              => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id, 'full_name' => $this->creator->full_name,
            ]),
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}

