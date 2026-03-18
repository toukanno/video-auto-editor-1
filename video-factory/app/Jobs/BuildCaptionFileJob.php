<?php

namespace App\Jobs;

use App\Models\CaptionStyle;
use App\Models\RenderTask;
use App\Models\Video;
use App\Services\Caption\CaptionFileBuilderService;
use App\Services\Caption\CaptionStyleService;
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

    public function handle(CaptionFileBuilderService $builder, CaptionStyleService $styleService): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_BUILDING_CAPTION);

        $style = $video->selectedCaptionStyle
            ?? CaptionStyle::where('user_id', $video->user_id)->first()
            ?? $styleService->ensureDefault($video->user);

        $captionPath = null;
        if ($video->enable_captions) {
            $captionPath = $builder->buildAss($video, $style);
            $builder->buildSrt($video);
        }

        $renderDefaults = match ($video->target_aspect_ratio) {
            '16:9' => ['aspect_ratio' => '16:9', 'target_width' => 1920, 'target_height' => 1080],
            '1:1' => ['aspect_ratio' => '1:1', 'target_width' => 1080, 'target_height' => 1080],
            default => ['aspect_ratio' => '9:16', 'target_width' => 1080, 'target_height' => 1920],
        };

        $renderTask = $video->renderTasks()->firstOrCreate(
            ['render_type' => $video->render_short ? 'short' : 'main'],
            array_merge($renderDefaults, [
                'caption_style_id' => $style->id,
                'status' => RenderTask::STATUS_PENDING,
            ])
        );

        RenderVideoJob::dispatch($this->videoId, $renderTask->id, $captionPath ?? '');
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('build_caption', $e->getMessage());
    }
}
