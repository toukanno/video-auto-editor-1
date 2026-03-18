<?php

namespace App\Http\Controllers;

use App\Models\CaptionStyle;
use App\Models\Video;
use App\Services\Video\ProcessingLogService;
use App\Services\Video\VideoPipelineService;
use App\Support\VideoProcessingDefaults;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VideoController extends Controller
{
    public function index()
    {
        $videos = Video::where('user_id', Auth::id())
            ->with(['renderTasks', 'renderTasks.publishTasks', 'selectedCaptionStyle'])
            ->latest()
            ->paginate(20);

        return view('videos.index', compact('videos'));
    }

    public function create()
    {
        $captionStyles = CaptionStyle::where('user_id', Auth::id())->get();
        $profiles = VideoProcessingDefaults::profiles();
        $defaults = VideoProcessingDefaults::processingOptions();
        $exportDefaults = VideoProcessingDefaults::exportOptions();

        return view('videos.create', compact('captionStyles', 'profiles', 'defaults', 'exportDefaults'));
    }

    public function store(Request $request, VideoPipelineService $pipeline, ProcessingLogService $logService)
    {
        $validated = $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:2097152',
            'title' => 'nullable|string|max:255',
            'selected_caption_style_id' => ['nullable', Rule::exists('caption_styles', 'id')->where('user_id', Auth::id())],
            'processing_profile' => ['required', Rule::in(array_keys(VideoProcessingDefaults::profiles()))],
            'generate_short' => 'nullable|boolean',
            'generate_long' => 'nullable|boolean',
            'enable_caption' => 'nullable|boolean',
            'enable_silence_cut' => 'nullable|boolean',
            'resize_mode' => ['required', Rule::in(['short', 'long', 'square', 'original'])],
            'bgm' => 'nullable|file|mimetypes:audio/mpeg,audio/mp4,audio/wav,audio/x-wav|max:51200',
            'bgm_volume' => 'nullable|integer|min:0|max:100',
            'se' => 'nullable|file|mimetypes:audio/mpeg,audio/mp4,audio/wav,audio/x-wav|max:20480',
            'se_volume' => 'nullable|integer|min:0|max:100',
            'export_format' => ['required', Rule::in(['mp4'])],
            'video_bitrate' => 'required|string|max:20',
            'audio_bitrate' => 'required|string|max:20',
            'target_fps' => 'required|integer|min:24|max:60',
        ]);

        $file = $request->file('video');
        $originalName = $file->getClientOriginalName();
        $storagePath = $file->store('videos/original', 'local');

        $processingOptions = array_merge(VideoProcessingDefaults::processingOptions(), [
            'generate_short' => $request->boolean('generate_short', true),
            'generate_long' => $request->boolean('generate_long', false),
            'enable_caption' => $request->boolean('enable_caption', true),
            'enable_silence_cut' => $request->boolean('enable_silence_cut', true),
            'resize_mode' => $request->input('resize_mode', 'short'),
            'bgm_volume' => (int) $request->input('bgm_volume', 20),
            'se_volume' => (int) $request->input('se_volume', 40),
        ]);

        if ($request->hasFile('bgm')) {
            $processingOptions['bgm_path'] = $request->file('bgm')->store('videos/assets/bgm', 'local');
        }

        if ($request->hasFile('se')) {
            $processingOptions['se_path'] = $request->file('se')->store('videos/assets/se', 'local');
        }

        $exportOptions = array_merge(VideoProcessingDefaults::exportOptions(), [
            'format' => $request->input('export_format', 'mp4'),
            'video_bitrate' => $request->input('video_bitrate', '4M'),
            'audio_bitrate' => $request->input('audio_bitrate', '128k'),
            'fps' => (int) $request->input('target_fps', 30),
        ]);

        $video = Video::create([
            'user_id' => Auth::id(),
            'title' => $request->input('title', pathinfo($originalName, PATHINFO_FILENAME)),
            'source_filename' => $originalName,
            'storage_disk' => 'local',
            'original_path' => $storagePath,
            'status' => Video::STATUS_UPLOADED,
            'selected_caption_style_id' => $request->input('selected_caption_style_id'),
            'processing_profile' => $request->input('processing_profile', 'balanced'),
            'processing_options' => $processingOptions,
            'export_options' => $exportOptions,
            'pipeline_summary' => [
                'captions' => $processingOptions['enable_caption'],
                'silence_cut' => $processingOptions['enable_silence_cut'],
                'resize_mode' => $processingOptions['resize_mode'],
                'short_export' => $processingOptions['generate_short'],
                'long_export' => $processingOptions['generate_long'],
            ],
        ]);

        $logService->info($video, 'upload', '動画を受信し、パイプライン実行待ちにしました。', [
            'filename' => $originalName,
            'profile' => $video->processing_profile,
        ]);

        $pipeline->start($video);

        return redirect()->route('videos.show', $video)
            ->with('success', '動画をアップロードしました。処理を開始します。');
    }

    public function show(Video $video)
    {
        $this->authorize('view', $video);

        $video->load([
            'transcriptSegments',
            'silenceSegments',
            'renderTasks.captionStyle',
            'renderTasks.publishTasks',
            'selectedCaptionStyle',
            'processingLogs',
        ]);

        return view('videos.show', compact('video'));
    }

    public function destroy(Video $video)
    {
        $this->authorize('delete', $video);

        foreach ([$video->original_path, $video->audio_path, $video->processingOption('bgm_path'), $video->processingOption('se_path')] as $path) {
            if ($path) {
                Storage::disk($video->storage_disk)->delete($path);
            }
        }

        foreach ($video->renderTasks as $task) {
            foreach ([$task->output_path, $task->thumbnail_path] as $path) {
                if ($path) {
                    Storage::disk($video->storage_disk)->delete($path);
                }
            }
        }

        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', '動画を削除しました。');
    }

    public function rerun(Video $video, Request $request, VideoPipelineService $pipeline, ProcessingLogService $logService)
    {
        $this->authorize('update', $video);

        $step = $request->input('step');

        if ($step) {
            $pipeline->rerunFrom($video, $step);
            $logService->info($video, 'rerun', '指定ステップから再実行しました。', ['step' => $step]);
        } else {
            $pipeline->retry($video);
            $logService->info($video, 'rerun', '失敗ステップから再実行しました。');
        }

        return redirect()->route('videos.show', $video)
            ->with('success', '処理を再実行します。');
    }
}
