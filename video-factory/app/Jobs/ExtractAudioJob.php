<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Video\AudioExtractService;
use App\Services\Video\ProcessingLogService;
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

    public function handle(AudioExtractService $service, ProcessingLogService $logService): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_EXTRACTING_AUDIO);
        $logService->info($video, 'extract_audio', '音声抽出とメタデータ解析を開始しました。');

        $meta = $service->probe($video);
        $video->update($meta);

        $audioPath = $service->extract($video);
        $video->update(['audio_path' => $audioPath]);
        $logService->info($video, 'extract_audio', '音声抽出が完了しました。', $meta);

        TranscribeVideoJob::dispatch($this->videoId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('extract_audio', $e->getMessage());
        if ($video) app(ProcessingLogService::class)->error($video, 'extract_audio', $e->getMessage());
    }
}
