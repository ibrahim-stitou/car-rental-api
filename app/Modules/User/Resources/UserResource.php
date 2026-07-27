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
            'agencies'    => $this->whenLoaded('agencies', fn() => $this->agencies->map(fn($a) => [
                'id' => $a->id, 'name' => $a->name,
                'stamp_url' => $a->pivot->stamp_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($a->pivot->stamp_path) : null,
                'signature_url' => $a->pivot->signature_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($a->pivot->signature_path) : null,
            ])),
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'full_name'   => $this->full_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'is_active'   => $this->is_active,
            'roles'       => $this->whenLoaded('roles', fn() => $this->getRoleNames()),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->getAllPermissions()->pluck('name')),
            'avatar'      => $this->getFirstMediaUrl('avatar'),
            'avatar_thumb'=> $this->getFirstMediaUrl('avatar', 'thumb'),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}

