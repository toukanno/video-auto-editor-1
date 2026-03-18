@extends('layouts.app')

@section('title', '動画アップロード')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">動画アップロード</h1>
        <p class="text-sm text-gray-500">動画アップロード時に、字幕・無音カット・BGM・リサイズ設定までまとめて保存できます。</p>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach($checks as $check)
            <div class="rounded-lg border {{ $check['ok'] ? 'border-green-200 bg-green-50' : 'border-yellow-200 bg-yellow-50' }} p-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">{{ $check['label'] }}</span>
                    <span class="text-xs {{ $check['ok'] ? 'text-green-700' : 'text-yellow-700' }}">{{ $check['ok'] ? 'OK' : '未設定' }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-600 break-all">{{ $check['detail'] }}</p>
            </div>
        @endforeach
    </div>

    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" x-data="{ uploading: false, filename: '', bgmFilename: '' }" class="bg-white shadow-sm rounded-lg p-6 space-y-6">
        @csrf

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">動画ファイル</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition-colors" x-on:dragover.prevent="$el.classList.add('border-indigo-400')" x-on:dragleave="$el.classList.remove('border-indigo-400')" x-on:drop.prevent="$el.classList.remove('border-indigo-400'); $refs.fileInput.files = $event.dataTransfer.files; filename = $event.dataTransfer.files[0]?.name || ''">
                        <p class="text-sm text-gray-600" x-show="!filename">ここにドラッグ&ドロップ、またはクリックして選択</p>
                        <p class="text-sm text-indigo-600 font-medium" x-show="filename" x-text="filename"></p>
                        <p class="mt-1 text-xs text-gray-500">MP4 / MOV / AVI、最大 2GB</p>
                        <input type="file" name="video" accept="video/mp4,video/quicktime,video/x-msvideo,.mp4,.mov,.avi" x-ref="fileInput" @change="filename = $event.target.files[0]?.name || ''" class="hidden" required>
                        <button type="button" @click="$refs.fileInput.click()" class="mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">ファイルを選択</button>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">タイトル</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="空欄の場合はファイル名を使用します">
                </div>

                <div>
                    <label for="bgm" class="block text-sm font-medium text-gray-700 mb-1">BGM / SE 素材（任意）</label>
                    <input type="file" name="bgm" id="bgm" accept="audio/mpeg,audio/mp4,audio/wav,.mp3,.m4a,.wav" @change="bgmFilename = $event.target.files[0]?.name || ''" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700">
                    <p class="mt-1 text-xs text-gray-500" x-show="bgmFilename">選択中: <span x-text="bgmFilename"></span></p>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">自動編集設定</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="target_aspect_ratio" class="block text-sm font-medium text-gray-700 mb-1">出力比率</label>
                            <select name="target_aspect_ratio" id="target_aspect_ratio" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="9:16">9:16 ショート</option>
                                <option value="16:9">16:9 横動画</option>
                                <option value="1:1">1:1 スクエア</option>
                            </select>
                        </div>
                        <div>
                            <label for="bgm_volume" class="block text-sm font-medium text-gray-700 mb-1">BGM 音量 (%)</label>
                            <input type="number" name="bgm_volume" id="bgm_volume" value="{{ old('bgm_volume', 15) }}" min="0" max="100" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="caption_style_id" class="block text-sm font-medium text-gray-700 mb-1">テロップスタイル</label>
                    <select name="caption_style_id" id="caption_style_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">デフォルト</option>
                        @foreach($captionStyles as $style)
                            <option value="{{ $style->id }}">{{ $style->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                    <label class="flex items-center"><input type="checkbox" name="render_short" value="1" checked class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm text-gray-700">ショート向けとして処理を最適化する</span></label>
                    <label class="flex items-center"><input type="checkbox" name="enable_captions" value="1" checked class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm text-gray-700">テロップ / 字幕を生成する</span></label>
                    <label class="flex items-center"><input type="checkbox" name="cut_silence" value="1" checked class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm text-gray-700">無音カットを有効にする</span></label>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('videos.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">キャンセル</a>
            <button type="submit" @click="uploading = true" :disabled="uploading" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
                <span x-show="!uploading">アップロード開始</span>
                <span x-show="uploading">アップロード中...</span>
            </button>
        </div>
    </form>
</div>
@endsection
