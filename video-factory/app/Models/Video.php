<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    use HasFactory;

    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_EXTRACTING_AUDIO = 'extracting_audio';
    public const STATUS_TRANSCRIBING = 'transcribing';
    public const STATUS_NORMALIZING = 'normalizing';
    public const STATUS_DETECTING_SILENCE = 'detecting_silence';
    public const STATUS_BUILDING_CAPTION = 'building_caption';
    public const STATUS_RENDERING = 'rendering';
    public const STATUS_RENDERED = 'rendered';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'title',
        'source_filename',
        'storage_disk',
        'original_path',
        'audio_path',
        'duration_sec',
        'width',
        'height',
        'fps',
        'status',
        'error_message',
        'last_failed_step',
        'selected_caption_style_id',
        'processing_profile',
        'processing_options',
        'export_options',
        'pipeline_summary',
        'last_processed_at',
    ];

    protected $casts = [
        'duration_sec' => 'decimal:2',
        'fps' => 'decimal:2',
        'processing_options' => 'array',
        'export_options' => 'array',
        'pipeline_summary' => 'array',
        'last_processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transcriptSegments(): HasMany
    {
        return $this->hasMany(TranscriptSegment::class)->orderBy('seq');
    }

    public function silenceSegments(): HasMany
    {
        return $this->hasMany(SilenceSegment::class)->orderBy('start_ms');
    }

    public function renderTasks(): HasMany
    {
        return $this->hasMany(RenderTask::class);
    }

    public function processingLogs(): HasMany
    {
        return $this->hasMany(ProcessingLog::class)->latest('id');
    }

    public function selectedCaptionStyle(): BelongsTo
    {
        return $this->belongsTo(CaptionStyle::class, 'selected_caption_style_id');
    }

    public function markStatus(string $status): void
    {
        $this->update([
            'status' => $status,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $step, string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'last_failed_step' => $step,
            'error_message' => mb_substr($message, 0, 1000),
        ]);
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function progressPercent(): int
    {
        return match ($this->status) {
            self::STATUS_UPLOADED => 5,
            self::STATUS_EXTRACTING_AUDIO => 15,
            self::STATUS_TRANSCRIBING => 30,
            self::STATUS_NORMALIZING => 45,
            self::STATUS_DETECTING_SILENCE => 60,
            self::STATUS_BUILDING_CAPTION => 75,
            self::STATUS_RENDERING => 90,
            self::STATUS_RENDERED, self::STATUS_COMPLETED => 100,
            self::STATUS_PUBLISHING => 95,
            self::STATUS_FAILED => 0,
            default => 0,
        };
    }

    public function processingOption(string $key, mixed $default = null): mixed
    {
        return data_get($this->processing_options ?? [], $key, $default);
    }
}
