<?php

namespace App\Jobs;

use App\Models\RenderTask;
use App\Models\Video;
use App\Services\Video\ProcessingLogService;
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

    public function __construct(public int $videoId, public int $renderTaskId) {}

    public function handle(ThumbnailService $service, ProcessingLogService $logService): void
    {
        $video = Video::findOrFail($this->videoId);
        $thumbPath = $service->generate($video);
        $renderTask = RenderTask::findOrFail($this->renderTaskId);
        $renderTask->update(['thumbnail_path' => $thumbPath]);

        if (!$video->renderTasks()->where('status', '!=', RenderTask::STATUS_COMPLETED)->exists()) {
            $video->markStatus(Video::STATUS_COMPLETED);
        }

        $logService->info($video, 'thumbnail', 'サムネイル生成が完了しました。');
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        if (!$video) {
            return;
        }

        // Only mark as completed if no other render tasks are still processing
        if (!$video->renderTasks()->where('status', '!=', RenderTask::STATUS_COMPLETED)->exists()) {
            $video->markStatus(Video::STATUS_COMPLETED);
        }

        app(ProcessingLogService::class)->warning($video, 'thumbnail', 'サムネイル生成に失敗しましたが処理は継続完了扱いです。', ['reason' => $e->getMessage()]);
    }
}
