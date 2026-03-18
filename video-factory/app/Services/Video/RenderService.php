<?php

namespace App\Services\Video;

use App\Models\RenderTask;
use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RenderService
{
    public function render(Video $video, RenderTask $renderTask, ?string $captionFilePath = null): string
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);
        $outputRelative = $this->outputRelativePath($renderTask, false);
        Storage::disk($video->storage_disk)->makeDirectory('videos/rendered');
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        $videoFilter = $this->buildVideoFilter($video, $renderTask, $captionFilePath);
        $cmd = [config('videofactory.ffmpeg_path', 'ffmpeg'), '-i', $inputPath];
        $cmd = array_merge($cmd, $this->audioInputArguments($video));
        $cmd = array_merge($cmd, $this->renderArguments($videoFilter, $outputPath, $video));

        $result = Process::timeout(1800)->run($cmd);
        if (!$result->successful()) {
            throw new RuntimeException('Render failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }

    public function renderWithSilenceCut(Video $video, RenderTask $renderTask, ?string $captionFilePath = null): string
    {
        $silenceSegments = $video->silenceSegments()->get();
        if ($silenceSegments->isEmpty()) {
            return $this->render($video, $renderTask, $captionFilePath);
        }

        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);
        $outputRelative = $this->outputRelativePath($renderTask, true);
        Storage::disk($video->storage_disk)->makeDirectory('videos/rendered');
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        $selectParts = [];
        $prevEnd = 0.0;
        foreach ($silenceSegments as $seg) {
            $startSec = $seg->start_ms / 1000;
            if ($startSec > $prevEnd) {
                $selectParts[] = "between(t,{$prevEnd},{$startSec})";
            }
            $prevEnd = $seg->end_ms / 1000;
        }
        $selectParts[] = "gte(t,{$prevEnd})";
        $selectExpr = implode('+', $selectParts);

        $filters = ["select='{$selectExpr}'", 'setpts=N/FRAME_RATE/TB', $this->buildScaleFilter($renderTask)];
        if ($captionFilePath && $video->enable_captions) {
            $filters[] = $this->buildCaptionFilter(Storage::disk($video->storage_disk)->path($captionFilePath));
        }
        $videoFilter = implode(',', array_filter($filters));

        $cmd = [config('videofactory.ffmpeg_path', 'ffmpeg'), '-i', $inputPath];
        $cmd = array_merge($cmd, $this->audioInputArguments($video));
        $audioFilter = ["aselect='{$selectExpr}'", 'asetpts=N/SR/TB'];
        if ($video->bgm_path) {
            $audioFilter[] = '[1:a]volume=' . ($video->bgm_volume / 100) . '[bgm]';
            $audioFilter[] = '[0:a][bgm]amix=inputs=2:duration=first:dropout_transition=2';
        }
        $cmd[] = '-vf';
        $cmd[] = $videoFilter;
        $cmd[] = '-af';
        $cmd[] = implode(';', $audioFilter);
        $cmd = array_merge($cmd, $this->codecArguments($outputPath));

        $result = Process::timeout(1800)->run($cmd);
        if (!$result->successful()) {
            throw new RuntimeException('Render with cut failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }

    private function outputRelativePath(RenderTask $renderTask, bool $cut): string
    {
        return 'videos/rendered/' . $renderTask->id . '_' . $renderTask->render_type . ($cut ? '_cut' : '') . '.mp4';
    }

    private function buildVideoFilter(Video $video, RenderTask $renderTask, ?string $captionFilePath): string
    {
        $filters = [$this->buildScaleFilter($renderTask)];
        if ($captionFilePath && $video->enable_captions) {
            $filters[] = $this->buildCaptionFilter(Storage::disk($video->storage_disk)->path($captionFilePath));
        }
        return implode(',', array_filter($filters));
    }

    private function buildScaleFilter(RenderTask $renderTask): string
    {
        $w = $renderTask->target_width;
        $h = $renderTask->target_height;

        return match ($renderTask->aspect_ratio) {
            '9:16' => "crop=ih*9/16:ih,scale={$w}:{$h}",
            '1:1' => 'crop=min(iw\\,ih):min(iw\\,ih),scale=' . $w . ':' . $h,
            default => "scale={$w}:{$h}:force_original_aspect_ratio=decrease,pad={$w}:{$h}:(ow-iw)/2:(oh-ih)/2",
        };
    }

    private function buildCaptionFilter(string $captionPath): string
    {
        $escaped = str_replace([':', '\\', "'"], ['\\:', '\\\\', "\\'"], $captionPath);
        return "subtitles='{$escaped}'";
    }

    private function audioInputArguments(Video $video): array
    {
        if (!$video->bgm_path) {
            return [];
        }
        return ['-stream_loop', '-1', '-i', Storage::disk($video->storage_disk)->path($video->bgm_path)];
    }

    private function renderArguments(string $videoFilter, string $outputPath, Video $video): array
    {
        $args = ['-vf', $videoFilter];
        if ($video->bgm_path) {
            $args[] = '-filter_complex';
            $args[] = '[1:a]volume=' . ($video->bgm_volume / 100) . '[bgm];[0:a][bgm]amix=inputs=2:duration=first:dropout_transition=2';
        }
        return array_merge($args, $this->codecArguments($outputPath));
    }

    private function codecArguments(string $outputPath): array
    {
        return [
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-y',
            $outputPath,
        ];
    }
}
