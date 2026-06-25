<?php

namespace App\Modules\Client\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'agency'                   => $this->whenLoaded('agency', fn() => [
                'id' => $this->agency->id, 'name' => $this->agency->name,
            ]),
            'first_name'               => $this->first_name,
            'last_name'                => $this->last_name,
            'full_name'                => $this->full_name,
            'email'                    => $this->email,
            'phone'                    => $this->phone,
            'date_of_birth'            => $this->date_of_birth?->toDateString(),
            'nationality'              => $this->nationality,
            'id_type'                  => $this->id_type,
            'id_number'                => $this->id_number,
            'id_expiry_date'           => $this->id_expiry_date?->toDateString(),
            'driving_license_number'   => $this->driving_license_number,
            'driving_license_category' => $this->driving_license_category,
            'driving_license_expiry'   => $this->driving_license_expiry?->toDateString(),
            'is_license_valid'         => $this->is_license_valid,
            'address'                  => $this->address,
            'city'                     => $this->city,
            'country'                  => $this->country,
            'is_blacklisted'           => $this->is_blacklisted,
            'blacklist_reason'         => $this->blacklist_reason,
            'notes'                    => $this->notes,
            'id_document'              => $this->getFirstMediaUrl('id_document') ?: null,
            'id_document_media_id'     => $this->getFirstMedia('id_document')?->id,
            'driving_license_doc'      => $this->getFirstMediaUrl('driving_license') ?: null,
            'driving_license_media_id' => $this->getFirstMedia('driving_license')?->id,
            'selfie'                   => $this->getFirstMediaUrl('selfie') ?: null,
            'selfie_media_id'          => $this->getFirstMedia('selfie')?->id,
            'creator'                  => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id, 'full_name' => $this->creator->full_name,
            ]),
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}

