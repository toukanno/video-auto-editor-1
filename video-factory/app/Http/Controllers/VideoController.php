<?php

namespace App\Http\Controllers;

use App\Models\CaptionStyle;
use App\Models\Video;
use App\Services\SystemHealthService;
use App\Services\Video\VideoPipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VideoController extends Controller
{
    public function index()
    {
        $videos = Video::where('user_id', Auth::id())
            ->with(['renderTasks', 'renderTasks.publishTasks'])
            ->latest()
            ->paginate(20);

        return view('videos.index', compact('videos'));
    }

    public function create(SystemHealthService $health)
    {
        $captionStyles = CaptionStyle::where('user_id', Auth::id())->get();

        return view('videos.create', [
            'captionStyles' => $captionStyles,
            'checks' => $health->checks(),
        ]);
    }

    public function store(Request $request, VideoPipelineService $pipeline, SystemHealthService $health)
    {
        $validated = $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:2097152',
            'bgm' => 'nullable|file|mimetypes:audio/mpeg,audio/mp4,audio/x-wav,audio/wav|max:51200',
            'title' => 'nullable|string|max:255',
            'caption_style_id' => ['nullable', Rule::exists('caption_styles', 'id')->where('user_id', Auth::id())],
            'render_short' => 'nullable|boolean',
            'target_aspect_ratio' => 'required|in:9:16,16:9,1:1',
            'cut_silence' => 'nullable|boolean',
            'enable_captions' => 'nullable|boolean',
            'bgm_volume' => 'nullable|integer|min:0|max:100',
        ]);

        $file = $request->file('video');
        $originalName = $file->getClientOriginalName();
        $storagePath = $file->store('videos/original', 'local');
        $bgmPath = $request->file('bgm')?->store('videos/bgm', 'local');

        $video = Video::create([
            'user_id' => Auth::id(),
            'selected_caption_style_id' => $validated['caption_style_id'] ?? null,
            'title' => $validated['title'] ?: pathinfo($originalName, PATHINFO_FILENAME),
            'source_filename' => $originalName,
            'storage_disk' => 'local',
            'original_path' => $storagePath,
            'bgm_path' => $bgmPath,
            'bgm_volume' => $validated['bgm_volume'] ?? 15,
            'status' => Video::STATUS_UPLOADED,
            'render_short' => $request->boolean('render_short', true),
            'target_aspect_ratio' => $validated['target_aspect_ratio'],
            'cut_silence' => $request->boolean('cut_silence', true),
            'enable_captions' => $request->boolean('enable_captions', true),
            'processing_profile' => [
                'caption_style_id' => $validated['caption_style_id'] ?? null,
                'target_aspect_ratio' => $validated['target_aspect_ratio'],
                'cut_silence' => $request->boolean('cut_silence', true),
                'enable_captions' => $request->boolean('enable_captions', true),
                'bgm_volume' => $validated['bgm_volume'] ?? 15,
            ],
        ]);

        if (!$health->readyForPipeline()) {
            return redirect()->route('videos.show', $video)
                ->with('success', '動画を保存しました。FFmpeg / FFprobe を設定後に再実行してください。');
        }

        $pipeline->start($video);

        return redirect()->route('videos.show', $video)
            ->with('success', '動画をアップロードしました。処理を開始します。');
    }

    public function show(Video $video)
    {
        $this->authorize('view', $video);

        $video->load([
            'selectedCaptionStyle',
            'transcriptSegments',
            'silenceSegments',
            'renderTasks.captionStyle',
            'renderTasks.publishTasks',
        ]);

        return view('videos.show', compact('video'));
    }

    public function destroy(Video $video)
    {
        $this->authorize('delete', $video);

        foreach ([$video->original_path, $video->audio_path, $video->bgm_path] as $path) {
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

        return redirect()->route('videos.index')->with('success', '動画を削除しました。');
    }

    public function rerun(Video $video, Request $request, VideoPipelineService $pipeline, SystemHealthService $health)
    {
        $this->authorize('update', $video);

        if (!$health->readyForPipeline()) {
            return back()->withErrors(['ffmpeg' => 'FFmpeg / FFprobe が見つからないため再実行できません。']);
        }

        $step = $request->input('step');
        $step ? $pipeline->rerunFrom($video, $step) : $pipeline->retry($video);

        return redirect()->route('videos.show', $video)->with('success', '処理を再実行します。');
    }
}
