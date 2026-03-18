<?php

namespace App\Services\Video;

use App\Models\SilenceSegment;
use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SilenceDetectionService
{
    /**
     * Detect silence segments using FFmpeg silencedetect filter.
     *
     * @param float $noiseThreshold dB threshold (default -30dB)
     * @param float $minDuration   minimum silence duration in seconds
     */
    public function detect(
        Video $video,
        float $noiseThreshold = -30,
        float $minDuration = 0.5
    ): array {
        $audioPath = Storage::disk($video->storage_disk)->path($video->audio_path);

        if (!filled(shell_exec('command -v '.escapeshellarg(config('videofactory.ffmpeg_path', 'ffmpeg'))))) {
            $video->silenceSegments()->delete();

            return [];
        }

        $result = Process::timeout(300)->run([
            config('videofactory.ffmpeg_path', 'ffmpeg'), '-i', $audioPath,
            '-af', "silencedetect=noise={$noiseThreshold}dB:d={$minDuration}",
            '-f', 'null', '-',
        ]);

        // silencedetect outputs to stderr
        $output = $result->errorOutput();
        $segments = $this->parseSilenceOutput($output);

        // Save to DB
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
            if (preg_match('/silence_end:\s*([\d.]+)\s*\|\s*silence_duration:\s*([\d.]+)/', $line, $m)) {
                if ($currentStart !== null) {
                    $segments[] = [
                        'start' => $currentStart,
                        'end' => (float) $m[1],
                        'duration' => (float) $m[2],
                    ];
                    $currentStart = null;
                }
            }
        }

        return $segments;
    }
}
