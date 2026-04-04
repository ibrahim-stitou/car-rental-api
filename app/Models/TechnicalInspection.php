<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\TechnicalInspectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TechnicalInspection extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<TechnicalInspectionFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'vehicle_id', 'inspection_date', 'expiry_date', 'result',
        'inspection_center', 'inspector_name', 'observations',
        'cost', 'next_inspection_date', 'created_by',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'inspection_date'      => 'date',
            'expiry_date'          => 'date',
            'next_inspection_date' => 'date',
            'cost'                 => 'decimal:2',
        ];
    }

    // Scopes
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeByVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    // Relations
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('inspection_report')->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
