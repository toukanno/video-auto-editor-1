@extends('layouts.app')

@section('title', '設定')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">設定</h1>
        <p class="text-sm text-gray-500 mt-1">外部連携の接続状況とローカル動作方針を確認できます。</p>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-4">ローカル実行モード</h2>
            <ul class="space-y-2 text-sm text-gray-700">
                <li>ログイン: デモユーザー自動ログイン</li>
                <li>キュー: `.env` で `QUEUE_CONNECTION=sync` を推奨</li>
                <li>文字起こし: APIキー未設定時は仮字幕にフォールバック</li>
                <li>動画処理: FFmpeg / FFprobe が必要</li>
            </ul>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-4">利用可能プロファイル</h2>
            <div class="space-y-2 text-sm text-gray-700">
                @foreach($profiles as $key => $label)
                    <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-2">
                        <span>{{ $label }}</span>
                        <code>{{ $key }}</code>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-4">投稿連携状況</h2>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="rounded border border-gray-100 p-4">
                <p class="font-medium text-gray-900">YouTube</p>
                <p class="text-gray-600 mt-1">{{ $accounts->has('youtube') ? '接続済み' : '未接続' }}</p>
            </div>
            <div class="rounded border border-gray-100 p-4">
                <p class="font-medium text-gray-900">TikTok</p>
                <p class="text-gray-600 mt-1">{{ $accounts->has('tiktok') ? '接続済み' : '未接続' }}</p>
            </div>
        </div>
        <p class="mt-4 text-xs text-gray-500">OAuth接続UI自体は未実装です。接続が必要な場合は `platform_accounts` テーブルへの登録または別途認証フロー追加が必要です。</p>
    </div>
</div>
@endsection
