<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Configuration extends Model
{
    use Auditable;

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    public static function getValue(string $key, $default = null)
    {
        $config = static::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    public static function setValue(string $key, $value, string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );
    }
}


