@extends('layouts.app')

@section('title', 'テロップスタイル作成')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">テロップスタイル作成</h1>

    <form action="{{ route('caption-styles.store') }}" method="POST" class="bg-white shadow-sm rounded-lg p-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">スタイル名</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                   placeholder="例: ポップ系、ニュース風">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="font_family" class="block text-sm font-medium text-gray-700 mb-1">フォント</label>
                <select name="font_family" id="font_family" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="Noto Sans JP">Noto Sans JP</option>
                    <option value="M PLUS Rounded 1c">M PLUS Rounded 1c</option>
                    <option value="Kosugi Maru">Kosugi Maru</option>
                    <option value="Sawarabi Gothic">Sawarabi Gothic</option>
                </select>
            </div>
            <div>
                <label for="font_size" class="block text-sm font-medium text-gray-700 mb-1">フォントサイズ</label>
                <input type="number" name="font_size" id="font_size" value="{{ old('font_size', 48) }}" min="12" max="120"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label for="font_color" class="block text-sm font-medium text-gray-700 mb-1">文字色</label>
                <input type="color" name="font_color" id="font_color" value="{{ old('font_color', '#FFFFFF') }}"
                       class="w-full h-10 rounded-md border-gray-300">
            </div>
            <div>
                <label for="stroke_color" class="block text-sm font-medium text-gray-700 mb-1">縁取り色</label>
                <input type="color" name="stroke_color" id="stroke_color" value="{{ old('stroke_color', '#000000') }}"
                       class="w-full h-10 rounded-md border-gray-300">
            </div>
            <div>
                <label for="background_color" class="block text-sm font-medium text-gray-700 mb-1">背景色</label>
                <input type="color" name="background_color" id="background_color" value="{{ old('background_color', '#000000') }}"
                       class="w-full h-10 rounded-md border-gray-300">
                <label class="flex items-center mt-1">
                    <input type="checkbox" onchange="document.getElementById('background_color').disabled = this.checked; if(this.checked) document.getElementById('background_color').value = '';"
                           class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-1 text-xs text-gray-500">なし</span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="stroke_width" class="block text-sm font-medium text-gray-700 mb-1">縁取り太さ</label>
                <input type="range" name="stroke_width" id="stroke_width" value="{{ old('stroke_width', 3) }}" min="0" max="10" step="1"
                       class="w-full" oninput="this.nextElementSibling.textContent = this.value + 'px'">
                <span class="text-xs text-gray-500">3px</span>
            </div>
            <div>
                <label for="position_y" class="block text-sm font-medium text-gray-700 mb-1">縦位置（上から%）</label>
                <input type="range" name="position_y" id="position_y" value="{{ old('position_y', 85) }}" min="10" max="95" step="5"
                       class="w-full" oninput="this.nextElementSibling.textContent = this.value + '%'">
                <span class="text-xs text-gray-500">85%</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="chars_per_line" class="block text-sm font-medium text-gray-700 mb-1">1行文字数</label>
                <input type="number" name="chars_per_line" id="chars_per_line" value="{{ old('chars_per_line', 18) }}" min="5" max="40"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="max_lines" class="block text-sm font-medium text-gray-700 mb-1">最大行数</label>
                <select name="max_lines" id="max_lines" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="1">1行</option>
                    <option value="2" selected>2行</option>
                    <option value="3">3行</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('caption-styles.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                キャンセル
            </a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                作成する
            </button>
        </div>
    </form>
</div>
@endsection
