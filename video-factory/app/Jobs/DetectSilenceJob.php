<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Video\ProcessingLogService;
use App\Services\Video\SilenceDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DetectSilenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public int $videoId) {}

    public function handle(SilenceDetectionService $service, ProcessingLogService $logService): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_DETECTING_SILENCE);

        $segments = $service->detect($video);
        $logService->info($video, 'detect_silence', '無音区間の解析が完了しました。', ['count' => count($segments)]);

        BuildCaptionFileJob::dispatch($this->videoId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('detect_silence', $e->getMessage());
        if ($video) app(ProcessingLogService::class)->error($video, 'detect_silence', $e->getMessage());
    }
}
