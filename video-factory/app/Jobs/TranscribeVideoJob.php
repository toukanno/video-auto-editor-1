<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Integrations\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TranscribeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public int $videoId) {}

    public function handle(TranscriptionService $service): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_TRANSCRIBING);

        $service->transcribe($video);

        NormalizeTranscriptJob::dispatch($this->videoId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('transcribe', $e->getMessage());
    }
}
