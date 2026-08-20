<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterTypeSetting extends Model
{
    protected $primaryKey = 'document_type';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['document_type', 'shared', 'prefix', 'separator', 'digits', 'current'];

    protected function casts(): array
    {
        return [
            'shared'  => 'boolean',
            'digits'  => 'integer',
            'current' => 'integer',
        ];
    }
}
