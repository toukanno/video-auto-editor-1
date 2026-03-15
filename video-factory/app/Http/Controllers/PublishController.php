<?php

namespace App\Http\Controllers;

use App\Jobs\PublishTikTokDraftJob;
use App\Jobs\PublishYoutubeJob;
use App\Models\PublishTask;
use App\Models\RenderTask;
use App\Models\Video;
use Illuminate\Http\Request;

class PublishController extends Controller
{
    public function youtube(Video $video, Request $request)
    {
        $this->authorize('update', $video);

        $request->validate([
            'render_task_id' => 'required|exists:render_tasks,id',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:5000',
            'tags' => 'nullable|string|max:500',
            'privacy_status' => 'required|in:public,private,unlisted',
        ]);

        $renderTask = RenderTask::where('id', $request->render_task_id)
            ->where('video_id', $video->id)
            ->where('status', RenderTask::STATUS_COMPLETED)
            ->firstOrFail();

        $tags = $request->tags
            ? array_map('trim', explode(',', $request->tags))
            : [];

        $publishTask = PublishTask::create([
            'render_task_id' => $renderTask->id,
            'platform' => 'youtube',
            'title' => $request->title,
            'description' => $request->description,
            'tags_json' => $tags,
            'privacy_status' => $request->privacy_status,
            'status' => PublishTask::STATUS_PENDING,
        ]);

        PublishYoutubeJob::dispatch($publishTask->id);

        return redirect()->route('videos.show', $video)
            ->with('success', 'YouTubeへの投稿を開始しました。');
    }

    public function tiktok(Video $video, Request $request)
    {
        $this->authorize('update', $video);

        $request->validate([
            'render_task_id' => 'required|exists:render_tasks,id',
            'title' => 'nullable|string|max:150',
            'privacy_status' => 'nullable|in:public,private',
        ]);

        $renderTask = RenderTask::where('id', $request->render_task_id)
            ->where('video_id', $video->id)
            ->where('status', RenderTask::STATUS_COMPLETED)
            ->firstOrFail();

        $publishTask = PublishTask::create([
            'render_task_id' => $renderTask->id,
            'platform' => 'tiktok',
            'title' => $request->title,
            'privacy_status' => $request->input('privacy_status', 'private'),
            'status' => PublishTask::STATUS_PENDING,
        ]);

        PublishTikTokDraftJob::dispatch($publishTask->id);

        return redirect()->route('videos.show', $video)
            ->with('success', 'TikTokへの下書きアップロードを開始しました。');
    }
}
