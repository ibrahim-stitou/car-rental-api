<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Expense extends Model implements HasMedia, Auditable
{
    use HasUuid, SoftDeletes, InteractsWithMedia, HasMediaCollections, AuditableTrait;

    protected $fillable = [
        'agency_id', 'vehicle_id', 'recorded_by', 'title', 'category',
        'amount', 'expense_date', 'payment_method', 'reference',
        'notes', 'agent_notes', 'description',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipts')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
        $this->addMediaCollection('documents');
    }
}
