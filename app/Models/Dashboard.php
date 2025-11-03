<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dashboard extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'user_id',
        'product_id',
        'layout',
        'filters',
        'description',
        'is_public',
        'is_active'
    ];

    protected $casts = [
        'layout' => 'array',
        'filters' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean'
    ];

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class)->orderBy('order_index');
    }

    
    public function addWidget(array $config): Widget
    {
        $maxOrder = $this->widgets()->max('order_index') ?? 0;
        
        return $this->widgets()->create([
            'title' => $config['title'],
            'type' => $config['type'],
            'configuration' => $config['configuration'] ?? [],
            'position' => $config['position'] ?? ['x' => 0, 'y' => 0, 'width' => 4, 'height' => 3],
            'data_source' => $config['data_source'] ?? null,
            'order_index' => $maxOrder + 1
        ]);
    }

    
    public function getDefaultFilters(): array
    {
        return $this->filters ?? [
            'date_range' => ['start' => null, 'end' => null],
            'branch' => null,
            'currency' => null,
            'demographic' => null,
            'product_type' => null
        ];
    }

    
    public function updateLayout(array $layout): void
    {
        $this->layout = $layout;
        $this->save();
    }

    
    public function canBeViewedBy(User $user): bool
    {
        return $this->user_id === $user->id || $this->is_public;
    }

    
    public function getWidgetCountAttribute(): int
    {
        return $this->widgets()->where('is_active', true)->count();
    }
}



