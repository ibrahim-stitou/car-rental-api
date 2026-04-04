<?php

namespace App\Modules\Notification\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data;

        return [
            'id'           => $this->id,
            'type'         => $data['type'] ?? null,
            'label'        => $data['label'] ?? null,
            'severity'     => $data['severity'] ?? 'info',
            'icon'         => $data['icon'] ?? 'bell',
            'title'        => $data['title'] ?? '',
            'body'         => $data['body'] ?? '',
            'action_url'   => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'entity_type'  => $data['entity_type'] ?? null,
            'entity_id'    => $data['entity_id'] ?? null,
            'meta'         => $data['meta'] ?? [],
            'is_read'      => $this->read_at !== null,
            'read_at'      => $this->read_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
            'time_ago'     => $this->created_at?->diffForHumans(),
        ];
    }
}

