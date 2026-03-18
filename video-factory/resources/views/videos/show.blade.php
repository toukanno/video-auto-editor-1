@extends('layouts.app')

@section('title', $video->title ?? '動画詳細')

@section('content')
<div class="mb-6"><a href="{{ route('videos.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; 動画一覧へ戻る</a></div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $video->title ?? '無題' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $video->source_filename }}</p>
                </div>
                <x-status-badge :status="$video->status" />
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1"><span>処理進捗</span><span>{{ $video->progressPercent() }}%</span></div>
                <div class="h-2 rounded-full bg-gray-200 overflow-hidden"><div class="h-full bg-indigo-500" style="width: {{ $video->progressPercent() }}%"></div></div>
            </div>
            @if($video->error_message)
                <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
                    <p class="text-sm text-red-700">{{ $video->error_message }}</p>
                    <form action="{{ route('videos.rerun', $video) }}" method="POST" class="mt-2">@csrf<input type="hidden" name="step" value="{{ $video->last_failed_step }}"><button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">失敗ステップから再実行</button></form>
                </div>
            @endif
            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500">出力比率</span><p class="font-medium">{{ $video->target_aspect_ratio }}</p></div>
                <div><span class="text-gray-500">字幕</span><p class="font-medium">{{ $video->enable_captions ? '有効' : '無効' }}</p></div>
                <div><span class="text-gray-500">無音カット</span><p class="font-medium">{{ $video->cut_silence ? '有効' : '無効' }}</p></div>
                <div><span class="text-gray-500">BGM</span><p class="font-medium">{{ $video->bgm_path ? 'あり (' . $video->bgm_volume . '%)' : 'なし' }}</p></div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">保存済みの自動編集設定</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="rounded-lg bg-gray-50 p-4">テロップスタイル: <span class="font-medium text-gray-900">{{ $video->selectedCaptionStyle->name ?? 'デフォルト' }}</span></div>
                <div class="rounded-lg bg-gray-50 p-4">再実行: 画面右側のボタンから前回設定のまま再処理できます。</div>
            </div>
        </div>

        @if($video->transcriptSegments->isNotEmpty())
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">文字起こし / 字幕結果</h2>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($video->transcriptSegments as $seg)
                <div class="flex items-start space-x-3 p-2 rounded hover:bg-gray-50">
                    <span class="text-xs text-gray-400 whitespace-nowrap mt-0.5">{{ gmdate('i:s', (int) ($seg->start_ms / 1000)) }}</span>
                    <div>
                        <p class="text-sm text-gray-900">{{ $seg->displayText() }}</p>
                        @if($seg->text_normalized && $seg->text_normalized !== $seg->text_raw)
                            <p class="text-xs text-gray-400 line-through mt-0.5">{{ $seg->text_raw }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($video->silenceSegments->isNotEmpty())
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">無音区間 ({{ $video->silenceSegments->count() }}箇所)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                @foreach($video->silenceSegments->take(20) as $seg)
                    <div class="bg-gray-50 rounded p-2">{{ gmdate('i:s', (int) ($seg->start_ms / 1000)) }} - {{ gmdate('i:s', (int) ($seg->end_ms / 1000)) }} <span class="text-gray-400">({{ number_format($seg->duration_ms / 1000, 1) }}s)</span></div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="space-y-6">
        @forelse($video->renderTasks as $renderTask)
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">{{ $renderTask->render_type === 'short' ? 'ショート動画' : '出力動画' }} ({{ $renderTask->aspect_ratio }})</h3>
                <x-status-badge :status="$renderTask->status" />
            </div>
            @if($renderTask->status === 'completed' && $renderTask->output_path)
                <video controls class="w-full rounded-lg bg-black" style="{{ $renderTask->aspect_ratio === '9:16' ? 'max-height: 400px;' : '' }}"><source src="{{ route('renders.preview', $renderTask) }}" type="video/mp4"></video>
                <a href="{{ route('renders.download', $renderTask) }}" class="mt-3 block text-center w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">エクスポートをダウンロード</a>
            @else
                <p class="text-sm text-gray-500">レンダリング完了後にプレビューとダウンロードが利用できます。</p>
            @endif
            @foreach($renderTask->publishTasks as $pt)
                <div class="mt-3 p-3 bg-gray-50 rounded-md"><div class="flex justify-between items-center"><span class="text-sm font-medium">{{ $pt->platform === 'youtube' ? 'YouTube' : 'TikTok' }}</span><x-status-badge :status="$pt->status" /></div>@if($pt->error_message)<p class="text-xs text-red-600 mt-1">{{ $pt->error_message }}</p>@endif</div>
            @endforeach
        </div>
        @empty
        <div class="bg-white shadow-sm rounded-lg p-6 text-sm text-gray-500">まだレンダリングジョブは作成されていません。</div>
        @endforelse

        <div class="bg-white shadow-sm rounded-lg p-6 space-y-3">
            <h3 class="font-semibold text-gray-900">操作</h3>
            <form action="{{ route('videos.rerun', $video) }}" method="POST">@csrf<button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-md hover:bg-yellow-600">保存済み設定で再実行</button></form>
            <form action="{{ route('videos.rerun', $video) }}" method="POST">@csrf<input type="hidden" name="step" value="extract_audio"><button type="submit" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">最初から再処理</button></form>
            <form action="{{ route('videos.destroy', $video) }}" method="POST" onsubmit="return confirm('この動画を削除しますか？')">@csrf @method('DELETE')<button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-md text-sm font-medium hover:bg-red-50">削除</button></form>
        </div>
    </div>
</div>
@endsection
