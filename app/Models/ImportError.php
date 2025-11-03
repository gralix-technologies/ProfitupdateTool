<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class ImportError extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'import_session_id',
        'product_id',
        'row_number',
        'error_type',
        'error_message',
        'row_data',
        'context'
    ];

    protected $casts = [
        'row_data' => 'array',
        'context' => 'array'
    ];

    
    const TYPE_VALIDATION = 'validation';
    const TYPE_PROCESSING = 'processing';
    const TYPE_SYSTEM = 'system';

    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('import_session_id', $sessionId);
    }

    
    public function scopeOfType($query, string $type)
    {
        return $query->where('error_type', $type);
    }

    
    public function getFormattedMessage(): string
    {
        $message = "Row {$this->row_number}: {$this->error_message}";
        
        if ($this->context && isset($this->context['field'])) {
            $message .= " (Field: {$this->context['field']})";
        }
        
        return $message;
    }
}



