<?php

namespace App\Jobs;

use App\Models\CaptionStyle;
use App\Models\RenderTask;
use App\Models\Video;
use App\Services\Caption\CaptionFileBuilderService;
use App\Services\Caption\CaptionStyleService;
use App\Services\Video\ProcessingLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BuildCaptionFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public int $videoId) {}

    public function handle(CaptionFileBuilderService $builder, CaptionStyleService $styleService, ProcessingLogService $logService): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_BUILDING_CAPTION);

        $style = $video->selectedCaptionStyle
            ?? CaptionStyle::where('user_id', $video->user_id)->first()
            ?? $styleService->ensureDefault($video->user);

        $captionPath = $video->processingOption('enable_caption', true)
            ? $builder->buildAss($video, $style)
            : null;

        $builder->buildSrt($video);
        $logService->info($video, 'build_caption', '字幕ファイルを生成しました。', ['style' => $style->name]);

        $tasks = [];
        if ($video->processingOption('generate_short', true)) {
            $tasks[] = $video->renderTasks()->updateOrCreate(
                ['render_type' => 'short'],
                ['caption_style_id' => $style->id, 'aspect_ratio' => '9:16', 'target_width' => 1080, 'target_height' => 1920, 'status' => RenderTask::STATUS_PENDING]
            );
        }
        if ($video->processingOption('generate_long', false)) {
            $tasks[] = $video->renderTasks()->updateOrCreate(
                ['render_type' => 'long'],
                ['caption_style_id' => $style->id, 'aspect_ratio' => '16:9', 'target_width' => 1920, 'target_height' => 1080, 'status' => RenderTask::STATUS_PENDING]
            );
        }

        foreach ($tasks as $renderTask) {
            RenderVideoJob::dispatch($this->videoId, $renderTask->id, $captionPath ?? '');
        }
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('build_caption', $e->getMessage());
        if ($video) app(ProcessingLogService::class)->error($video, 'build_caption', $e->getMessage());
    }
}
