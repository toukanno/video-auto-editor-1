<?php

namespace App\Http\Controllers;

use App\Models\RenderTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RenderController extends Controller
{
    public function index()
    {
        $renders = RenderTask::whereHas('video', function ($q) {
            $q->where('user_id', Auth::id());
        })
            ->with(['video', 'publishTasks'])
            ->latest()
            ->paginate(20);

        return view('renders.index', compact('renders'));
    }

    /**
     * Stream the rendered video for preview.
     */
    public function preview(RenderTask $renderTask)
    {
        $video = $renderTask->video;
        $this->authorize('view', $video);

        if (!$renderTask->output_path || $renderTask->status !== RenderTask::STATUS_COMPLETED) {
            abort(404);
        }

        $path = Storage::disk($video->storage_disk)->path($renderTask->output_path);

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
        ]);
    }

    /**
     * Download the rendered video.
     */
    public function download(RenderTask $renderTask)
    {
        $video = $renderTask->video;
        $this->authorize('view', $video);

        if (!$renderTask->output_path || $renderTask->status !== RenderTask::STATUS_COMPLETED) {
            abort(404);
        }

        $path = Storage::disk($video->storage_disk)->path($renderTask->output_path);
        $filename = $video->title . '_' . $renderTask->render_type . '.mp4';

        return response()->download($path, $filename);
    }
}
