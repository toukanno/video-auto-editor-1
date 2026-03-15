@extends('layouts.app')

@section('title', 'テロップスタイル管理')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">テロップスタイル管理</h1>
    <a href="{{ route('caption-styles.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
        新規スタイル作成
    </a>
</div>

@if($styles->isEmpty())
    <div class="text-center py-16 bg-white rounded-lg shadow-sm">
        <p class="text-gray-500">テロップスタイルがありません。</p>
        <a href="{{ route('caption-styles.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            最初のスタイルを作成
        </a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($styles as $style)
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-3">{{ $style->name }}</h3>

            <!-- Preview -->
            <div class="bg-gray-900 rounded-lg p-4 mb-4 text-center" style="min-height: 80px; display: flex; align-items: flex-end; justify-content: center;">
                <span style="
                    color: {{ $style->font_color }};
                    font-size: {{ min($style->font_size / 3, 24) }}px;
                    -webkit-text-stroke: {{ $style->stroke_width / 2 }}px {{ $style->stroke_color }};
                    {{ $style->background_color ? 'background-color: ' . $style->background_color . '; padding: 2px 8px;' : '' }}
                ">
                    テロップのプレビュー
                </span>
            </div>

            <div class="text-sm text-gray-600 space-y-1">
                <p>フォント: {{ $style->font_family }}</p>
                <p>サイズ: {{ $style->font_size }}px</p>
                <p>1行文字数: {{ $style->chars_per_line }}文字</p>
                <p>最大行数: {{ $style->max_lines }}行</p>
            </div>

            <div class="mt-4 flex space-x-2">
                <a href="{{ route('caption-styles.edit', $style) }}" class="flex-1 text-center px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    編集
                </a>
                <form action="{{ route('caption-styles.duplicate', $style) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        複製
                    </button>
                </form>
                <form action="{{ route('caption-styles.destroy', $style) }}" method="POST"
                      onsubmit="return confirm('削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 border border-red-300 text-red-600 rounded-md text-sm hover:bg-red-50">
                        削除
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection
