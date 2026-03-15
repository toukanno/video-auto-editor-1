<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Caption\TranscriptNormalizerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class NormalizeTranscriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public int $videoId) {}

    public function handle(TranscriptNormalizerService $service): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_NORMALIZING);

        // Try LLM normalization, fall back to rule-based
        try {
            $service->normalize($video);
        } catch (Throwable) {
            $service->normalizeWithRules($video);
        }

        DetectSilenceJob::dispatch($this->videoId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('normalize', $e->getMessage());
    }
}
