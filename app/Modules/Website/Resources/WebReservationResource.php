<?php

namespace App\Modules\Website\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference'        => $this->reference,
            'vehicle'          => $this->whenLoaded('vehicle', fn() => [
                'id'        => $this->vehicle->id,
                'full_name' => $this->vehicle->full_name,
                'category'  => $this->vehicle->category,
                'photo'     => $this->vehicle->getFirstMediaUrl('photos', 'thumb'),
            ]),
            'full_name'        => $this->full_name,
            'first_name'       => $this->first_name,
            'last_name'        => $this->last_name,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'pickup_date'      => $this->pickup_date?->toISOString(),
            'return_date'      => $this->return_date?->toISOString(),
            'pickup_location'  => $this->pickup_location,
            'days'             => $this->days,
            'message'          => $this->message,
            'status'           => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'reservation_id'   => $this->reservation_id,
            'ip_address'       => $this->ip_address,
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
