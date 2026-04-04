<?php

namespace App\Modules\TechnicalInspection\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'vehicle'              => $this->whenLoaded('vehicle', fn() => [
                'id'        => $this->vehicle->id,
                'full_name' => $this->vehicle->full_name,
                'registration_number' => $this->vehicle->registration_number,
            ]),
            'inspection_date'      => $this->inspection_date?->toDateString(),
            'expiry_date'          => $this->expiry_date?->toDateString(),
            'result'               => $this->result,
            'inspection_center'    => $this->inspection_center,
            'inspector_name'       => $this->inspector_name,
            'observations'         => $this->observations,
            'cost'                 => $this->cost,
            'next_inspection_date' => $this->next_inspection_date?->toDateString(),
            'report'               => $this->getFirstMediaUrl('inspection_report'),
            'photos'               => $this->getMediaByCollection('photos'),
            'creator'              => $this->whenLoaded('creator', fn() => [
                'id'        => $this->creator->id,
                'full_name' => $this->creator->full_name,
            ]),
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}

