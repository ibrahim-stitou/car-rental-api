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
        'pickup_date', 'return_date', 'actual_return_date', 'rental_unit',
        'pickup_location', 'return_location', 'actual_return_location', 'status',
        'contract_generated_at', 'contract_status', 'is_favorable', 'closure_comment',
        'daily_rate', 'hourly_rate', 'monthly_rate', 'total_days', 'total_hours', 'total_months', 'subtotal',
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
            'hourly_rate'         => 'decimal:2',
            'monthly_rate'        => 'decimal:2',
            'total_days'          => 'integer',
            'total_hours'         => 'integer',
            'total_months'        => 'integer',
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
    // Format "YY-XXXX" (ex: 26-0001), repris de l'ancien système. Les
    // réservations migrées utilisent un format distinct ('ARCHIVE-<ancien_id>',
    // voir migrate_legacy_data.sql) pour rester clairement identifiables comme
    // de l'historique archivé — elles n'alimentent donc pas ce compteur.
    public function generateReservationNumber(): string
    {
        $year = now()->format('y');
        $prefix = "{$year}-";

        // Include soft-deleted rows: the unique index still holds their number,
        // so a plain count() would collide once any reservation for the year is deleted.
        $latestNumber = static::withTrashed()
            ->where('reservation_number', 'like', $prefix . '%')
            ->orderByDesc('reservation_number')
            ->value('reservation_number');

        $next = $latestNumber ? ((int) substr($latestNumber, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Recomputes total_days|total_hours|total_months/subtotal/discount_amount/
     * total_amount. When the actual return happened before the contracted
     * return_date (early return/early termination), billing is prorated to
     * the actual duration used rather than the originally contracted one —
     * never extended for a late return, which is handled separately via
     * additional_fees.
     *
     * Hourly reservations (rental_unit = 'hour') bill hourly_rate x whole
     * hours elapsed (ceil), never converting to daily billing even once the
     * duration crosses 24h — the price basis agreed at booking doesn't
     * silently change. LLD reservations (rental_unit = 'month') bill
     * monthly_rate x whole calendar months elapsed (ceil, calendar-aware via
     * Carbon rather than a fixed seconds-per-month approximation, since
     * months vary in length). total_days/total_hours/total_months are still
     * populated for units they don't apply to (rounded from whichever unit
     * is authoritative) purely so the NOT NULL columns stay meaningful for
     * any code still reading them; only the matching unit's field drives the total.
     */
    public function calculateTotal(): void
    {
        $billableEndDate = $this->isEarlyReturn ? $this->actual_return_date : $this->return_date;

        if ($this->rental_unit === 'hour') {
            $this->total_hours = max(1, (int) ceil($this->pickup_date->diffInSeconds($billableEndDate) / 3600));
            $this->total_days = max(1, (int) ceil($this->total_hours / 24));
            $this->total_months = null;
            $this->subtotal = $this->hourly_rate * $this->total_hours;
        } elseif ($this->rental_unit === 'month') {
            $this->total_months = max(1, $this->monthsBetweenCeil($this->pickup_date, $billableEndDate));
            $this->total_days = max(1, (int) ceil($this->pickup_date->diffInSeconds($billableEndDate) / 86400));
            $this->total_hours = null;
            $this->subtotal = $this->monthly_rate * $this->total_months;
        } else {
            $this->total_days = max(1, (int) ceil($this->pickup_date->diffInSeconds($billableEndDate) / 86400));
            $this->total_hours = null;
            $this->total_months = null;
            $this->subtotal = $this->daily_rate * $this->total_days;
        }

        $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->additional_fees;
    }

    /**
     * Whole calendar months between two dates, rounded UP if there's any
     * partial month remaining (mirrors the day/hour "any period started is
     * due in full" rule) — Carbon's own diffInMonths() truncates, which
     * would silently undercharge a contract that ends mid-month.
     */
    private function monthsBetweenCeil(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $whole = (int) $start->diffInMonths($end);
        return $start->copy()->addMonths($whole)->lt($end) ? $whole + 1 : $whole;
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
     * the agent before they confirm an early return. Prorates per hour for
     * hourly reservations, per day otherwise; `unit` tells the frontend which
     * label to render ("actual_days"/"contracted_days" keys are kept
     * unit-agnostic for backward compatibility with existing callers).
     */
    public function previewEarlyReturn(\Carbon\Carbon $actualReturnDate): array
    {
        $isEarly = $actualReturnDate->lt($this->return_date);
        $billableEndDate = $isEarly ? $actualReturnDate : $this->return_date;

        if ($this->rental_unit === 'month') {
            $rate = $this->monthly_rate;
            $actualUnits = max(1, $this->monthsBetweenCeil($this->pickup_date, $billableEndDate));
            $contractedUnits = max(1, $this->monthsBetweenCeil($this->pickup_date, $this->return_date));
        } else {
            $isHourly = $this->rental_unit === 'hour';
            $divisor = $isHourly ? 3600 : 86400;
            $rate = $isHourly ? $this->hourly_rate : $this->daily_rate;
            $actualUnits = max(1, (int) ceil($this->pickup_date->diffInSeconds($billableEndDate) / $divisor));
            $contractedUnits = max(1, (int) ceil($this->pickup_date->diffInSeconds($this->return_date) / $divisor));
        }

        $subtotal = $rate * $actualUnits;
        $discountAmount = $subtotal * ($this->discount_percentage / 100);
        $suggestedTotal = $subtotal - $discountAmount + ($this->additional_fees ?? 0);

        return [
            'is_early_return'         => $isEarly,
            'unit'                    => $this->rental_unit,
            'actual_days'             => $actualUnits,
            'contracted_days'         => $contractedUnits,
            'suggested_total_amount'  => round((float) $suggestedTotal, 2),
        ];
    }

    /**
     * Whole calendar months elapsed since pickup, capped at the contracted
     * duration — purely time-based (for the "durée" progress bar), NOT the
     * amount currently billable (see getMonthsDueAttribute below, which is
     * this value + 1, matching the "first month due immediately" rule).
     * 0 for non-LLD reservations.
     */
    public function getMonthsElapsedAttribute(): int
    {
        if ($this->rental_unit !== 'month' || !$this->pickup_date || !$this->total_months) {
            return 0;
        }
        return min($this->total_months, max(0, (int) $this->pickup_date->diffInMonths(now())));
    }

    /**
     * How many monthly installments are due as of today: the first month is
     * due at signing (day 0), each subsequent whole month elapsed adds one
     * more — e.g. a 24-month contract at 4000 MAD/month owes 4000 MAD on day
     * 0, 8000 MAD once 1 full month has passed, etc. Capped at total_months.
     * 0 for non-LLD reservations.
     */
    public function getMonthsDueAttribute(): int
    {
        if ($this->rental_unit !== 'month' || !$this->total_months) {
            return 0;
        }
        return min($this->total_months, $this->months_elapsed + 1);
    }

    /**
     * The LLD "credit" basis: what's actually owed so far, NOT the full
     * contract value — this is what makes a 24-month, 96 000 MAD contract
     * show a 4 000 MAD credit at signing rather than the full amount.
     * Falls back to total_amount for non-LLD reservations (no proration).
     */
    public function getAmountDueSoFarAttribute(): float
    {
        if ($this->rental_unit !== 'month') {
            return (float) $this->total_amount;
        }
        return (float) $this->monthly_rate * $this->months_due;
    }

    /**
     * Recompute payment_status (pending|partial|paid) from the sum of recorded
     * payments vs. the current total_amount. Single source of truth so it can't
     * drift from the payments table — called whenever a payment or the total changes.
     */
    public function syncPaymentStatus(): string
    {
        $totalPaid = $this->payments()->sum('amount');
        // For LLD, "paid" means caught up through the current due month, not
        // the entire multi-year contract settled — otherwise this would sit
        // on "partiel" for the contract's whole lifetime even when the
        // client pays every month on time.
        $balance   = $this->amount_due_so_far - $totalPaid;

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
    public function contractEvents(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(ReservationContractEvent::class)->latest(); }
    public function billingDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(BillingDocument::class); }
    public function lldBillingDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->billingDocuments()->where('type', 'LLD'); }

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
