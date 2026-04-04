<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Agency extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'name', 'address', 'city', 'country', 'phone',
        'email', 'is_active', 'manager_id',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relations
    public function manager(): BelongsTo { return $this->belongsTo(User::class, 'manager_id'); }
    public function vehicles(): HasMany { return $this->hasMany(Vehicle::class); }
    public function clients(): HasMany { return $this->hasMany(Client::class); }
    public function reservations(): HasMany { return $this->hasMany(Reservation::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('documents');
    }
}
