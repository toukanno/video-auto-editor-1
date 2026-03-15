<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('source_filename');
            $table->string('storage_disk')->default('local');
            $table->string('original_path');
            $table->string('audio_path')->nullable();
            $table->decimal('duration_sec', 10, 2)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('fps', 6, 2)->nullable();
            $table->string('status')->default('uploaded');
            $table->string('error_message', 1000)->nullable();
            $table->string('last_failed_step')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
