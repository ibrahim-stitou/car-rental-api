<?php

namespace App\Modules\Vehicle\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'agency'              => $this->whenLoaded('agency', fn() => [
                'id'   => $this->agency->id,
                'name' => $this->agency->name,
            ]),
            'brand'               => $this->brand,
            'model'               => $this->model,
            'year'                => $this->year,
            'full_name'           => $this->full_name,
            'registration_number' => $this->registration_number,
            'vin'                 => $this->vin,
            'color'               => $this->color,
            'category'            => $this->category,
            'fuel_type'           => $this->fuel_type,
            'transmission'        => $this->transmission,
            'seats'               => $this->seats,
            'daily_rate'          => $this->daily_rate,
            'deposit_amount'      => $this->deposit_amount,
            'mileage'             => $this->mileage,
            'status'              => $this->status,
            'is_active'           => $this->is_active,
            'is_available'        => $this->is_available,
            'notes'               => $this->notes,
            'photos'              => $this->getMediaByCollection('photos'),
            'registration_card'   => $this->getFirstMediaUrl('registration_card'),
            'documents_count'     => $this->getMedia('documents')->count(),
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}

