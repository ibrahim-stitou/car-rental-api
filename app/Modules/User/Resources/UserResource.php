<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'agency'      => $this->whenLoaded('agency', fn() => [
                'id' => $this->agency->id, 'name' => $this->agency->name,
            ]),
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'is_active'   => $this->is_active,
            'roles'       => $this->whenLoaded('roles', fn() => $this->getRoleNames()),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->getAllPermissions()->pluck('name')),
            'agency_id'   => $this->agency_id,
            'avatar'      => $this->getFirstMediaUrl('avatar'),
            'avatar_thumb'=> $this->getFirstMediaUrl('avatar', 'thumb'),
            'signature'   => $this->getFirstMediaUrl('signature') ?: null,
            'stamp'       => $this->getFirstMediaUrl('stamp') ?: null,
            'has_signature' => $this->hasSignature(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}

