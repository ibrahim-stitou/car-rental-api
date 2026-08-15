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
            'agency_id'           => $this->agency_id,
            'agency'              => $this->whenLoaded('agency', fn() => [
                'id'    => $this->agency->id,
                'name'  => $this->agency->name,
                'city'  => $this->agency->city,
                'phone' => $this->agency->phone,
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
            'hourly_rate'         => $this->hourly_rate,
            'monthly_rate'        => $this->monthly_rate,
            'deposit_amount'      => $this->deposit_amount,
            'mileage'             => $this->mileage,
            'average_consumption' => $this->average_consumption !== null ? (float) $this->average_consumption : null,
            'status'              => $this->status,
            'condition'           => $this->condition,
            'is_active'                => $this->is_active,
            'is_available'             => $this->is_available,
            'has_adblue'               => (bool) $this->has_adblue,
            'notes'                    => $this->notes,
            'description'              => $this->description,
            'show_on_website'          => $this->show_on_website,
            'website_description'      => $this->website_description,
            'website_price_override'   => $this->website_price_override,
            'website_price'            => $this->website_price_override ?? $this->daily_rate,
            'photos'                   => $this->getMediaByCollection('photos'),
            'registration_card'        => $this->getFirstMediaUrl('registration_card'),
            'documents_count'          => $this->getMedia('documents')->count(),
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}

