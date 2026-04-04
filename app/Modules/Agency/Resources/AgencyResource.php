<?php

namespace App\Modules\Agency\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'address'    => $this->address,
            'city'       => $this->city,
            'country'    => $this->country,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'is_active'  => $this->is_active,
            'manager'    => $this->whenLoaded('manager', fn() => [
                'id'        => $this->manager->id,
                'full_name' => $this->manager->full_name,
                'email'     => $this->manager->email,
            ]),
            'logo'            => $this->getFirstMediaUrl('logo'),
            'documents_count' => $this->getMedia('documents')->count(),
            'vehicles_count'  => $this->whenCounted('vehicles'),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}

