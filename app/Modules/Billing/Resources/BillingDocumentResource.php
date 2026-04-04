<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'document_number'        => $this->document_number,
            'type'                   => $this->type,
            'type_name'              => $this->type_name,
            'status'                 => $this->status,
            'agency'                 => $this->whenLoaded('agency', fn() => [
                'id'   => $this->agency->id,
                'name' => $this->agency->name,
            ]),
            'reservation'            => $this->whenLoaded('reservation', fn() => [
                'id'                 => $this->reservation->id,
                'reservation_number' => $this->reservation->reservation_number,
            ]),
            'client'                 => $this->whenLoaded('client', fn() => [
                'id'        => $this->client->id,
                'full_name' => $this->client->full_name,
            ]),
            'client_name'            => $this->client_name,
            'client_address'         => $this->client_address,
            'client_phone'           => $this->client_phone,
            'client_email'           => $this->client_email,
            'issue_date'             => $this->issue_date?->format('Y-m-d'),
            'due_date'               => $this->due_date?->format('Y-m-d'),
            'delivery_date'          => $this->delivery_date?->format('Y-m-d'),
            'subtotal'               => $this->subtotal,
            'tax_rate'               => $this->tax_rate,
            'tax_amount'             => $this->tax_amount,
            'discount_percentage'    => $this->discount_percentage,
            'discount_amount'        => $this->discount_amount,
            'total_amount'           => $this->total_amount,
            'paid_amount'            => $this->paid_amount,
            'balance'                => $this->balance,
            'is_paid'                => $this->is_paid,
            'payment_method'         => $this->payment_method,
            'payment_reference'      => $this->payment_reference,
            'paid_at'                => $this->paid_at?->toISOString(),
            'notes'                  => $this->notes,
            'terms_conditions'       => $this->terms_conditions,
            'reference_document_number' => $this->reference_document_number,
            'creator'                => $this->whenLoaded('creator', fn() => [
                'id'        => $this->creator->id,
                'full_name' => $this->creator->full_name,
            ]),
            'approver'               => $this->whenLoaded('approver', fn() => [
                'id'        => $this->approver->id,
                'full_name' => $this->approver->full_name,
            ]),
            'approved_at'            => $this->approved_at?->toISOString(),
            'items'                  => BillingDocumentItemResource::collection($this->whenLoaded('items')),
            'document_pdf'           => $this->getFirstMediaUrl('document_pdf'),
            'attachments_count'      => $this->getMedia('attachments')->count(),
            'created_at'             => $this->created_at?->toISOString(),
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}

