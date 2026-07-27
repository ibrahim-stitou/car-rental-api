<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AgencyUser extends Pivot
{
    use HasUuid;

    protected $table = 'agency_user';

    public $incrementing = false;

    protected $keyType = 'string';
}
