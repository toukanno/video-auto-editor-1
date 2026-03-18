<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Caption\TranscriptNormalizerService;
use App\Services\Video\ProcessingLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class NormalizeTranscriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public int $videoId) {}

    public function handle(TranscriptNormalizerService $service, ProcessingLogService $logService): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->markStatus(Video::STATUS_NORMALIZING);

        try {
            $service->normalize($video);
            $logService->info($video, 'normalize', '字幕テキストを整形しました。');
        } catch (Throwable $e) {
            $service->normalizeWithRules($video);
            $logService->warning($video, 'normalize', 'LLM整形に失敗したため、ルールベース整形へフォールバックしました。', ['reason' => $e->getMessage()]);
        }

        DetectSilenceJob::dispatch($this->videoId);
    }

    public function failed(Throwable $e): void
    {
        $video = Video::find($this->videoId);
        $video?->markFailed('normalize', $e->getMessage());
        if ($video) app(ProcessingLogService::class)->error($video, 'normalize', $e->getMessage());
    }
}
