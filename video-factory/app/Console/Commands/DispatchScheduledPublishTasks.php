<?php

namespace App\Console\Commands;

use App\Jobs\PublishTikTokDraftJob;
use App\Jobs\PublishYoutubeJob;
use App\Models\PublishTask;
use Illuminate\Console\Command;

class DispatchScheduledPublishTasks extends Command
{
    protected $signature = 'publish:dispatch-scheduled';

    protected $description = 'Dispatch publish jobs for scheduled tasks that are due';

    public function handle(): int
    {
        $tasks = PublishTask::where('status', PublishTask::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No scheduled publish tasks are due.');

            return self::SUCCESS;
        }

        foreach ($tasks as $task) {
            $task->update(['status' => PublishTask::STATUS_PENDING]);

            match ($task->platform) {
                'youtube' => PublishYoutubeJob::dispatch($task->id),
                'tiktok' => PublishTikTokDraftJob::dispatch($task->id),
            };

            $this->info("Dispatched {$task->platform} publish task #{$task->id}.");
        }

        $this->info("Dispatched {$tasks->count()} scheduled publish task(s).");

        return self::SUCCESS;
    }
}
