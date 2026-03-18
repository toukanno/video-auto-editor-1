@extends('layouts.app')

@section('title', '動画アップロード')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">動画アップロード</h1>

    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" x-data="{ uploading: false, filename: '' }" class="bg-white shadow-sm rounded-lg p-6 space-y-8">
        @csrf

        <section class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">動画ファイル</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition-colors" x-on:dragover.prevent="$el.classList.add('border-indigo-400')" x-on:dragleave="$el.classList.remove('border-indigo-400')" x-on:drop.prevent="$el.classList.remove('border-indigo-400'); $refs.fileInput.files = $event.dataTransfer.files; filename = $event.dataTransfer.files[0]?.name || ''">
                    <p class="text-sm text-gray-600" x-show="!filename">ここにドラッグ&ドロップ、またはクリックして選択</p>
                    <p class="text-sm text-indigo-600 font-medium" x-show="filename" x-text="filename"></p>
                    <p class="mt-1 text-xs text-gray-500">MP4, MOV, AVI 対応 / 最大2GB</p>
                    <input type="file" name="video" accept="video/mp4,video/quicktime,.mp4,.mov,.avi" x-ref="fileInput" @change="filename = $event.target.files[0]?.name || ''" class="hidden" required>
                    <button type="button" @click="$refs.fileInput.click()" class="mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">ファイルを選択</button>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">タイトル</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="動画のタイトル（空欄ならファイル名）">
                </div>
                <div>
                    <label for="processing_profile" class="block text-sm font-medium text-gray-700 mb-1">自動編集プロファイル</label>
                    <select name="processing_profile" id="processing_profile" class="w-full rounded-md border-gray-300 shadow-sm">
                        @foreach($profiles as $key => $label)
                            <option value="{{ $key }}" @selected(old('processing_profile', 'balanced') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="grid md:grid-cols-2 gap-6">
            <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                <h2 class="font-semibold text-gray-900">編集設定</h2>
                <label class="flex items-center"><input type="checkbox" name="generate_short" value="1" checked class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">ショート動画(9:16)を生成</span></label>
                <label class="flex items-center"><input type="checkbox" name="generate_long" value="1" class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">ロング動画(16:9)も生成</span></label>
                <label class="flex items-center"><input type="checkbox" name="enable_caption" value="1" checked class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">テロップ/字幕を入れる</span></label>
                <label class="flex items-center"><input type="checkbox" name="enable_silence_cut" value="1" checked class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">無音カットを有効化</span></label>
                <div>
                    <label for="resize_mode" class="block text-sm font-medium text-gray-700 mb-1">リサイズ方式</label>
                    <select name="resize_mode" id="resize_mode" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="short">縦動画優先</option>
                        <option value="long">横動画優先</option>
                        <option value="square">正方形</option>
                        <option value="original">元サイズ維持</option>
                    </select>
                </div>
                <div>
                    <label for="selected_caption_style_id" class="block text-sm font-medium text-gray-700 mb-1">テロップスタイル</label>
                    <select name="selected_caption_style_id" id="selected_caption_style_id" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">自動選択</option>
                        @foreach($captionStyles as $style)
                            <option value="{{ $style->id }}">{{ $style->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                <h2 class="font-semibold text-gray-900">音声・エクスポート設定</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">BGMファイル</label>
                    <input type="file" name="bgm" accept="audio/*" class="w-full text-sm text-gray-600">
                    <input type="range" name="bgm_volume" min="0" max="100" value="20" class="w-full mt-2">
                    <p class="text-xs text-gray-500">BGM音量</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SEファイル</label>
                    <input type="file" name="se" accept="audio/*" class="w-full text-sm text-gray-600">
                    <input type="range" name="se_volume" min="0" max="100" value="40" class="w-full mt-2">
                    <p class="text-xs text-gray-500">SE音量</p>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">形式</label>
                        <select name="export_format" class="w-full rounded-md border-gray-300 shadow-sm"><option value="mp4">MP4</option></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">映像ビットレート</label>
                        <input type="text" name="video_bitrate" value="{{ old('video_bitrate', $exportDefaults['video_bitrate']) }}" class="w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">音声ビットレート</label>
                        <input type="text" name="audio_bitrate" value="{{ old('audio_bitrate', $exportDefaults['audio_bitrate']) }}" class="w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">出力FPS</label>
                    <input type="number" name="target_fps" min="24" max="60" value="{{ old('target_fps', $exportDefaults['fps']) }}" class="w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>
        </section>

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
