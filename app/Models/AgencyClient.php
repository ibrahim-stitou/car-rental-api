<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AgencyClient extends Pivot
{
    use HasUuid;

    protected $table = 'agency_client';

    public $incrementing = false;

    protected $keyType = 'string';
}
