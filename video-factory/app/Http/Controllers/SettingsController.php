<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        $logPath = storage_path('logs/laravel.log');
        $logLines = File::exists($logPath)
            ? collect(explode("\n", trim(File::get($logPath))))->filter()->take(-80)->values()
            : collect();

        $checks = [
            'ffmpeg' => filled(shell_exec('command -v '.escapeshellarg(config('videofactory.ffmpeg_path', 'ffmpeg')))),
            'ffprobe' => filled(shell_exec('command -v '.escapeshellarg(config('videofactory.ffprobe_path', 'ffprobe')))),
            'openai' => filled(config('services.openai.api_key')),
            'youtube' => filled(config('services.youtube.client_id')) && filled(config('services.youtube.client_secret')),
            'tiktok' => filled(config('services.tiktok.client_key')) && filled(config('services.tiktok.client_secret')),
        ];

        return view('settings.index', compact('checks', 'logLines'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'silence_threshold_db' => 'required|numeric|min:-80|max:0',
            'silence_min_duration' => 'required|numeric|min:0.1|max:10',
            'llm_model' => 'required|string|max:100',
        ]);

        session()->flash('settings_form', $validated);

        return back()->with('success', '設定値を保存しました。ローカル環境では .env / config の更新も必要です。');
    }
}
