<?php

namespace App\Services\Video;

use App\Models\RenderTask;
use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RenderService
{
    /**
     * Render the final video with captions burned in and optional aspect ratio conversion.
     */
    public function render(Video $video, RenderTask $renderTask, string $captionFilePath): string
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);

        $outputDir = 'videos/rendered';
        $outputFilename = $renderTask->id . '_' . $renderTask->render_type . '.mp4';
        $outputRelative = $outputDir . '/' . $outputFilename;
        Storage::disk($video->storage_disk)->makeDirectory($outputDir);
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        $captionAbsPath = Storage::disk($video->storage_disk)->path($captionFilePath);

        $filters = $this->buildFilterChain($video, $renderTask, $captionAbsPath);

        $cmd = [
            'ffmpeg', '-i', $inputPath,
            '-vf', $filters,
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-y',
            $outputPath,
        ];

        $result = Process::timeout(1800)->run($cmd);

        if (!$result->successful()) {
            throw new RuntimeException('Render failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }

    /**
     * Render with silence segments removed.
     */
    public function renderWithSilenceCut(
        Video $video,
        RenderTask $renderTask,
        string $captionFilePath
    ): string {
        $silenceSegments = $video->silenceSegments()->get();

        if ($silenceSegments->isEmpty()) {
            return $this->render($video, $renderTask, $captionFilePath);
        }

        // Build select filter to exclude silence segments
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);
        $outputDir = 'videos/rendered';
        $outputFilename = $renderTask->id . '_' . $renderTask->render_type . '_cut.mp4';
        $outputRelative = $outputDir . '/' . $outputFilename;
        Storage::disk($video->storage_disk)->makeDirectory($outputDir);
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        // Create a concat filter from non-silent segments
        $selectParts = [];
        $prevEnd = 0.0;

        foreach ($silenceSegments as $seg) {
            $startSec = $seg->start_ms / 1000;
            if ($startSec > $prevEnd) {
                $selectParts[] = "between(t,{$prevEnd},{$startSec})";
            }
            $prevEnd = $seg->end_ms / 1000;
        }
        // Add the last segment after final silence
        $selectParts[] = "gte(t,{$prevEnd})";

        $selectExpr = implode('+', $selectParts);
        $captionAbsPath = Storage::disk($video->storage_disk)->path($captionFilePath);

        $videoFilter = "select='{$selectExpr}',setpts=N/FRAME_RATE/TB";
        $audioFilter = "aselect='{$selectExpr}',asetpts=N/SR/TB";

        // Add caption overlay and aspect ratio conversion
        $captionFilter = $this->buildCaptionFilter($captionAbsPath);
        $scaleFilter = $this->buildScaleFilter($renderTask);

        $vf = implode(',', array_filter([$videoFilter, $scaleFilter, $captionFilter]));
        $af = $audioFilter;

        $cmd = [
            'ffmpeg', '-i', $inputPath,
            '-vf', $vf,
            '-af', $af,
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-y',
            $outputPath,
        ];

        $result = Process::timeout(1800)->run($cmd);

        if (!$result->successful()) {
            throw new RuntimeException('Render with cut failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }

    private function buildFilterChain(Video $video, RenderTask $renderTask, string $captionPath): string
    {
        $filters = [];

        $filters[] = $this->buildScaleFilter($renderTask);
        $filters[] = $this->buildCaptionFilter($captionPath);

        return implode(',', array_filter($filters));
    }

    private function buildScaleFilter(RenderTask $renderTask): string
    {
        $w = $renderTask->target_width;
        $h = $renderTask->target_height;

        if ($renderTask->aspect_ratio === '9:16') {
            // Crop to center then scale for vertical video
            return "crop=ih*9/16:ih,scale={$w}:{$h}";
        }

        return "scale={$w}:{$h}:force_original_aspect_ratio=decrease,pad={$w}:{$h}:(ow-iw)/2:(oh-ih)/2";
    }

    private function buildCaptionFilter(string $captionPath): string
    {
        $escaped = str_replace([':', '\\', "'"], ['\\:', '\\\\', "\\'"], $captionPath);
        return "subtitles='{$escaped}'";
    }
}
