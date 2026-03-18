<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'step',
        'level',
        'message',
        'context_json',
    ];

    protected $casts = [
        'context_json' => 'array',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
