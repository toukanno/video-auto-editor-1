<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Upload Limits
    |--------------------------------------------------------------------------
    */
    'max_file_size_mb' => env('VIDEO_MAX_FILE_SIZE_MB', 2048),
    'max_duration_sec' => env('VIDEO_MAX_DURATION_SEC', 1800), // 30 minutes
    'allowed_mimes' => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Settings
    |--------------------------------------------------------------------------
    */
    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),

    /*
    |--------------------------------------------------------------------------
    | Silence Detection
    |--------------------------------------------------------------------------
    */
    'silence_threshold_db' => env('SILENCE_THRESHOLD_DB', -30),
    'silence_min_duration' => env('SILENCE_MIN_DURATION', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Caption Defaults
    |--------------------------------------------------------------------------
    */
    'caption' => [
        'font_family' => 'Noto Sans JP',
        'font_size' => 48,
        'font_color' => '#FFFFFF',
        'stroke_color' => '#000000',
        'stroke_width' => 3,
        'position_y' => 85,
        'max_lines' => 2,
        'chars_per_line' => 18,
    ],

    /*
    |--------------------------------------------------------------------------
    | Render Defaults
    |--------------------------------------------------------------------------
    */
    'render' => [
        'short' => [
            'width' => 1080,
            'height' => 1920,
            'aspect_ratio' => '9:16',
        ],
        'long' => [
            'width' => 1920,
            'height' => 1080,
            'aspect_ratio' => '16:9',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM for Caption Normalization
    |--------------------------------------------------------------------------
    */
    'llm' => [
        'base_url' => env('LLM_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('LLM_MODEL', 'gpt-4o-mini'),
    ],
];
