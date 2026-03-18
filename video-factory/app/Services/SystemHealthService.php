<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class SystemHealthService
{
    public function checks(): array
    {
        return [
            'php' => [
                'label' => 'PHP',
                'ok' => true,
                'detail' => PHP_VERSION,
            ],
            'ffmpeg' => $this->binaryCheck(config('videofactory.ffmpeg_path', 'ffmpeg'), 'FFmpeg'),
            'ffprobe' => $this->binaryCheck(config('videofactory.ffprobe_path', 'ffprobe'), 'FFprobe'),
            'openai' => [
                'label' => 'OpenAI API キー',
                'ok' => filled(config('services.openai.api_key')),
                'detail' => filled(config('services.openai.api_key')) ? '設定済み' : '未設定',
            ],
            'youtube' => [
                'label' => 'YouTube API',
                'ok' => filled(config('services.youtube.client_id')) && filled(config('services.youtube.client_secret')),
                'detail' => filled(config('services.youtube.client_id')) && filled(config('services.youtube.client_secret')) ? '設定済み' : '未設定',
            ],
            'tiktok' => [
                'label' => 'TikTok API',
                'ok' => filled(config('services.tiktok.client_key')) && filled(config('services.tiktok.client_secret')),
                'detail' => filled(config('services.tiktok.client_key')) && filled(config('services.tiktok.client_secret')) ? '設定済み' : '未設定',
            ],
        ];
    }

    public function readyForPipeline(): bool
    {
        $checks = $this->checks();
        return $checks['ffmpeg']['ok'] && $checks['ffprobe']['ok'];
    }

    private function binaryCheck(string $binary, string $label): array
    {
        $result = Process::timeout(10)->run([$binary, '-version']);

        return [
            'label' => $label,
            'ok' => $result->successful(),
            'detail' => $result->successful()
                ? strtok(trim($result->output() ?: $result->errorOutput()), "\n")
                : 'コマンドを実行できませんでした',
        ];
    }
}
