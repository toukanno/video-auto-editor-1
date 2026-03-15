<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RenderTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'video_id',
        'caption_style_id',
        'render_type',
        'aspect_ratio',
        'target_width',
        'target_height',
        'output_path',
        'thumbnail_path',
        'status',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'target_width' => 'integer',
        'target_height' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function captionStyle(): BelongsTo
    {
        return $this->belongsTo(CaptionStyle::class);
    }

    public function publishTasks(): HasMany
    {
        return $this->hasMany(PublishTask::class);
    }
}
