@extends('layouts.app')

@section('title', '設定 / ログ')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-4">実行環境チェック</h1>
            <div class="space-y-3 text-sm">
                @foreach($checks as $check)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">{{ $check['label'] }}</span>
                        <span class="px-2 py-1 rounded text-xs {{ $check['ok'] ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}" title="{{ $check['detail'] }}">
                            {{ $check['ok'] ? '利用可' : '未設定 / 未導入' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">自動編集設定</h2>
            <form action="{{ route('settings.update') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">無音判定しきい値 (dB)</label>
                    <input type="number" step="1" name="silence_threshold_db" value="{{ session('settings_form.silence_threshold_db', config('videofactory.silence_threshold_db')) }}" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">最小無音時間 (秒)</label>
                    <input type="number" step="0.1" name="silence_min_duration" value="{{ session('settings_form.silence_min_duration', config('videofactory.silence_min_duration')) }}" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">LLM モデル</label>
                    <input type="text" name="llm_model" value="{{ session('settings_form.llm_model', config('services.llm.model')) }}" class="w-full rounded-md border-gray-300">
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                    保存
                </button>
            </form>
            <p class="mt-3 text-xs text-gray-500">※ この画面はローカル確認向けです。永続反映には .env / config の更新が必要です。</p>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">アプリログ</h2>
        @if($logLines->isEmpty())
            <p class="text-sm text-gray-500">まだログがありません。</p>
        @else
            <div class="bg-gray-950 text-green-200 rounded-lg p-4 text-xs overflow-auto max-h-[720px] font-mono space-y-1">
                @foreach($logLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
