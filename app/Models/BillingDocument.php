<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BillingDocument extends Model implements HasMedia, Auditable
{
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'document_number', 'type', 'status', 'agency_id', 'reservation_id', 'client_id',
        'client_name', 'client_address', 'client_phone', 'client_email',
        'issue_date', 'due_date', 'delivery_date',
        'subtotal', 'tax_rate', 'tax_amount', 'discount_percentage', 'discount_amount',
        'total_amount', 'paid_amount', 'balance',
        'payment_method', 'payment_reference', 'paid_at',
        'notes', 'terms_conditions', 'reference_document_number',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'issue_date'            => 'date',
            'due_date'              => 'date',
            'delivery_date'         => 'date',
            'subtotal'              => 'decimal:2',
            'tax_rate'              => 'decimal:2',
            'tax_amount'            => 'decimal:2',
            'discount_percentage'   => 'decimal:2',
            'discount_amount'       => 'decimal:2',
            'total_amount'          => 'decimal:2',
            'paid_amount'           => 'decimal:2',
            'balance'               => 'decimal:2',
            'paid_at'               => 'datetime',
            'approved_at'           => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BillingDocument $document) {
            if (empty($document->document_number)) {
                $document->document_number = $document->generateDocumentNumber();
            }
        });
    }

    // Generate document number based on type
    public function generateDocumentNumber(): string
    {
        $year = now()->year;
        $latest = static::where('type', $this->type)->whereYear('created_at', $year)->count() + 1;
        return $this->type . '-' . $year . '-' . str_pad($latest, 6, '0', STR_PAD_LEFT);
    }

    // Calculate totals
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total_price');
        $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        $subtotalAfterDiscount = $this->subtotal - $this->discount_amount;
        $this->tax_amount = $subtotalAfterDiscount * ($this->tax_rate / 100);
        $this->total_amount = $subtotalAfterDiscount + $this->tax_amount;
        $this->balance = $this->total_amount - $this->paid_amount;
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        $types = [
            'BC' => 'Bon de Commande',
            'BR' => 'Bon de Réception',
            'BL' => 'Bon de Livraison',
            'DV' => 'Devis',
            'FA' => 'Facture',
            'AV' => 'Avoir',
        ];
        return $types[$this->type] ?? $this->type;
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->balance <= 0 && $this->total_amount > 0;
    }

    // Relations
    public function agency(): BelongsTo { return $this->belongsTo(Agency::class); }
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(BillingDocumentItem::class); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document_pdf')->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
        $this->addMediaCollection('attachments');
    }
}

