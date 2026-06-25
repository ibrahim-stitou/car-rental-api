<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingDocumentItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'billing_document_id', 'description', 'quantity', 'unit_price', 'total_price', 'tax_rate', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'integer',
            'unit_price'  => 'decimal:2',
            'total_price' => 'decimal:2',
            'tax_rate'    => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BillingDocumentItem $item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }

    // Relations
    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }
}
