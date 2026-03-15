<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'render_task_id',
        'platform_account_id',
        'platform',
        'title',
        'description',
        'tags_json',
        'privacy_status',
        'external_id',
        'external_url',
        'status',
        'scheduled_at',
        'published_at',
        'response_json',
        'error_message',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'response_json' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function renderTask(): BelongsTo
    {
        return $this->belongsTo(RenderTask::class);
    }

    public function platformAccount(): BelongsTo
    {
        return $this->belongsTo(PlatformAccount::class);
    }
}
