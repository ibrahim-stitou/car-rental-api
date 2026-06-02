<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Client extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'agency_id', 'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'nationality', 'id_type', 'id_number', 'id_expiry_date',
        'driving_license_number', 'driving_license_category', 'driving_license_expiry',
        'address', 'city', 'country', 'is_blacklisted', 'blacklist_reason',
        'notes', 'agent_notes', 'created_by',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'date_of_birth'          => 'date',
            'id_expiry_date'         => 'date',
            'driving_license_expiry' => 'date',
            'is_blacklisted'         => 'boolean',
        ];
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getIsLicenseValidAttribute(): bool
    {
        return $this->driving_license_expiry && $this->driving_license_expiry->isFuture();
    }

    // Scopes
    public function scopeBlacklisted(Builder $query): Builder
    {
        return $query->where('is_blacklisted', true);
    }

    // Relations
    public function agency(): BelongsTo { return $this->belongsTo(Agency::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reservations(): HasMany { return $this->hasMany(Reservation::class); }
    public function claims(): HasMany { return $this->hasMany(Claim::class); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('id_document')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
        $this->addMediaCollection('driving_license')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
        $this->addMediaCollection('selfie')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
        $this->addMediaCollection('other_documents');
    }
}
