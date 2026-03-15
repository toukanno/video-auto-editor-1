@props(['status'])

@php
$colors = [
    'uploaded' => 'bg-gray-100 text-gray-700',
    'extracting_audio' => 'bg-blue-100 text-blue-700',
    'transcribing' => 'bg-blue-100 text-blue-700',
    'normalizing' => 'bg-blue-100 text-blue-700',
    'detecting_silence' => 'bg-blue-100 text-blue-700',
    'building_caption' => 'bg-blue-100 text-blue-700',
    'rendering' => 'bg-yellow-100 text-yellow-700',
    'rendered' => 'bg-green-100 text-green-700',
    'publishing' => 'bg-purple-100 text-purple-700',
    'completed' => 'bg-green-100 text-green-700',
    'failed' => 'bg-red-100 text-red-700',
    'pending' => 'bg-gray-100 text-gray-700',
    'processing' => 'bg-yellow-100 text-yellow-700',
];

$labels = [
    'uploaded' => 'アップロード済',
    'extracting_audio' => '音声抽出中',
    'transcribing' => '文字起こし中',
    'normalizing' => 'テロップ整形中',
    'detecting_silence' => '無音検出中',
    'building_caption' => '字幕生成中',
    'rendering' => 'レンダリング中',
    'rendered' => 'レンダリング完了',
    'publishing' => '投稿中',
    'completed' => '完了',
    'failed' => '失敗',
    'pending' => '待機中',
    'processing' => '処理中',
];

$colorClass = $colors[$status] ?? 'bg-gray-100 text-gray-700';
$label = $labels[$status] ?? $status;

$isProcessing = in_array($status, ['extracting_audio', 'transcribing', 'normalizing', 'detecting_silence', 'building_caption', 'rendering', 'publishing', 'processing']);
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
    @if($isProcessing)
        <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    {{ $label }}
</span>
