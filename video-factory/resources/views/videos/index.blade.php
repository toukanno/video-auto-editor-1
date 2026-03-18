@extends('layouts.app')

@section('title', '動画一覧')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">動画一覧</h1>
        <p class="text-sm text-gray-500 mt-1">アップロード、進捗確認、再実行、エクスポートの一連操作をここから行えます。</p>
    </div>
    <a href="{{ route('videos.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">新規アップロード</a>
</div>

@if($videos->isEmpty())
    <div class="text-center py-16 bg-white rounded-lg shadow-sm">
        <h3 class="mt-2 text-sm font-medium text-gray-900">動画がありません</h3>
        <p class="mt-1 text-sm text-gray-500">最初の動画をアップロードしましょう。</p>
        <div class="mt-6"><a href="{{ route('videos.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">アップロード</a></div>
    </div>
@else
    <div class="space-y-4">
        @foreach($videos as $video)
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('videos.show', $video) }}" class="text-lg font-semibold text-indigo-600 hover:text-indigo-900">{{ $video->title ?? '無題' }}</a>
                            <x-status-badge :status="$video->status" />
                        </div>
                        <p class="text-sm text-gray-500">{{ $video->source_filename }}</p>
                        <div class="grid md:grid-cols-4 gap-3 text-sm text-gray-600">
                            <div>進捗: {{ $video->progressPercent() }}%</div>
                            <div>時間: {{ $video->duration_sec ? gmdate('H:i:s', (int) $video->duration_sec) : '-' }}</div>
                            <div>プロファイル: {{ $video->processing_profile }}</div>
                            <div>最終処理: {{ $video->last_processed_at?->format('Y/m/d H:i') ?? '-' }}</div>
                        </div>
                        <div class="w-full max-w-xl bg-gray-100 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $video->isFailed() ? 100 : $video->progressPercent() }}%"></div>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                            @if($video->processingOption('generate_short', true)) <span class="px-2 py-1 rounded bg-gray-100">ショート出力</span> @endif
                            @if($video->processingOption('generate_long', false)) <span class="px-2 py-1 rounded bg-gray-100">ロング出力</span> @endif
                            @if($video->processingOption('enable_caption', true)) <span class="px-2 py-1 rounded bg-gray-100">字幕あり</span> @endif
                            @if($video->processingOption('enable_silence_cut', true)) <span class="px-2 py-1 rounded bg-gray-100">無音カット</span> @endif
                            <span class="px-2 py-1 rounded bg-gray-100">リサイズ: {{ $video->processingOption('resize_mode', 'short') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('videos.show', $video) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">詳細</a>
                        @if($video->isFailed())
                            <form action="{{ route('videos.rerun', $video) }}" method="POST">@csrf<button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-md text-sm">再実行</button></form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $videos->links() }}</div>
@endif
@endsection
