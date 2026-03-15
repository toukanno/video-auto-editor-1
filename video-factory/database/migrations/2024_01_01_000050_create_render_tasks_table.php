<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('render_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('caption_style_id')->nullable()->constrained()->nullOnDelete();
            $table->string('render_type')->default('short'); // long, short, short_auto_cut
            $table->string('aspect_ratio')->default('9:16');
            $table->unsignedInteger('target_width')->default(1080);
            $table->unsignedInteger('target_height')->default(1920);
            $table->string('output_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('error_message', 1000)->nullable();
            $table->timestamps();

            $table->index(['video_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_tasks');
    }
};
