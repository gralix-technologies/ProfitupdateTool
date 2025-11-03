<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, Auditable;

    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    
    public function dashboards(): HasMany
    {
        return $this->hasMany(Dashboard::class);
    }

    
    public function formulas(): HasMany
    {
        return $this->hasMany(Formula::class, 'created_by');
    }

    
    protected $excludeFromAudit = [
        'password',
        'remember_token',
        'email_verified_at',
    ];
}



