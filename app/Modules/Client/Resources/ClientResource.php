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
            'agencies'                 => $this->whenLoaded('agencies', fn() => $this->agencies->map(fn($a) => [
                'id' => $a->id, 'name' => $a->name,
            ])),
            'first_name'               => $this->first_name,
            'last_name'                => $this->last_name,
            'full_name'                => $this->full_name,
            'email'                    => $this->email,
            'phone'                    => $this->phone,
            'date_of_birth'            => $this->date_of_birth?->toDateString(),
            'birth_place'              => $this->birth_place,
            'nationality'              => $this->nationality,
            'id_type'                  => $this->id_type,
            'id_number'                => $this->id_number,
            'id_expiry_date'           => $this->id_expiry_date?->toDateString(),
            'driving_license_number'   => $this->driving_license_number,
            'driving_license_category' => $this->driving_license_category,
            'driving_license_expiry'   => $this->driving_license_expiry?->toDateString(),
            'license_issue_date'       => $this->license_issue_date?->toDateString(),
            'license_issue_place'      => $this->license_issue_place,
            'is_license_valid'         => $this->is_license_valid,
            'address'                  => $this->address,
            'city'                     => $this->city,
            'country'                  => $this->country,
            'is_blacklisted'           => $this->is_blacklisted,
            'blacklist_reason'         => $this->blacklist_reason,
            'reservations_count'       => $this->whenCounted('reservations'),
            'notes'                    => $this->notes,

            // Pièce d'identité — recto / verso
            'id_document_recto'              => $this->getFirstMediaUrl('id_document_recto') ?: null,
            'id_document_recto_media_id'     => $this->getFirstMedia('id_document_recto')?->id,
            'id_document_verso'              => $this->getFirstMediaUrl('id_document_verso') ?: null,
            'id_document_verso_media_id'     => $this->getFirstMedia('id_document_verso')?->id,

            // Permis de conduire — recto / verso
            'driving_license_recto'          => $this->getFirstMediaUrl('driving_license_recto') ?: null,
            'driving_license_recto_media_id' => $this->getFirstMedia('driving_license_recto')?->id,
            'driving_license_verso'          => $this->getFirstMediaUrl('driving_license_verso') ?: null,
            'driving_license_verso_media_id' => $this->getFirstMedia('driving_license_verso')?->id,

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
