<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingDocumentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'description'    => $this->description,
            'quantity'       => $this->quantity,
            'unit'           => $this->unit,
            'unit_price'     => $this->unit_price,
            'unit_price_ttc' => round((float) $this->unit_price * (1 + (float) $this->tax_rate / 100), 2),
            'total_price'    => $this->total_price,
            'tax_rate'       => $this->tax_rate,
            'notes'          => $this->notes,
        ];
    }
}

