<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranscriptSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'seq',
        'start_ms',
        'end_ms',
        'text_raw',
        'text_normalized',
        'confidence',
        'speaker_label',
    ];

    protected $casts = [
        'start_ms' => 'integer',
        'end_ms' => 'integer',
        'confidence' => 'decimal:4',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function durationMs(): int
    {
        return $this->end_ms - $this->start_ms;
    }

    public function displayText(): string
    {
        return $this->text_normalized ?? $this->text_raw;
    }
}
