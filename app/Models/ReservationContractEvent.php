<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationContractEvent extends Model
{
    use HasUuid;

    protected $fillable = [
        'reservation_id', 'event_type', 'actor_id', 'reason', 'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
