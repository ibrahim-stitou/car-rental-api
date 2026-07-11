<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Traits\HasUuid;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ReservationPayment extends Model implements Auditable
{
    use HasUuid, AuditableTrait;

    protected $auditExclude = ['updated_at'];

    protected $fillable = [
        'reservation_id', 'recorded_by', 'amount', 'payment_method',
        'payment_date', 'reference', 'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}