@extends('layouts.app')

@section('title', 'レンダリング結果一覧')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">レンダリング結果一覧</h1>

@if($renders->isEmpty())
    <div class="text-center py-16 bg-white rounded-lg shadow-sm">
        <p class="text-gray-500">レンダリング結果がありません。</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($renders as $render)
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <!-- Thumbnail -->
            @if($render->thumbnail_path && $render->status === 'completed')
                <div class="aspect-video bg-gray-900 flex items-center justify-center">
                    <video class="max-h-full" style="{{ $render->aspect_ratio === '9:16' ? 'max-width: 56%;' : 'width: 100%;' }}">
                        <source src="{{ route('renders.preview', $render) }}" type="video/mp4">
                    </video>
                </div>
            @else
                <div class="aspect-video bg-gray-100 flex items-center justify-center">
                    <x-status-badge :status="$render->status" />
                </div>
            @endif

            <div class="p-4">
                <h3 class="font-medium text-gray-900">{{ $render->video->title ?? '無題' }}</h3>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-sm text-gray-500">
                        {{ $render->render_type === 'short' ? 'ショート' : 'ロング' }}
                        / {{ $render->aspect_ratio }}
                    </span>
                    <x-status-badge :status="$render->status" />
                </div>

                @if($render->status === 'completed' && $render->output_path)
                    <div class="mt-3 flex space-x-2">
                        <a href="{{ route('renders.preview', $render) }}" target="_blank"
                           class="flex-1 text-center px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            プレビュー
                        </a>
                        <a href="{{ route('renders.download', $render) }}"
                           class="flex-1 text-center px-3 py-1.5 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                            ダウンロード
                        </a>
                    </div>
                @endif

                <!-- Publish status -->
                @foreach($render->publishTasks as $pt)
                    <div class="mt-2 flex items-center justify-between text-xs">
                        <span>{{ $pt->platform === 'youtube' ? 'YouTube' : 'TikTok' }}</span>
                        <x-status-badge :status="$pt->status" />
                    </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $renders->links() }}
    </div>
@endif
@endsection
