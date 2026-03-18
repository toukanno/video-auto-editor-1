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
        'preferred_caption_style_id',
        'processing_options',
    ];

    protected $casts = [
        'duration_sec' => 'decimal:2',
        'fps' => 'decimal:2',
        'preferred_caption_style_id' => 'integer',
        'processing_options' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function preferredCaptionStyle(): BelongsTo
    {
        return $this->belongsTo(CaptionStyle::class, 'preferred_caption_style_id');
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

    public function markStatus(string $status): void
    {
        $this->update(['status' => $status, 'error_message' => null]);
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

    public function processingOption(string $key, mixed $default = null): mixed
    {
        return data_get($this->processing_options ?? [], $key, $default);
    }

    public function shouldRenderShort(): bool
    {
        return (bool) $this->processingOption('render_short', true);
    }

    public function shouldRenderLong(): bool
    {
        return (bool) $this->processingOption('render_long', false);
    }

    public function shouldAutoCutSilence(): bool
    {
        return (bool) $this->processingOption('auto_cut_silence', true);
    }
}
