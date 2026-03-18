@extends('layouts.app')

@section('title', '設定とログ')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">設定とログ</h1>
            <p class="text-sm text-gray-500 mt-1">動画処理の接続状態、保存済みアカウント、最近のログをまとめて確認できます。</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">システム接続状態</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @foreach(['ffmpeg' => 'FFmpeg', 'ffprobe' => 'FFprobe', 'openai' => 'OpenAI / Whisper', 'youtube' => 'YouTube API', 'tiktok' => 'TikTok API'] as $key => $label)
                        <div class="border rounded-lg p-4 flex items-center justify-between">
                            <span class="font-medium text-gray-700">{{ $label }}</span>
                            <x-status-badge :status="$configStatus[$key] ? 'completed' : 'failed'" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">ジョブ状況</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($videoStats as $status => $total)
                        <div class="border rounded-lg p-4">
                            <div class="mb-2"><x-status-badge :status="$status" /></div>
                            <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">アプリケーションログ</h2>
                    <span class="text-xs text-gray-500">最新 {{ count($logLines) }} 行</span>
                </div>
                <pre class="bg-gray-950 text-green-200 rounded-lg p-4 text-xs overflow-x-auto max-h-[32rem]">{{ implode("
", $logLines ?: ['ログはまだ出力されていません。']) }}</pre>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">保存済み投稿アカウント</h2>
                <div class="space-y-3 text-sm">
                    @foreach(['youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $key => $label)
                        @php($account = $platforms->get($key))
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-700">{{ $label }}</span>
                                <x-status-badge :status="$account ? 'completed' : 'pending'" />
                            </div>
                            <p class="mt-2 text-gray-500">
                                @if($account)
                                    {{ $account->account_name ?: 'アカウント名未設定' }}
                                @else
                                    まだ接続されていません。
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">セットアップのヒント</h2>
                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-600">
                    <li>.env に API キーと OAuth 情報を設定してください。</li>
                    <li>バックグラウンド処理は <code>php artisan queue:listen</code> で実行します。</li>
                    <li>FFmpeg / FFprobe を PATH 上に置くか .env でパスを指定してください。</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
