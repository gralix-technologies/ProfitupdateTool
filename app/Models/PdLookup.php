<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class PdLookup extends Model
{
    use Auditable;

    protected $table = 'pd_lookup';
    
    protected $fillable = [
        'credit_rating',
        'pd_default'
    ];

    protected $casts = [
        'pd_default' => 'decimal:6'
    ];
}


