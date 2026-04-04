<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Vehicle extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'agency_id', 'brand', 'model', 'year', 'registration_number',
        'vin', 'color', 'category', 'fuel_type', 'transmission',
        'seats', 'daily_rate', 'deposit_amount', 'mileage',
        'status', 'is_active', 'notes',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'year'           => 'integer',
            'seats'          => 'integer',
            'daily_rate'     => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'mileage'        => 'integer',
            'is_active'      => 'boolean',
        ];
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model} {$this->year}";
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'available';
    }

    // Relations
    public function agency(): BelongsTo { return $this->belongsTo(Agency::class); }
    public function technicalInspections(): HasMany { return $this->hasMany(TechnicalInspection::class); }
    public function vignettes(): HasMany { return $this->hasMany(Vignette::class); }
    public function insurances(): HasMany { return $this->hasMany(Insurance::class); }
    public function reservations(): HasMany { return $this->hasMany(Reservation::class); }
    public function maintenances(): HasMany { return $this->hasMany(Maintenance::class); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('registration_card')->singleFile();
        $this->addMediaCollection('documents');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)->height(150)->sharpen(5)->nonQueued();
        $this->addMediaConversion('medium')
            ->width(800)->height(600)->nonQueued();
    }
}
