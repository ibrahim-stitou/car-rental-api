<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\MaintenanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Maintenance extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<MaintenanceFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'vehicle_id', 'title', 'type', 'sub_type', 'description', 'agent_notes',
        'maintenance_date', 'completion_date',
        'mileage_at_service', 'next_service_mileage', 'next_service_date',
        'next_oil_change_mileage', 'tire_position',
        'cost', 'actual_cost', 'service_provider', 'status', 'priority', 'created_by',
        'expense_id',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'maintenance_date'       => 'date',
            'completion_date'        => 'date',
            'next_service_date'      => 'date',
            'cost'                   => 'decimal:2',
            'actual_cost'            => 'decimal:2',
            'mileage_at_service'     => 'integer',
            'next_service_mileage'   => 'integer',
            'next_oil_change_mileage'=> 'integer',
        ];
    }

    // Scopes
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('maintenance_date', '<', now());
    }

    public function scopeByVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    // Relations
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function claim(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Claim::class);
    }
    public function expense(): BelongsTo { return $this->belongsTo(Expense::class); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invoices')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
        $this->addMediaCollection('photos_before')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('photos_after')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('documents');
    }
}
