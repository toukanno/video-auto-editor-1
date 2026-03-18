<?php

namespace App\Http\Controllers;

use App\Models\PlatformAccount;
use App\Models\Video;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $logPath = storage_path('logs/laravel.log');
        $logLines = File::exists($logPath)
            ? array_slice(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -80)
            : [];

        $platforms = PlatformAccount::where('user_id', $user->id)->get()->keyBy('platform');

        $videoStats = Video::where('user_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $configStatus = [
            'ffmpeg' => filled(config('videofactory.ffmpeg_path')),
            'ffprobe' => filled(config('videofactory.ffprobe_path')),
            'openai' => filled(config('services.openai.api_key')),
            'youtube' => filled(config('services.youtube.client_id')) && filled(config('services.youtube.client_secret')),
            'tiktok' => filled(config('services.tiktok.client_key')) && filled(config('services.tiktok.client_secret')),
        ];

        return view('settings.index', [
            'platforms' => $platforms,
            'videoStats' => $videoStats,
            'configStatus' => $configStatus,
            'logLines' => $logLines,
        ]);
    }
}
