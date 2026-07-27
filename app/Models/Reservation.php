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
        'pickup_location', 'return_location', 'actual_return_location', 'status',
        'contract_generated_at', 'is_favorable', 'closure_comment',
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
            'contract_generated_at' => 'datetime',
            'is_favorable'        => 'boolean',
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
        $prefix = "RES-{$year}-";

        // Include soft-deleted rows: the unique index still holds their number,
        // so a plain count() would collide once any reservation for the year is deleted.
        $latestNumber = static::withTrashed()
            ->where('reservation_number', 'like', $prefix . '%')
            ->orderByDesc('reservation_number')
            ->value('reservation_number');

        $next = $latestNumber ? ((int) substr($latestNumber, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Recomputes total_days/subtotal/discount_amount/total_amount. When the
     * actual return happened before the contracted return_date (early
     * return), billing is prorated to the actual days used rather than the
     * originally contracted duration — never extended for a late return,
     * which is handled separately via additional_fees.
     */
    public function calculateTotal(): void
    {
        $billableEndDate = $this->isEarlyReturn ? $this->actual_return_date : $this->return_date;

        $this->total_days = max(1, (int) $this->pickup_date->diffInDays($billableEndDate));
        $this->subtotal = $this->daily_rate * $this->total_days;
        $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->additional_fees;
    }

    /**
     * True once the vehicle came back earlier than the contracted return_date.
     * Purely derived (not persisted) — recompute at read time is cheap and
     * keeps it from ever drifting out of sync with the two source columns.
     */
    public function getIsEarlyReturnAttribute(): bool
    {
        return (bool) ($this->actual_return_date && $this->return_date
            && $this->actual_return_date->lt($this->return_date));
    }

    /**
     * Previews the billing impact of returning on $actualReturnDate without
     * mutating or persisting anything — used to suggest a prorated amount to
     * the agent before they confirm an early return.
     */
    public function previewEarlyReturn(\Carbon\Carbon $actualReturnDate): array
    {
        $isEarly = $actualReturnDate->lt($this->return_date);
        $billableEndDate = $isEarly ? $actualReturnDate : $this->return_date;

        $actualDays = max(1, (int) $this->pickup_date->diffInDays($billableEndDate));
        $contractedDays = max(1, (int) $this->pickup_date->diffInDays($this->return_date));

        $subtotal = $this->daily_rate * $actualDays;
        $discountAmount = $subtotal * ($this->discount_percentage / 100);
        $suggestedTotal = $subtotal - $discountAmount + ($this->additional_fees ?? 0);

        return [
            'is_early_return'         => $isEarly,
            'actual_days'             => $actualDays,
            'contracted_days'         => $contractedDays,
            'suggested_total_amount'  => round((float) $suggestedTotal, 2),
        ];
    }

    /**
     * Recompute payment_status (pending|partial|paid) from the sum of recorded
     * payments vs. the current total_amount. Single source of truth so it can't
     * drift from the payments table — called whenever a payment or the total changes.
     */
    public function syncPaymentStatus(): string
    {
        $totalPaid = $this->payments()->sum('amount');
        $balance   = $this->total_amount - $totalPaid;

        $status = match(true) {
            $balance <= 0  => 'paid',
            $totalPaid > 0 => 'partial',
            default        => 'pending',
        };

        $this->update(['payment_status' => $status]);

        return $status;
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
        // Archive of superseded contracts (e.g. before regeneration after an
        // extension) — kept for audit history, never singleFile.
        $this->addMediaCollection('contract_history')
            ->acceptsMimeTypes(['application/pdf']);
        $this->addMediaCollection('pickup_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('return_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('damage_reports');
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes([
                'application/pdf', 'image/jpeg', 'image/png', 'image/webp',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
