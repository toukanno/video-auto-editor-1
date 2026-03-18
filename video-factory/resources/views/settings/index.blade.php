@extends('layouts.app')

@section('title', '設定 / ログ')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">設定 / システム状態</h1>
        <p class="mt-1 text-sm text-gray-500">ローカル動作に必要な依存関係・API 設定・アプリログをまとめて確認できます。</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($checks as $check)
            <div class="rounded-lg border {{ $check['ok'] ? 'border-green-200 bg-green-50' : 'border-yellow-200 bg-yellow-50' }} p-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900">{{ $check['label'] }}</h2>
                    <span class="text-xs font-medium {{ $check['ok'] ? 'text-green-700' : 'text-yellow-700' }}">{{ $check['ok'] ? 'OK' : '要設定' }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-600 break-all">{{ $check['detail'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-lg bg-white shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">セットアップメモ</h2>
        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
            <li>FFmpeg / FFprobe が未設定だと、動画解析・無音カット・レンダリングは開始されません。</li>
            <li>OpenAI API キーがない場合、文字起こしは実行できませんが、設定画面から不足を確認できます。</li>
            <li>YouTube / TikTok API は投稿時のみ必要です。未設定でも編集機能までは利用できます。</li>
        </ul>
    </div>

    <div class="rounded-lg bg-white shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">アプリログ</h2>
        <pre class="overflow-x-auto rounded-md bg-gray-950 p-4 text-xs text-green-200">{{ $logTail }}</pre>
    </div>
</div>
@endsection
