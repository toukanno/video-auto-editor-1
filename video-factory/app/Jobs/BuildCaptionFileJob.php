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

        // Get selected caption style, otherwise use default
        $style = $video->preferredCaptionStyle
            ?? CaptionStyle::where('user_id', $video->user_id)->first()
            ?? $styleService->ensureDefault($video->user);

        // Build ASS file with styling
        $captionPath = $builder->buildAss($video, $style);

        // Also build SRT as backup
        $builder->buildSrt($video);

        $renderProfiles = [];

        if ($video->shouldRenderShort()) {
            $renderProfiles[] = [
                'render_type' => $video->shouldAutoCutSilence() ? 'short_auto_cut' : 'short',
                'aspect_ratio' => '9:16',
                'target_width' => 1080,
                'target_height' => 1920,
            ];
        }

        if ($video->shouldRenderLong()) {
            $renderProfiles[] = [
                'render_type' => 'long',
                'aspect_ratio' => '16:9',
                'target_width' => 1920,
                'target_height' => 1080,
            ];
        }

        if (empty($renderProfiles)) {
            $renderProfiles[] = [
                'render_type' => $video->shouldAutoCutSilence() ? 'short_auto_cut' : 'short',
                'aspect_ratio' => '9:16',
                'target_width' => 1080,
                'target_height' => 1920,
            ];
        }

        foreach ($renderProfiles as $profile) {
            $renderTask = $video->renderTasks()->updateOrCreate(
                ['render_type' => $profile['render_type']],
                [
                    'caption_style_id' => $style->id,
                    'aspect_ratio' => $profile['aspect_ratio'],
                    'target_width' => $profile['target_width'],
                    'target_height' => $profile['target_height'],
                    'status' => RenderTask::STATUS_PENDING,
                    'error_message' => null,
                ]
            );

            RenderVideoJob::dispatch($this->videoId, $renderTask->id, $captionPath);
        }
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('build_caption', $e->getMessage());
    }
}
