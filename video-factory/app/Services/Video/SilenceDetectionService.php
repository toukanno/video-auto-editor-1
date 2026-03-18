<?php

namespace App\Services\Video;

use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class SilenceDetectionService
{
    public function detect(Video $video, ?float $noiseThreshold = null, ?float $minDuration = null): array
    {
        $noiseThreshold ??= (float) config('videofactory.silence_threshold_db', -30);
        $minDuration ??= (float) config('videofactory.silence_min_duration', 0.5);

        if (!$video->processingOption('enable_silence_cut', true)) {
            $video->silenceSegments()->delete();
            return [];
        }

        $audioPath = Storage::disk($video->storage_disk)->path($video->audio_path);
        $ffmpeg = config('videofactory.ffmpeg_path', 'ffmpeg');
        $result = Process::timeout(300)->run([
            $ffmpeg, '-i', $audioPath,
            '-af', "silencedetect=noise={$noiseThreshold}dB:d={$minDuration}",
            '-f', 'null', '-',
        ]);

        $segments = $this->parseSilenceOutput($result->errorOutput());
        $video->silenceSegments()->delete();

        $saved = [];
        foreach ($segments as $seg) {
            $saved[] = $video->silenceSegments()->create([
                'start_ms' => (int) ($seg['start'] * 1000),
                'end_ms' => (int) ($seg['end'] * 1000),
                'duration_ms' => (int) ($seg['duration'] * 1000),
            ]);
        }

        return $saved;
    }

    private function parseSilenceOutput(string $output): array
    {
        $segments = [];
        $currentStart = null;

        foreach (explode("\n", $output) as $line) {
            if (preg_match('/silence_start:\s*([\d.]+)/', $line, $m)) {
                $currentStart = (float) $m[1];
            }
            if (preg_match('/silence_end:\s*([\d.]+)\s*\|\s*silence_duration:\s*([\d.]+)/', $line, $m) && $currentStart !== null) {
                $segments[] = [
                    'start' => $currentStart,
                    'end' => (float) $m[1],
                    'duration' => (float) $m[2],
                ];
                $currentStart = null;
            }
        }

        return $segments;
    }
}
