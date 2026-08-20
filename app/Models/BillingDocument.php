<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
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
        'client_name', 'client_address', 'client_phone', 'client_email', 'client_ice',
        'issue_date', 'due_date', 'delivery_date',
        'subtotal', 'tax_rate', 'tax_amount', 'discount_percentage', 'discount_amount',
        'total_amount', 'paid_amount', 'balance',
        'payment_method', 'payment_reference', 'paid_at',
        'notes', 'terms_conditions', 'reference_document_number',
        'created_by', 'approved_by', 'approved_at', 'unapprove_reason',
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
                // Temporary reference — real number generated on approval
                $document->document_number = 'BROUILLON-' . strtoupper(Str::random(6));
            }
        });
    }

    public function isTempReference(): bool
    {
        return str_starts_with($this->document_number ?? '', 'BROUILLON-');
    }

    // Generate document number using this document's agency's own counter —
    // each agency is a distinct legal entity with its own gapless sequence.
    // LLD documents are invoices too ("une facture") — they share FA's
    // counter entirely (same prefix, same running sequence) rather than
    // having their own separate series.
    public function generateDocumentNumber(): string
    {
        $counterType = strtolower($this->type) === 'lld' ? 'fa' : strtolower($this->type);
        return AgencyDocumentCounter::nextNumber($this->agency_id, $counterType);
    }

    // Calculate totals — TVA is per-line; no global discount
    public function calculateTotals(): void
    {
        $items = $this->items()->get(['total_price', 'tax_rate']);

        $this->subtotal        = $items->sum('total_price');
        $this->tax_amount      = $items->sum(fn($i) => $i->total_price * ($i->tax_rate / 100));
        $this->discount_amount = 0;
        $this->total_amount    = $this->subtotal + $this->tax_amount;
        $this->balance         = $this->total_amount - $this->paid_amount;
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

