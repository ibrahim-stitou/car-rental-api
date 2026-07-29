<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebReservation extends Model
{
    use HasUuid;

    protected $fillable = [
        'reference', 'vehicle_id', 'first_name', 'last_name', 'email', 'phone',
        'pickup_date', 'return_date', 'pickup_location', 'message',
        'status', 'rejection_reason', 'reservation_id', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'datetime',
            'return_date' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo    { return $this->belongsTo(Vehicle::class); }
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getDaysAttribute(): int
    {
        return max(1, (int) ceil($this->pickup_date->diffInSeconds($this->return_date) / 86400));
    }

    public static function generateReference(): string
    {
        $year  = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('WEB-%s-%04d', $year, $count);
    }
}
