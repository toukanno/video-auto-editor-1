@extends('layouts.app')

@section('title', $video->title ?? '動画詳細')

@section('content')
<div class="mb-6">
    <a href="{{ route('videos.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; 動画一覧へ戻る</a>
</div>

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
                <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                    <span>処理進捗</span><span>{{ $video->isFailed() ? '失敗' : $video->progressPercent() . '%' }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3"><div class="bg-indigo-600 h-3 rounded-full" style="width: {{ $video->isFailed() ? 100 : $video->progressPercent() }}%"></div></div>
            </div>

            @if($video->error_message)
                <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
                    <p class="text-sm text-red-700">{{ $video->error_message }}</p>
                    <form action="{{ route('videos.rerun', $video) }}" method="POST" class="mt-2">@csrf<input type="hidden" name="step" value="{{ $video->last_failed_step }}"><button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">失敗ステップから再実行</button></form>
                </div>
            @endif

            <div class="mt-4 grid md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500">時間</span><p class="font-medium">{{ $video->duration_sec ? gmdate('H:i:s', (int) $video->duration_sec) : '-' }}</p></div>
                <div><span class="text-gray-500">解像度</span><p class="font-medium">{{ $video->width && $video->height ? "{$video->width}x{$video->height}" : '-' }}</p></div>
                <div><span class="text-gray-500">FPS</span><p class="font-medium">{{ $video->fps ?? '-' }}</p></div>
                <div><span class="text-gray-500">更新</span><p class="font-medium">{{ $video->last_processed_at?->format('Y/m/d H:i') ?? '-' }}</p></div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">自動編集設定</h2>
            <dl class="grid md:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">プロファイル</dt><dd class="font-medium">{{ $video->processing_profile }}</dd></div>
                <div><dt class="text-gray-500">字幕スタイル</dt><dd class="font-medium">{{ $video->selectedCaptionStyle?->name ?? '自動選択' }}</dd></div>
                <div><dt class="text-gray-500">リサイズ</dt><dd class="font-medium">{{ $video->processingOption('resize_mode', 'short') }}</dd></div>
                <div><dt class="text-gray-500">エクスポート</dt><dd class="font-medium">{{ strtoupper($video->export_options['format'] ?? 'mp4') }} / {{ $video->export_options['video_bitrate'] ?? '4M' }}</dd></div>
                <div><dt class="text-gray-500">BGM</dt><dd class="font-medium">{{ $video->processingOption('bgm_path') ? 'あり' : 'なし' }}</dd></div>
                <div><dt class="text-gray-500">SE</dt><dd class="font-medium">{{ $video->processingOption('se_path') ? 'あり' : 'なし' }}</dd></div>
            </dl>
        </div>

        @if($video->transcriptSegments->isNotEmpty())
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">文字起こし / テロップ候補</h2>
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

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">処理ログ</h2>
            @if($video->processingLogs->isEmpty())
                <p class="text-sm text-gray-500">まだログがありません。</p>
            @else
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($video->processingLogs as $log)
                        <div class="border-l-4 {{ $log->level === 'error' ? 'border-red-400 bg-red-50' : ($log->level === 'warning' ? 'border-yellow-400 bg-yellow-50' : 'border-indigo-400 bg-indigo-50') }} p-3 rounded-r-md">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $log->step }}</p>
                                    <p class="text-sm text-gray-700">{{ $log->message }}</p>
                                </div>
                                <span class="text-xs text-gray-500">{{ $log->created_at->format('Y/m/d H:i:s') }}</span>
                            </div>
                            @if($log->context_json)
                                <pre class="mt-2 text-xs text-gray-600 whitespace-pre-wrap">{{ json_encode($log->context_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        @foreach($video->renderTasks as $renderTask)
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">{{ $renderTask->render_type === 'short' ? 'ショート動画' : 'ロング動画' }} ({{ $renderTask->aspect_ratio }})</h3>
                <x-status-badge :status="$renderTask->status" />
            </div>

            @if($renderTask->status === 'completed' && $renderTask->output_path)
                <div class="space-y-3">
                    <video controls class="w-full rounded-lg bg-black" style="{{ $renderTask->aspect_ratio === '9:16' ? 'max-height: 400px;' : '' }}"><source src="{{ route('renders.preview', $renderTask) }}" type="video/mp4"></video>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('renders.download', $renderTask) }}" class="block text-center w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">ダウンロード</a>
                        <form action="{{ route('videos.rerun', $video) }}" method="POST">@csrf<input type="hidden" name="step" value="build_caption"><button type="submit" class="w-full px-4 py-2 border border-indigo-300 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-50">再レンダリング</button></form>
                    </div>
                </div>
            @endif

            @if($renderTask->status === 'completed' && $renderTask->output_path)
                <div class="mt-4 space-y-3 border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-700">公開設定</h4>
                    <form action="{{ route('videos.publish.youtube', $video) }}" method="POST" class="space-y-2 p-3 bg-gray-50 rounded-md">
                        @csrf
                        <input type="hidden" name="render_task_id" value="{{ $renderTask->id }}">
                        <p class="text-sm font-medium text-gray-700">YouTube</p>
                        <input type="text" name="title" placeholder="タイトル" value="{{ $video->title }}" required class="w-full text-sm border-gray-300 rounded-md">
                        <textarea name="description" placeholder="説明文" rows="2" class="w-full text-sm border-gray-300 rounded-md"></textarea>
                        <input type="text" name="tags" placeholder="タグ（カンマ区切り）" class="w-full text-sm border-gray-300 rounded-md">
                        <select name="privacy_status" class="w-full text-sm border-gray-300 rounded-md">
                            <option value="private">非公開</option>
                            <option value="unlisted">限定公開</option>
                            <option value="public">公開</option>
                        </select>
                        <div>
                            <label class="text-xs text-gray-500">予約投稿（空欄で即時投稿）</label>
                            <input type="datetime-local" name="scheduled_at" class="w-full text-sm border-gray-300 rounded-md" min="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">YouTubeに投稿</button>
                    </form>

                    <form action="{{ route('videos.publish.tiktok', $video) }}" method="POST" class="space-y-2 p-3 bg-gray-50 rounded-md">
                        @csrf
                        <input type="hidden" name="render_task_id" value="{{ $renderTask->id }}">
                        <p class="text-sm font-medium text-gray-700">TikTok</p>
                        <input type="text" name="title" placeholder="タイトル" value="{{ $video->title }}" class="w-full text-sm border-gray-300 rounded-md">
                        <select name="privacy_status" class="w-full text-sm border-gray-300 rounded-md">
                            <option value="private">非公開（下書き）</option>
                            <option value="public">公開</option>
                        </select>
                        <div>
                            <label class="text-xs text-gray-500">予約投稿（空欄で即時投稿）</label>
                            <input type="datetime-local" name="scheduled_at" class="w-full text-sm border-gray-300 rounded-md" min="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-black">TikTokに投稿</button>
                    </form>
                </div>
            @endif

            @foreach($renderTask->publishTasks as $pt)
            <div class="mt-3 p-3 bg-gray-50 rounded-md">
                <div class="flex justify-between items-center"><span class="text-sm font-medium">{{ $pt->platform === 'youtube' ? 'YouTube' : 'TikTok' }}</span><x-status-badge :status="$pt->status" /></div>
                @if($pt->scheduled_at && $pt->status === 'scheduled')<p class="text-xs text-amber-600 mt-1">予約: {{ $pt->scheduled_at->format('Y/m/d H:i') }}</p>@endif
                @if($pt->external_url)<a href="{{ $pt->external_url }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 mt-1 block">{{ $pt->external_url }}</a>@endif
                @if($pt->error_message)<p class="text-xs text-red-600 mt-1">{{ $pt->error_message }}</p>@endif
            </div>
            @endforeach
        </div>
        @endforeach

        <div class="bg-white shadow-sm rounded-lg p-6 space-y-3">
            <h3 class="font-semibold text-gray-900">操作</h3>
            @if($video->isFailed())
                <form action="{{ route('videos.rerun', $video) }}" method="POST">@csrf<button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-md hover:bg-yellow-600">失敗ステップから再実行</button></form>
            @endif
            <form action="{{ route('videos.rerun', $video) }}" method="POST">@csrf<input type="hidden" name="step" value="extract_audio"><button type="submit" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">最初から再処理</button></form>
            <form action="{{ route('videos.destroy', $video) }}" method="POST" onsubmit="return confirm('この動画を削除しますか？')">@csrf @method('DELETE')<button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-md text-sm font-medium hover:bg-red-50">削除</button></form>
        </div>
    </div>
</div>
@endsection
