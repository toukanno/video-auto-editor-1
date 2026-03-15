<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Video\AudioExtractService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExtractAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;

    public function __construct(public int $videoId) {}

    public function handle(AudioExtractService $service): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_EXTRACTING_AUDIO);

        // Probe video metadata
        $meta = $service->probe($video);
        $video->update($meta);

        // Extract audio
        $audioPath = $service->extract($video);
        $video->update(['audio_path' => $audioPath]);

        // Chain to next job
        TranscribeVideoJob::dispatch($this->videoId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('extract_audio', $e->getMessage());
    }
}
