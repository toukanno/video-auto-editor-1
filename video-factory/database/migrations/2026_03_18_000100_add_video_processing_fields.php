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
            $table->string('processing_profile')->default('balanced')->after('last_failed_step');
            $table->json('processing_options')->nullable()->after('processing_profile');
            $table->json('export_options')->nullable()->after('processing_options');
            $table->json('pipeline_summary')->nullable()->after('export_options');
            $table->timestamp('last_processed_at')->nullable()->after('pipeline_summary');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selected_caption_style_id');
            $table->dropColumn(['processing_profile', 'processing_options', 'export_options', 'pipeline_summary', 'last_processed_at']);
        });
    }
};
