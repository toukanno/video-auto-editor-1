<?php

namespace App\Jobs;

use App\Models\RenderTask;
use App\Models\Video;
use App\Services\Video\ThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public int $videoId,
        public int $renderTaskId
    ) {}

    public function handle(ThumbnailService $service): void
    {
        $video = Video::findOrFail($this->videoId);

        $thumbPath = $service->generate($video);

        $renderTask = RenderTask::findOrFail($this->renderTaskId);
        $renderTask->update(['thumbnail_path' => $thumbPath]);

        $video->markStatus(Video::STATUS_COMPLETED);
    }

    public function failed(Throwable $e): void
    {
        // Thumbnail failure is not critical, still mark as completed
        $video = Video::find($this->videoId);
        $video?->markStatus(Video::STATUS_COMPLETED);
    }
}
