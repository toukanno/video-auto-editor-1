<?php

namespace App\Jobs;

use App\Models\PlatformAccount;
use App\Models\PublishTask;
use App\Models\RenderTask;
use App\Models\Video;
use App\Services\Integrations\YoutubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PublishYoutubeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 1800;

    public function __construct(public int $publishTaskId) {}

    public function handle(YoutubeService $service): void
    {
        $publishTask = PublishTask::findOrFail($this->publishTaskId);
        $renderTask = $publishTask->renderTask;
        $video = $renderTask->video;

        $publishTask->update(['status' => PublishTask::STATUS_PROCESSING]);
        $video->markStatus(Video::STATUS_PUBLISHING);

        $account = PlatformAccount::where('user_id', $video->user_id)
            ->where('platform', 'youtube')
            ->firstOrFail();

        if (empty($renderTask->output_path)) {
            throw new RuntimeException('レンダリング済みの動画ファイルパスが見つかりません。');
        }

        $videoPath = Storage::disk($video->storage_disk)->path($renderTask->output_path);

        if (!file_exists($videoPath)) {
            throw new RuntimeException("レンダリング済みの動画ファイルが存在しません: {$videoPath}");
        }

        $result = $service->upload($publishTask, $account, $videoPath);

        $publishTask->update([
            'status' => PublishTask::STATUS_COMPLETED,
            'external_id' => $result['external_id'],
            'external_url' => $result['external_url'],
            'response_json' => $result['response'],
            'published_at' => now(),
        ]);

        $video->markStatus(Video::STATUS_COMPLETED);
    }

    public function failed(Throwable $e): void
    {
        $publishTask = PublishTask::find($this->publishTaskId);
        $publishTask?->update([
            'status' => PublishTask::STATUS_FAILED,
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
