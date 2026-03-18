@extends('layouts.app')

@section('title', $video->title ?? '動画詳細')

@section('content')
<div class="mb-6">
    <a href="{{ route('videos.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; 動画一覧へ戻る</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Header -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $video->title ?? '無題' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $video->source_filename }}</p>
                </div>
                <x-status-badge :status="$video->status" />
            </div>

            @if($video->error_message)
                <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
                    <p class="text-sm text-red-700">{{ $video->error_message }}</p>
                    <form action="{{ route('videos.rerun', $video) }}" method="POST" class="mt-2">
                        @csrf
                        <input type="hidden" name="step" value="{{ $video->last_failed_step }}">
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">失敗ステップから再実行</button>
                    </form>
                </div>
            @endif

            <div class="mt-4 grid grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">時間</span>
                    <p class="font-medium">{{ $video->duration_sec ? gmdate('H:i:s', (int) $video->duration_sec) : '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">解像度</span>
                    <p class="font-medium">{{ $video->width && $video->height ? "{$video->width}x{$video->height}" : '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">FPS</span>
                    <p class="font-medium">{{ $video->fps ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">作成日</span>
                    <p class="font-medium">{{ $video->created_at->format('Y/m/d H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">編集設定</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">テロップスタイル</span>
                    <p class="font-medium">{{ $video->preferredCaptionStyle?->name ?? 'デフォルト' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">無音カット</span>
                    <p class="font-medium">{{ $video->shouldAutoCutSilence() ? '有効' : '無効' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">生成フォーマット</span>
                    <p class="font-medium">{{ collect([$video->shouldRenderShort() ? '9:16ショート' : null, $video->shouldRenderLong() ? '16:9ロング' : null])->filter()->implode(' / ') ?: '9:16ショート' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">再実行</span>
                    <p class="font-medium">保存済み設定を使って再処理します</p>
                </div>
            </div>
        </div>

        <!-- Processing Pipeline -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">処理パイプライン</h2>
            @php
                $steps = [
                    ['key' => 'extract_audio', 'label' => '音声抽出', 'status' => 'extracting_audio'],
                    ['key' => 'transcribe', 'label' => '文字起こし', 'status' => 'transcribing'],
                    ['key' => 'normalize', 'label' => 'テロップ整形', 'status' => 'normalizing'],
                    ['key' => 'detect_silence', 'label' => '無音検出', 'status' => 'detecting_silence'],
                    ['key' => 'build_caption', 'label' => '字幕生成', 'status' => 'building_caption'],
                    ['key' => 'render', 'label' => 'レンダリング', 'status' => 'rendering'],
                ];
                $statusOrder = ['uploaded', 'extracting_audio', 'transcribing', 'normalizing', 'detecting_silence', 'building_caption', 'rendering', 'rendered', 'publishing', 'completed', 'failed'];
                $currentIdx = array_search($video->status, $statusOrder);
            @endphp
            <div class="space-y-3">
                @foreach($steps as $i => $step)
                    @php
                        $stepIdx = array_search($step['status'], $statusOrder);
                        if ($video->status === 'failed' && $video->last_failed_step === $step['key']) {
                            $stepState = 'failed';
                        } elseif ($currentIdx > $stepIdx) {
                            $stepState = 'done';
                        } elseif ($currentIdx === $stepIdx) {
                            $stepState = 'active';
                        } else {
                            $stepState = 'pending';
                        }
                    @endphp
                    <div class="flex items-center space-x-3">
                        @if($stepState === 'done')
                            <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        @elseif($stepState === 'active')
                            <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                        @elseif($stepState === 'failed')
                            <div class="w-6 h-6 rounded-full bg-red-500 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full bg-gray-200"></div>
                        @endif
                        <span class="text-sm {{ $stepState === 'active' ? 'font-medium text-blue-700' : ($stepState === 'failed' ? 'font-medium text-red-700' : 'text-gray-600') }}">
                            {{ $step['label'] }}
                        </span>
                        @if($stepState === 'failed')
                            <form action="{{ route('videos.rerun', $video) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="step" value="{{ $step['key'] }}">
                                <button type="submit" class="text-xs text-red-600 hover:text-red-800">再実行</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Transcript -->
        @if($video->transcriptSegments->isNotEmpty())
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">文字起こし結果</h2>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($video->transcriptSegments as $seg)
                <div class="flex items-start space-x-3 p-2 rounded hover:bg-gray-50">
                    <span class="text-xs text-gray-400 whitespace-nowrap mt-0.5">
                        {{ gmdate('i:s', (int) ($seg->start_ms / 1000)) }}
                    </span>
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

        <!-- Silence Segments -->
        @if($video->silenceSegments->isNotEmpty())
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">無音区間 ({{ $video->silenceSegments->count() }}箇所)</h2>
            <div class="grid grid-cols-3 gap-2 text-sm">
                @foreach($video->silenceSegments->take(20) as $seg)
                <div class="bg-gray-50 rounded p-2">
                    <span class="text-gray-500">{{ gmdate('i:s', (int) ($seg->start_ms / 1000)) }} - {{ gmdate('i:s', (int) ($seg->end_ms / 1000)) }}</span>
                    <span class="text-gray-400 text-xs ml-1">({{ number_format($seg->duration_ms / 1000, 1) }}s)</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Right: Renders & Publishing -->
    <div class="space-y-6">
        <!-- Render Tasks -->
        @foreach($video->renderTasks as $renderTask)
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">
                    {{ $renderTask->render_type === 'short' ? 'ショート動画' : 'ロング動画' }}
                    ({{ $renderTask->aspect_ratio }})
                </h3>
                <x-status-badge :status="$renderTask->status" />
            </div>

            @if($renderTask->status === 'completed' && $renderTask->output_path)
                <div class="space-y-3">
                    <!-- Preview -->
                    <video controls class="w-full rounded-lg bg-black"
                           style="{{ $renderTask->aspect_ratio === '9:16' ? 'max-height: 400px;' : '' }}">
                        <source src="{{ route('renders.preview', $renderTask) }}" type="video/mp4">
                    </video>

                    <!-- Download -->
                    <a href="{{ route('renders.download', $renderTask) }}"
                       class="block text-center w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ダウンロード
                    </a>

                    <!-- YouTube Publish -->
                    <div x-data="{ showYt: false }">
                        <button @click="showYt = !showYt"
                                class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            YouTube に投稿
                        </button>
                        <form x-show="showYt" action="{{ route('videos.publish.youtube', $video) }}" method="POST" class="mt-3 space-y-2">
                            @csrf
                            <input type="hidden" name="render_task_id" value="{{ $renderTask->id }}">
                            <input type="text" name="title" value="{{ $video->title }}" placeholder="タイトル"
                                   class="w-full rounded-md border-gray-300 text-sm" required>
                            <textarea name="description" placeholder="説明文" rows="3"
                                      class="w-full rounded-md border-gray-300 text-sm"></textarea>
                            <input type="text" name="tags" placeholder="タグ（カンマ区切り）"
                                   class="w-full rounded-md border-gray-300 text-sm">
                            <select name="privacy_status" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="private">非公開</option>
                                <option value="unlisted">限定公開</option>
                                <option value="public">公開</option>
                            </select>
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">
                                投稿する
                            </button>
                        </form>
                    </div>

                    <!-- TikTok Publish -->
                    <div x-data="{ showTt: false }">
                        <button @click="showTt = !showTt"
                                class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                            TikTok に下書き
                        </button>
                        <form x-show="showTt" action="{{ route('videos.publish.tiktok', $video) }}" method="POST" class="mt-3 space-y-2">
                            @csrf
                            <input type="hidden" name="render_task_id" value="{{ $renderTask->id }}">
                            <input type="text" name="title" value="{{ $video->title }}" placeholder="タイトル"
                                   class="w-full rounded-md border-gray-300 text-sm">
                            <select name="privacy_status" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="private">下書き（非公開）</option>
                                <option value="public">公開</option>
                            </select>
                            <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800">
                                送信する
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Publish Tasks -->
            @foreach($renderTask->publishTasks as $pt)
            <div class="mt-3 p-3 bg-gray-50 rounded-md">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium">
                        {{ $pt->platform === 'youtube' ? 'YouTube' : 'TikTok' }}
                    </span>
                    <x-status-badge :status="$pt->status" />
                </div>
                @if($pt->external_url)
                    <a href="{{ $pt->external_url }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 mt-1 block">
                        {{ $pt->external_url }}
                    </a>
                @endif
                @if($pt->error_message)
                    <p class="text-xs text-red-600 mt-1">{{ $pt->error_message }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach

        <!-- Actions -->
        <div class="bg-white shadow-sm rounded-lg p-6 space-y-3">
            <h3 class="font-semibold text-gray-900">操作</h3>

            @if($video->isFailed())
                <form action="{{ route('videos.rerun', $video) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-md hover:bg-yellow-600">
                        失敗ステップから再実行
                    </button>
                </form>
            @endif

            <form action="{{ route('videos.rerun', $video) }}" method="POST">
                @csrf
                <input type="hidden" name="step" value="extract_audio">
                <button type="submit" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    最初から再処理
                </button>
            </form>

            <form action="{{ route('videos.destroy', $video) }}" method="POST"
                  onsubmit="return confirm('この動画を削除しますか？')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-md text-sm font-medium hover:bg-red-50">
                    削除
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
