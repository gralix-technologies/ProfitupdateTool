<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class LgdLookup extends Model
{
    use Auditable;

    protected $table = 'lgd_lookup';
    
    protected $fillable = [
        'collateral_type',
        'lgd_default'
    ];

    protected $casts = [
        'lgd_default' => 'decimal:6'
    ];
}


