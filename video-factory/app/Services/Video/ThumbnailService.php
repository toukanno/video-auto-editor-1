<?php

namespace App\Services\Video;

use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ThumbnailService
{
    /**
     * Generate thumbnail from a video at a specific timestamp.
     * Defaults to 25% of the video duration.
     */
    public function generate(Video $video, ?float $timestampSec = null): string
    {
        $inputPath = Storage::disk($video->storage_disk)->path($video->original_path);

        $outputDir = 'videos/thumbs';
        $outputFilename = $video->id . '.jpg';
        $outputRelative = $outputDir . '/' . $outputFilename;
        Storage::disk($video->storage_disk)->makeDirectory($outputDir);
        $outputPath = Storage::disk($video->storage_disk)->path($outputRelative);

        $timestamp = $timestampSec ?? ($video->duration_sec * 0.25);

        $result = Process::timeout(30)->run([
            'ffmpeg',
            '-ss', (string) $timestamp,
            '-i', $inputPath,
            '-vframes', '1',
            '-q:v', '2',
            '-y',
            $outputPath,
        ]);

        if (!$result->successful()) {
            throw new RuntimeException('Thumbnail generation failed: ' . $result->errorOutput());
        }

        return $outputRelative;
    }
}
