<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Claim extends Model implements HasMedia, Auditable
{
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'vehicle_id', 'reservation_id', 'client_id', 'maintenance_id', 'created_by',
        'claim_date', 'claim_number', 'title', 'description', 'agent_notes',
        'accident_type', 'is_client_responsible', 'responsible_notes',
        'status',
        'total_damage_amount', 'insurance_amount_recovered', 'client_paid_amount', 'company_expense_amount',
        'insurance_reference', 'insurance_claim_date', 'settlement_date',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'claim_date'                  => 'date',
            'insurance_claim_date'        => 'date',
            'settlement_date'             => 'date',
            'is_client_responsible'       => 'boolean',
            'total_damage_amount'         => 'decimal:2',
            'insurance_amount_recovered'  => 'decimal:2',
            'client_paid_amount'          => 'decimal:2',
            'company_expense_amount'      => 'decimal:2',
        ];
    }

    // Computed
    public function getNetCompanyLossAttribute(): float
    {
        return max(0.0, (float) $this->total_damage_amount
            - (float) $this->insurance_amount_recovered
            - (float) $this->client_paid_amount);
    }

    // Scopes
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'under_review', 'insurance_claimed']);
    }

    public function scopeByVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    // Relations
    public function vehicle(): BelongsTo    { return $this->belongsTo(Vehicle::class); }
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function client(): BelongsTo     { return $this->belongsTo(Client::class); }
    public function maintenance(): BelongsTo { return $this->belongsTo(Maintenance::class); }
    public function creator(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('documents');
        $this->addMediaCollection('insurance_documents');
    }

    protected static function booted(): void
    {
        static::creating(function (Claim $claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = 'CLM-' . date('Y') . '-' . str_pad(
                    (static::withTrashed()->whereYear('created_at', date('Y'))->count() + 1),
                    4, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
