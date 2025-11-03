<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class RiskWeight extends Model
{
    use Auditable;

    protected $fillable = [
        'credit_rating',
        'collateral_type',
        'risk_weight_percent'
    ];

    protected $casts = [
        'risk_weight_percent' => 'decimal:2'
    ];
}


