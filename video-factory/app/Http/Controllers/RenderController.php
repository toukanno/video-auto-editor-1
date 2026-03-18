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

    public function preview(RenderTask $renderTask)
    {
        $video = $renderTask->video;
        $this->authorize('view', $video);

        if (!$renderTask->output_path || $renderTask->status !== RenderTask::STATUS_COMPLETED) {
            abort(404);
        }

        return response()->file(Storage::disk($video->storage_disk)->path($renderTask->output_path), [
            'Content-Type' => 'video/mp4',
        ]);
    }

    public function download(RenderTask $renderTask)
    {
        $video = $renderTask->video;
        $this->authorize('view', $video);

        if (!$renderTask->output_path || $renderTask->status !== RenderTask::STATUS_COMPLETED) {
            abort(404);
        }

        $filename = str($video->title)->slug('_') . '_' . $renderTask->render_type . '.mp4';

        return response()->download(Storage::disk($video->storage_disk)->path($renderTask->output_path), $filename);
    }
}
