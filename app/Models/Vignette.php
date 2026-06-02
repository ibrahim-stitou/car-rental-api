<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\VignetteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Vignette extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<VignetteFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'vehicle_id', 'year', 'issue_date', 'expiry_date', 'amount',
        'payment_method', 'payment_reference', 'is_paid', 'paid_at',
        'agent_notes', 'created_by',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'issue_date'  => 'date',
            'expiry_date' => 'date',
            'amount'      => 'decimal:2',
            'is_paid'     => 'boolean',
            'paid_at'     => 'datetime',
            'year'        => 'integer',
        ];
    }

    // Scopes
    public function scopeCurrentYear(Builder $query): Builder
    {
        return $query->where('year', now()->year);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('is_paid', false);
    }

    // Relations
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('vignette_document')->singleFile();
        $this->addMediaCollection('payment_proof')->singleFile();
        $this->addMediaCollection('documents');
    }
}
