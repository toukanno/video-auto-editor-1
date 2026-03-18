<?php

namespace App\Services\Video;

use App\Jobs\BuildCaptionFileJob;
use App\Jobs\DetectSilenceJob;
use App\Jobs\ExtractAudioJob;
use App\Jobs\GenerateThumbnailJob;
use App\Jobs\NormalizeTranscriptJob;
use App\Jobs\RenderVideoJob;
use App\Jobs\TranscribeVideoJob;
use App\Models\RenderTask;
use App\Models\Video;
use RuntimeException;

class VideoPipelineService
{
    public function start(Video $video): void
    {
        ExtractAudioJob::dispatch($video->id);
    }

    public function rerunFrom(Video $video, string $step): void
    {
        match ($step) {
            'extract_audio' => ExtractAudioJob::dispatch($video->id),
            'transcribe' => TranscribeVideoJob::dispatch($video->id),
            'normalize' => NormalizeTranscriptJob::dispatch($video->id),
            'detect_silence' => DetectSilenceJob::dispatch($video->id),
            'build_caption' => BuildCaptionFileJob::dispatch($video->id),
            'render' => $this->dispatchRender($video),
            'thumbnail' => $this->dispatchThumbnail($video),
            default => ExtractAudioJob::dispatch($video->id),
        };
    }

    public function retry(Video $video): void
    {
        if ($video->last_failed_step) {
            $this->rerunFrom($video, $video->last_failed_step);
            return;
        }
        $this->start($video);
    }

    private function dispatchRender(Video $video): void
    {
        $renderTask = $video->renderTasks()->latest()->first();
        if (!$renderTask) {
            throw new RuntimeException('再レンダリング対象が見つかりません。');
        }
        RenderVideoJob::dispatch($video->id, $renderTask->id, $video->enable_captions ? 'videos/captions/' . $video->id . '.ass' : '');
    }

    private function dispatchThumbnail(Video $video): void
    {
        $renderTask = $video->renderTasks()->where('status', RenderTask::STATUS_COMPLETED)->latest()->first();
        if (!$renderTask) {
            throw new RuntimeException('サムネイル生成対象のレンダリング結果がありません。');
        }
        GenerateThumbnailJob::dispatch($video->id, $renderTask->id);
    }
}
