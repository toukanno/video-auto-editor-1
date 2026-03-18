<?php

namespace App\Http\Controllers;

use App\Models\CaptionStyle;
use App\Models\Video;
use App\Services\Video\VideoPipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    public function create()
    {
        $captionStyles = CaptionStyle::where('user_id', Auth::id())->get();

        return view('videos.create', compact('captionStyles'));
    }

    public function store(Request $request, VideoPipelineService $pipeline)
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:2097152', // 2GB
            'title' => 'nullable|string|max:255',
            'caption_style_id' => 'nullable|exists:caption_styles,id',
            'render_short' => 'nullable|boolean',
            'render_long' => 'nullable|boolean',
        ]);

        $file = $request->file('video');
        $originalName = $file->getClientOriginalName();

        // Store video
        $storagePath = $file->store('videos/original', 'local');

        $video = Video::create([
            'user_id' => Auth::id(),
            'title' => $request->input('title', pathinfo($originalName, PATHINFO_FILENAME)),
            'source_filename' => $originalName,
            'storage_disk' => 'local',
            'original_path' => $storagePath,
            'status' => Video::STATUS_UPLOADED,
        ]);

        if ($request->boolean('render_short', true)) {
            $video->renderTasks()->create([
                'caption_style_id' => $request->input('caption_style_id'),
                'render_type' => 'short',
                'aspect_ratio' => '9:16',
                'target_width' => 1080,
                'target_height' => 1920,
                'status' => 'pending',
            ]);
        }

        if ($request->boolean('render_long')) {
            $video->renderTasks()->create([
                'caption_style_id' => $request->input('caption_style_id'),
                'render_type' => 'long',
                'aspect_ratio' => '16:9',
                'target_width' => 1920,
                'target_height' => 1080,
                'status' => 'pending',
            ]);
        }

        // Start processing pipeline
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
        ]);

        return view('videos.show', compact('video'));
    }

    public function destroy(Video $video)
    {
        $this->authorize('delete', $video);

        // Clean up files
        if ($video->original_path) {
            Storage::disk($video->storage_disk)->delete($video->original_path);
        }
        if ($video->audio_path) {
            Storage::disk($video->storage_disk)->delete($video->audio_path);
        }

        foreach ($video->renderTasks as $task) {
            if ($task->output_path) {
                Storage::disk($video->storage_disk)->delete($task->output_path);
            }
            if ($task->thumbnail_path) {
                Storage::disk($video->storage_disk)->delete($task->thumbnail_path);
            }
        }

        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', '動画を削除しました。');
    }

    public function rerun(Video $video, Request $request, VideoPipelineService $pipeline)
    {
        $this->authorize('update', $video);

        $step = $request->input('step');

        if ($step) {
            $pipeline->rerunFrom($video, $step);
        } else {
            $pipeline->retry($video);
        }

        return redirect()->route('videos.show', $video)
            ->with('success', '処理を再実行します。');
    }
}
