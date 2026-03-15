<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publish_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('render_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform'); // youtube, tiktok
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('tags_json')->nullable();
            $table->string('privacy_status')->default('private'); // public, private, unlisted
            $table->string('external_id')->nullable();
            $table->string('external_url')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('response_json')->nullable();
            $table->string('error_message', 1000)->nullable();
            $table->timestamps();

            $table->index(['render_task_id', 'platform']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_tasks');
    }
};
