<?php

namespace App\Services\Video;

use App\Models\Video;

class ProcessingLogService
{
    public function info(Video $video, string $step, string $message, array $context = []): void
    {
        $this->write($video, $step, 'info', $message, $context);
    }

    public function warning(Video $video, string $step, string $message, array $context = []): void
    {
        $this->write($video, $step, 'warning', $message, $context);
    }

    public function error(Video $video, string $step, string $message, array $context = []): void
    {
        $this->write($video, $step, 'error', $message, $context);
    }

    public function write(Video $video, string $step, string $level, string $message, array $context = []): void
    {
        $video->processingLogs()->create([
            'step' => $step,
            'level' => $level,
            'message' => $message,
            'context_json' => $context ?: null,
        ]);
    }
}
