@extends('layouts.app')

@section('title', '動画アップロード')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">動画アップロード</h1>

    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data"
          x-data="{ uploading: false, progress: 0, filename: '' }"
          class="bg-white shadow-sm rounded-lg p-6 space-y-6">
        @csrf

        <!-- Video File -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">動画ファイル</label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition-colors"
                 x-on:dragover.prevent="$el.classList.add('border-indigo-400')"
                 x-on:dragleave="$el.classList.remove('border-indigo-400')"
                 x-on:drop.prevent="$el.classList.remove('border-indigo-400'); $refs.fileInput.files = $event.dataTransfer.files; filename = $event.dataTransfer.files[0]?.name || ''">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-600" x-show="!filename">
                    ここにドラッグ&ドロップ、またはクリックして選択
                </p>
                <p class="mt-2 text-sm text-indigo-600 font-medium" x-show="filename" x-text="filename"></p>
                <p class="mt-1 text-xs text-gray-500">MP4, MOV 対応 / 最大2GB</p>
                <input type="file" name="video" accept="video/mp4,video/quicktime,.mp4,.mov"
                       x-ref="fileInput"
                       @change="filename = $event.target.files[0]?.name || ''"
                       class="hidden" required>
                <button type="button" @click="$refs.fileInput.click()" class="mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    ファイルを選択
                </button>
            </div>
        </div>

        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">タイトル</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                   placeholder="動画のタイトル（空欄の場合はファイル名を使用）">
        </div>

        <!-- Caption Style -->
        @if($captionStyles->isNotEmpty())
        <div>
            <label for="caption_style_id" class="block text-sm font-medium text-gray-700 mb-1">テロップスタイル</label>
            <select name="caption_style_id" id="caption_style_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">デフォルト</option>
                @foreach($captionStyles as $style)
                    <option value="{{ $style->id }}">{{ $style->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Options -->
        <div class="space-y-3">
            <label class="flex items-center">
                <input type="checkbox" name="render_short" value="1" checked
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">9:16 ショート動画を生成する</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="render_long" value="1"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">16:9 横長動画も生成する</span>
            </label>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('videos.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                キャンセル
            </a>
            <button type="submit" @click="uploading = true"
                    :disabled="uploading"
                    class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!uploading">アップロード開始</span>
                <span x-show="uploading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    アップロード中...
                </span>
            </button>
        </div>
    </form>
</div>
@endsection
