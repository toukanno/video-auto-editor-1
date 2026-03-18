<?php

namespace App\Services\Video;

use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AudioExtractService
{
    public function extract(Video $video): string
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);
        $outputRelative = 'videos/audio/' . $video->id . '.wav';

        Storage::disk($video->storage_disk)->makeDirectory('videos/audio');
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        $ffmpeg = config('videofactory.ffmpeg_path', 'ffmpeg');
        $result = Process::timeout(600)->run([
            $ffmpeg, '-i', $inputPath,
            '-vn', '-ac', '1', '-ar', '16000', '-y', $outputPath,
        ]);

        if (!$result->successful()) {
            throw new RuntimeException('FFmpeg audio extraction failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }

    public function probe(Video $video): array
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);
        $ffprobe = config('videofactory.ffprobe_path', 'ffprobe');

        $result = Process::timeout(30)->run([
            $ffprobe, '-v', 'quiet', '-print_format', 'json', '-show_format', '-show_streams', $inputPath,
        ]);

        if (!$result->successful()) {
            throw new RuntimeException('FFprobe failed: ' . $result->errorOutput());
        }

        $data = json_decode($result->output(), true);
        $videoStream = collect($data['streams'] ?? [])->firstWhere('codec_type', 'video');
        $duration = (float) ($data['format']['duration'] ?? 0);
        $width = (int) ($videoStream['width'] ?? 0);
        $height = (int) ($videoStream['height'] ?? 0);
        $fpsRaw = $videoStream['r_frame_rate'] ?? '30/1';
        $fpsParts = explode('/', $fpsRaw);
        $fps = count($fpsParts) === 2 && (int) $fpsParts[1] > 0 ? (float) $fpsParts[0] / (float) $fpsParts[1] : 30.0;

        return [
            'duration_sec' => round($duration, 2),
            'width' => $width,
            'height' => $height,
            'fps' => round($fps, 2),
        ];
    }
}
