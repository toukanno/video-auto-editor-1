@extends('layouts.app')

@section('title', '動画一覧')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">動画一覧</h1>
        <p class="text-sm text-gray-500 mt-1">保存済みの設定・進捗・出力状況を一覧で確認できます。</p>
    </div>
    <a href="{{ route('videos.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">新規アップロード</a>
</div>

@if($videos->isEmpty())
    <div class="text-center py-16 bg-white rounded-lg shadow-sm">
        <h3 class="mt-2 text-sm font-medium text-gray-900">動画がありません</h3>
        <p class="mt-1 text-sm text-gray-500">最初の動画をアップロードしましょう。</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($videos as $video)
            <div class="bg-white shadow-sm rounded-lg p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('videos.show', $video) }}" class="text-lg font-semibold text-indigo-600 hover:text-indigo-800">{{ $video->title ?? '無題' }}</a>
                            <x-status-badge :status="$video->status" />
                        </div>
                        <p class="text-sm text-gray-500">{{ $video->source_filename }}</p>
                        <div class="grid gap-2 text-sm text-gray-600 md:grid-cols-4">
                            <div>比率: <span class="font-medium text-gray-900">{{ $video->target_aspect_ratio }}</span></div>
                            <div>字幕: <span class="font-medium text-gray-900">{{ $video->enable_captions ? '有効' : '無効' }}</span></div>
                            <div>無音カット: <span class="font-medium text-gray-900">{{ $video->cut_silence ? '有効' : '無効' }}</span></div>
                            <div>BGM: <span class="font-medium text-gray-900">{{ $video->bgm_path ? 'あり' : 'なし' }}</span></div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                <span>ジョブ進捗</span>
                                <span>{{ $video->progressPercent() }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-200 overflow-hidden"><div class="h-full bg-indigo-500" style="width: {{ $video->progressPercent() }}%"></div></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <a href="{{ route('videos.show', $video) }}" class="text-indigo-600 hover:text-indigo-800">詳細</a>
                        @if($video->isFailed())
                            <form action="{{ route('videos.rerun', $video) }}" method="POST">@csrf<button type="submit" class="text-yellow-600 hover:text-yellow-800">再実行</button></form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $videos->links() }}</div>
@endif
@endsection
