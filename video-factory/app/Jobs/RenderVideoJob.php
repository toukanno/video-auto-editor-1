<?php

namespace App\Jobs;

use App\Models\RenderTask;
use App\Models\Video;
use App\Services\Video\RenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RenderVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 1800;

    public function __construct(
        public int $videoId,
        public int $renderTaskId,
        public string $captionFilePath
    ) {}

    public function handle(RenderService $service): void
    {
        $video = Video::findOrFail($this->videoId);
        $renderTask = RenderTask::findOrFail($this->renderTaskId);

        $video->markStatus(Video::STATUS_RENDERING);
        $renderTask->update([
            'status' => RenderTask::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        // Render with silence cut if silence segments exist
        $hasSilence = $video->silenceSegments()->exists();

        $outputPath = $hasSilence
            ? $service->renderWithSilenceCut($video, $renderTask, $this->captionFilePath)
            : $service->render($video, $renderTask, $this->captionFilePath);

        $renderTask->update([
            'output_path' => $outputPath,
            'status' => RenderTask::STATUS_COMPLETED,
            'finished_at' => now(),
        ]);

        $video->markStatus(Video::STATUS_RENDERED);

        // Chain to thumbnail generation
        GenerateThumbnailJob::dispatch($this->videoId, $this->renderTaskId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('render', $e->getMessage());

        $renderTask = RenderTask::find($this->renderTaskId);
        $renderTask?->update([
            'status' => RenderTask::STATUS_FAILED,
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
            'finished_at' => now(),
        ]);
    }
}
