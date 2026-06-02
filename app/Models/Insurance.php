<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\InsuranceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Insurance extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<InsuranceFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'vehicle_id', 'insurance_company', 'policy_number', 'type', 'insurance_type',
        'start_date', 'end_date', 'premium_amount', 'deductible_amount',
        'coverage_details', 'is_active', 'agent_name', 'agent_phone',
        'notes', 'agent_notes', 'created_by',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'start_date'        => 'date',
            'end_date'          => 'date',
            'premium_amount'    => 'decimal:2',
            'deductible_amount' => 'decimal:2',
            'coverage_details'  => 'array',
            'is_active'         => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('end_date', '>=', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    // Relations
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('policy_document')->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
        $this->addMediaCollection('green_card')->singleFile();
        $this->addMediaCollection('attachments');
        $this->addMediaCollection('documents');
    }
}
