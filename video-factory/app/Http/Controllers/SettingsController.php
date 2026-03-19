<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index(SystemHealthService $healthService)
    {
        $logPath = storage_path('logs/laravel.log');
        $logLines = File::exists($logPath)
            ? collect(explode("\n", trim(File::get($logPath))))->filter()->take(-80)->values()
            : collect();

        $checks = $healthService->checks();

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
