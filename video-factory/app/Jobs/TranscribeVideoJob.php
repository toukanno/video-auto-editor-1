<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Integrations\TranscriptionService;
use App\Services\Video\ProcessingLogService;
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

    public function handle(TranscriptionService $service, ProcessingLogService $logService): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_TRANSCRIBING);
        $logService->info($video, 'transcribe', '文字起こしを開始しました。');

        $result = $service->transcribe($video);
        $logService->info($video, 'transcribe', '文字起こしが完了しました。', $result);

        NormalizeTranscriptJob::dispatch($this->videoId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('transcribe', $e->getMessage());
        if ($video) app(ProcessingLogService::class)->error($video, 'transcribe', $e->getMessage());
    }
}
