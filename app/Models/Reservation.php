<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Reservation extends Model implements HasMedia, Auditable
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory, HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'reservation_number', 'agency_id', 'vehicle_id', 'client_id', 'created_by', 'validated_by',
        'second_driver_id', 'second_driver_name', 'second_driver_license', 'second_driver_phone',
        'pickup_date', 'return_date', 'actual_return_date',
        'pickup_location', 'return_location', 'status',
        'daily_rate', 'total_days', 'subtotal',
        'discount_percentage', 'discount_amount', 'additional_fees', 'total_amount',
        'deposit_amount', 'deposit_paid', 'deposit_paid_at',
        'payment_status', 'payment_method',
        'initial_mileage', 'final_mileage',
        'fuel_level_pickup', 'fuel_level_return',
        'notes', 'agent_notes', 'cancellation_reason', 'cancelled_at',
    ];

    protected $auditExclude = ['updated_at'];

    protected function casts(): array
    {
        return [
            'pickup_date'         => 'datetime',
            'return_date'         => 'datetime',
            'actual_return_date'  => 'datetime',
            'daily_rate'          => 'decimal:2',
            'total_days'          => 'integer',
            'subtotal'            => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount'     => 'decimal:2',
            'additional_fees'     => 'decimal:2',
            'total_amount'        => 'decimal:2',
            'deposit_amount'      => 'decimal:2',
            'deposit_paid'        => 'boolean',
            'deposit_paid_at'     => 'datetime',
            'initial_mileage'     => 'integer',
            'final_mileage'       => 'integer',
            'cancelled_at'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            if (empty($reservation->reservation_number)) {
                $reservation->reservation_number = $reservation->generateReservationNumber();
            }
        });
    }

    // Methods
    public function generateReservationNumber(): string
    {
        $year = now()->year;
        $latest = static::whereYear('created_at', $year)->count() + 1;
        return 'RES-' . $year . '-' . str_pad($latest, 6, '0', STR_PAD_LEFT);
    }

    public function calculateTotal(): void
    {
        $this->total_days = max(1, (int) $this->pickup_date->diffInDays($this->return_date));
        $this->subtotal = $this->daily_rate * $this->total_days;
        $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->additional_fees;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->return_date->isPast();
    }

    // Scopes
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('return_date', '<', now());
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // Relations
    public function agency(): BelongsTo { return $this->belongsTo(Agency::class); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function secondDriver(): BelongsTo { return $this->belongsTo(Client::class, 'second_driver_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function validator(): BelongsTo { return $this->belongsTo(User::class, 'validated_by'); }
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(ReservationPayment::class); }

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('contract')->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
        $this->addMediaCollection('pickup_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('return_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('damage_reports');
    }
}
