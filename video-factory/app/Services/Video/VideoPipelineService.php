<?php

namespace App\Services\Video;

use App\Jobs\BuildCaptionFileJob;
use App\Jobs\DetectSilenceJob;
use App\Jobs\ExtractAudioJob;
use App\Jobs\GenerateThumbnailJob;
use App\Jobs\NormalizeTranscriptJob;
use App\Jobs\RenderVideoJob;
use App\Jobs\TranscribeVideoJob;
use App\Models\Video;

class VideoPipelineService
{
    /**
     * Kick off the full processing pipeline for a video.
     * Each job chains to the next on success.
     */
    public function start(Video $video): void
    {
        ExtractAudioJob::dispatch($video->id);
    }

    /**
     * Re-run the pipeline from a specific step.
     */
    public function rerunFrom(Video $video, string $step): void
    {
        $jobMap = [
            'extract_audio' => ExtractAudioJob::class,
            'transcribe' => TranscribeVideoJob::class,
            'normalize' => NormalizeTranscriptJob::class,
            'detect_silence' => DetectSilenceJob::class,
            'build_caption' => BuildCaptionFileJob::class,
            'render' => RenderVideoJob::class,
            'thumbnail' => GenerateThumbnailJob::class,
        ];

        $jobClass = $jobMap[$step] ?? ExtractAudioJob::class;
        $jobClass::dispatch($video->id);
    }

    /**
     * Re-run from the last failed step.
     */
    public function retry(Video $video): void
    {
        if ($video->last_failed_step) {
            $this->rerunFrom($video, $video->last_failed_step);
        } else {
            $this->start($video);
        }
    }
}
