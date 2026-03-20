<?php

namespace App\Jobs;

use App\Models\RenderTask;
use App\Models\Video;
use App\Services\Video\ProcessingLogService;
use App\Services\Video\RenderService;
use Illuminate\Bus\Queueable;
use RuntimeException;
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

    public function __construct(public int $videoId, public int $renderTaskId, public string $captionFilePath) {}

    public function handle(RenderService $service, ProcessingLogService $logService): void
    {
        $video = Video::findOrFail($this->videoId);
        $renderTask = RenderTask::findOrFail($this->renderTaskId);

        $video->markStatus(Video::STATUS_RENDERING);
        $renderTask->update(['status' => RenderTask::STATUS_PROCESSING, 'started_at' => now()]);
        $logService->info($video, 'render', 'レンダリングを開始しました。', ['render_type' => $renderTask->render_type]);

        if (empty($this->captionFilePath)) {
            throw new RuntimeException('字幕ファイルパスが指定されていません。');
        }

        $outputPath = $video->processingOption('enable_silence_cut', true) && $video->silenceSegments()->exists()
            ? $service->renderWithSilenceCut($video, $renderTask, $this->captionFilePath)
            : $service->render($video, $renderTask, $this->captionFilePath);

        $renderTask->update(['output_path' => $outputPath, 'status' => RenderTask::STATUS_COMPLETED, 'finished_at' => now()]);
        $video->update(['status' => Video::STATUS_RENDERED, 'last_processed_at' => now()]);
        $logService->info($video, 'render', 'レンダリングが完了しました。', ['render_type' => $renderTask->render_type]);

        GenerateThumbnailJob::dispatch($this->videoId, $this->renderTaskId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('render', $e->getMessage());
        $renderTask = RenderTask::find($this->renderTaskId);
        $renderTask?->update(['status' => RenderTask::STATUS_FAILED, 'error_message' => mb_substr($e->getMessage(), 0, 1000), 'finished_at' => now()]);
        if ($video) app(ProcessingLogService::class)->error($video, 'render', $e->getMessage(), ['render_task_id' => $this->renderTaskId]);
    }
}
