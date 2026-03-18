<?php

namespace App\Services\Video;

use App\Models\RenderTask;
use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RenderService
{
    public function render(Video $video, RenderTask $renderTask, ?string $captionFilePath): string
    {
        return $this->runRender($video, $renderTask, $captionFilePath, false);
    }

    public function renderWithSilenceCut(Video $video, RenderTask $renderTask, ?string $captionFilePath): string
    {
        return $this->runRender($video, $renderTask, $captionFilePath, true);
    }

    private function runRender(Video $video, RenderTask $renderTask, ?string $captionFilePath, bool $cutSilence): string
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);
        $outputRelative = 'videos/rendered/' . $renderTask->id . '_' . $renderTask->render_type . '.mp4';
        Storage::disk($video->storage_disk)->makeDirectory('videos/rendered');
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        $ffmpeg = config('videofactory.ffmpeg_path', 'ffmpeg');
        $cmd = [$ffmpeg, '-i', $inputPath];

        $bgmPath = $video->processingOption('bgm_path');
        if ($bgmPath) {
            $cmd[] = '-stream_loop';
            $cmd[] = '-1';
            $cmd[] = '-i';
            $cmd[] = Storage::disk($video->storage_disk)->path($bgmPath);
        }

        $sePath = $video->processingOption('se_path');
        if ($sePath) {
            $cmd[] = '-i';
            $cmd[] = Storage::disk($video->storage_disk)->path($sePath);
        }

        $cmd = array_merge($cmd, $this->buildFilterArgs($video, $renderTask, $captionFilePath, $cutSilence));

        $videoBitrate = $video->export_options['video_bitrate'] ?? '4M';
        $audioBitrate = $video->export_options['audio_bitrate'] ?? '128k';
        $fps = (string) ($video->export_options['fps'] ?? 30);

        $cmd = array_merge($cmd, [
            '-r', $fps,
            '-c:v', 'libx264',
            '-preset', $this->resolvePreset($video->processing_profile),
            '-b:v', $videoBitrate,
            '-c:a', 'aac',
            '-b:a', $audioBitrate,
            '-movflags', '+faststart',
            '-shortest',
            '-y',
            $outputPath,
        ]);

        $result = Process::timeout(1800)->run($cmd);
        if (!$result->successful()) {
            throw new RuntimeException('Render failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }

    private function buildFilterArgs(Video $video, RenderTask $renderTask, ?string $captionFilePath, bool $cutSilence): array
    {
        $videoFilters = [];
        $audioFilters = [];

        if ($cutSilence && $video->silenceSegments()->exists()) {
            $parts = [];
            $prevEnd = 0.0;
            foreach ($video->silenceSegments as $seg) {
                $startSec = $seg->start_ms / 1000;
                if ($startSec > $prevEnd) {
                    $parts[] = "between(t,{$prevEnd},{$startSec})";
                }
                $prevEnd = $seg->end_ms / 1000;
            }
            $parts[] = "gte(t,{$prevEnd})";
            $selectExpr = implode('+', $parts);
            $videoFilters[] = "select='{$selectExpr}'";
            $videoFilters[] = 'setpts=N/FRAME_RATE/TB';
            $audioFilters[] = "aselect='{$selectExpr}'";
            $audioFilters[] = 'asetpts=N/SR/TB';
        }

        $videoFilters[] = $this->buildScaleFilter($video, $renderTask);

        if ($video->processingOption('enable_caption', true) && $captionFilePath) {
            $videoFilters[] = $this->buildCaptionFilter(Storage::disk($video->storage_disk)->path($captionFilePath));
        }

        if ($video->processingOption('bgm_path')) {
            $bgmVolume = max(0, min(1, ((int) $video->processingOption('bgm_volume', 20)) / 100));
            $audioFilters[] = '[1:a]volume=' . $bgmVolume . '[bgm]';
            $audioMixInput = '[0:a][bgm]';
            $audioFilters[] = $audioMixInput . 'amix=inputs=2:duration=first:dropout_transition=2[aout]';
        }

        if ($video->processingOption('se_path')) {
            $inputIndex = $video->processingOption('bgm_path') ? 2 : 1;
            $seVolume = max(0, min(1, ((int) $video->processingOption('se_volume', 40)) / 100));
            $audioFilters[] = '[' . $inputIndex . ':a]volume=' . $seVolume . ',adelay=500|500[se]';
            $base = collect($audioFilters)->contains(fn ($filter) => str_contains($filter, '[aout]')) ? '[aout]' : '[0:a]';
            $audioFilters[] = $base . '[se]amix=inputs=2:duration=first:dropout_transition=2[aout2]';
        }

        $args = [];
        if ($videoFilters) {
            $args[] = '-vf';
            $args[] = implode(',', $videoFilters);
        }

        if ($audioFilters) {
            $args[] = '-filter_complex';
            $args[] = implode(';', $audioFilters);
            $args[] = '-map';
            $args[] = '0:v:0';
            $args[] = '-map';
            $args[] = collect($audioFilters)->contains(fn ($filter) => str_contains($filter, '[aout2]')) ? '[aout2]' : (collect($audioFilters)->contains(fn ($filter) => str_contains($filter, '[aout]')) ? '[aout]' : '0:a:0');
        }

        return $args;
    }

    private function buildScaleFilter(Video $video, RenderTask $renderTask): string
    {
        $w = $renderTask->target_width;
        $h = $renderTask->target_height;
        $mode = $video->processingOption('resize_mode', $renderTask->render_type === 'short' ? 'short' : 'long');

        return match ($mode) {
            'square' => "scale={$w}:{$w}:force_original_aspect_ratio=decrease,pad={$w}:{$w}:(ow-iw)/2:(oh-ih)/2",
            'original' => 'scale=iw:ih',
            'long' => "scale={$w}:{$h}:force_original_aspect_ratio=decrease,pad={$w}:{$h}:(ow-iw)/2:(oh-ih)/2",
            default => "crop='min(iw,ih)*9/16':'min(iw,ih)',scale={$w}:{$h}",
        };
    }

    private function buildCaptionFilter(string $captionPath): string
    {
        $escaped = str_replace([':', '\\', "'"], ['\\:', '\\\\', "\\'"], $captionPath);
        return "subtitles='{$escaped}'";
    }

    private function resolvePreset(string $profile): string
    {
        return match ($profile) {
            'fast' => 'veryfast',
            'quality' => 'slow',
            default => 'medium',
        };
    }
}
