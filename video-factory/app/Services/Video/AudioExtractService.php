<?php

namespace App\Services\Video;

use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AudioExtractService
{
    /**
     * Extract audio from video as mono 16kHz WAV (optimal for transcription).
     */
    public function extract(Video $video): string
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);
        $outputDir = 'videos/audio';
        $outputFilename = $video->id . '.wav';
        $outputRelative = $outputDir . '/' . $outputFilename;

        if (!$this->commandExists(config('videofactory.ffmpeg_path', 'ffmpeg'))) {
            return $video->original_path;
        }

        Storage::disk($video->storage_disk)->makeDirectory($outputDir);
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        $result = Process::timeout(600)->run([
            config('videofactory.ffmpeg_path', 'ffmpeg'), '-i', $inputPath,
            '-vn',           // no video
            '-ac', '1',      // mono
            '-ar', '16000',  // 16kHz
            '-y',            // overwrite
            $outputPath,
        ]);

        if (!$result->successful()) {
            throw new RuntimeException('FFmpeg audio extraction failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }

    /**
     * Probe video metadata (duration, resolution, fps).
     */
    public function probe(Video $video): array
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);

        if (!$this->commandExists(config('videofactory.ffprobe_path', 'ffprobe'))) {
            return [
                'duration_sec' => null,
                'width' => null,
                'height' => null,
                'fps' => null,
            ];
        }

        $result = Process::timeout(30)->run([
            config('videofactory.ffprobe_path', 'ffprobe'),
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $inputPath,
        ]);

        if (!$result->successful()) {
            throw new RuntimeException('FFprobe failed: ' . $result->errorOutput());
        }

        $data = json_decode($result->output(), true);
        $videoStream = collect($data['streams'] ?? [])
            ->firstWhere('codec_type', 'video');

        $duration = (float) ($data['format']['duration'] ?? 0);
        $width = (int) ($videoStream['width'] ?? 0);
        $height = (int) ($videoStream['height'] ?? 0);

        $fpsRaw = $videoStream['r_frame_rate'] ?? '30/1';
        $fpsParts = explode('/', $fpsRaw);
        $fps = count($fpsParts) === 2 && (int) $fpsParts[1] > 0
            ? (float) $fpsParts[0] / (float) $fpsParts[1]
            : 30.0;

        return [
            'duration_sec' => round($duration, 2),
            'width' => $width,
            'height' => $height,
            'fps' => round($fps, 2),
        ];
    }

    private function commandExists(string $command): bool
    {
        return filled(shell_exec('command -v '.escapeshellarg($command)));
    }
}
