<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index(SystemHealthService $health)
    {
        $logPath = storage_path('logs/laravel.log');
        $logTail = File::exists($logPath)
            ? collect(explode(PHP_EOL, File::get($logPath)))->filter()->take(-80)->implode(PHP_EOL)
            : 'ログファイルはまだ生成されていません。';

        return view('settings.index', [
            'checks' => $health->checks(),
            'logTail' => $logTail,
        ]);
    }
}
