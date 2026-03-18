<?php

namespace App\Support;

class VideoProcessingDefaults
{
    public static function processingOptions(): array
    {
        return [
            'generate_short' => true,
            'generate_long' => false,
            'enable_caption' => true,
            'enable_silence_cut' => true,
            'resize_mode' => 'short',
            'bgm_path' => null,
            'bgm_volume' => 20,
            'se_path' => null,
            'se_volume' => 40,
            'caption_preset' => 'default',
        ];
    }

    public static function exportOptions(): array
    {
        return [
            'format' => 'mp4',
            'video_bitrate' => '4M',
            'audio_bitrate' => '128k',
            'fps' => 30,
        ];
    }

    public static function profiles(): array
    {
        return [
            'fast' => '速度優先',
            'balanced' => '標準',
            'quality' => '品質優先',
        ];
    }
}
