<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('selected_caption_style_id')->nullable()->after('user_id')->constrained('caption_styles')->nullOnDelete();
            $table->boolean('render_short')->default(true)->after('status');
            $table->string('target_aspect_ratio')->default('9:16')->after('render_short');
            $table->boolean('cut_silence')->default(true)->after('target_aspect_ratio');
            $table->boolean('enable_captions')->default(true)->after('cut_silence');
            $table->string('bgm_path')->nullable()->after('audio_path');
            $table->unsignedTinyInteger('bgm_volume')->default(15)->after('bgm_path');
            $table->json('processing_profile')->nullable()->after('bgm_volume');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selected_caption_style_id');
            $table->dropColumn(['render_short', 'target_aspect_ratio', 'cut_silence', 'enable_captions', 'bgm_path', 'bgm_volume', 'processing_profile']);
        });
    }
};
