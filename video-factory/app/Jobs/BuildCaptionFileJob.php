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

        // Get user's default caption style or create one
        $style = CaptionStyle::where('user_id', $video->user_id)->first()
            ?? $styleService->ensureDefault($video->user);

        // Build ASS file with styling
        $captionPath = $builder->buildAss($video, $style);

        // Also build SRT as backup
        $builder->buildSrt($video);

        // Create render task if none exists
        $renderTask = $video->renderTasks()->firstOrCreate(
            ['render_type' => 'short'],
            [
                'caption_style_id' => $style->id,
                'aspect_ratio' => '9:16',
                'target_width' => 1080,
                'target_height' => 1920,
                'status' => RenderTask::STATUS_PENDING,
            ]
        );

        RenderVideoJob::dispatch($this->videoId, $renderTask->id, $captionPath);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('build_caption', $e->getMessage());
    }
}
