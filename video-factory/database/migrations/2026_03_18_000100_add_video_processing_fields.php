<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (!Schema::hasColumn('videos', 'selected_caption_style_id')) {
                $table->foreignId('selected_caption_style_id')->nullable()->after('user_id')->constrained('caption_styles')->nullOnDelete();
            }
            if (!Schema::hasColumn('videos', 'processing_profile')) {
                $table->string('processing_profile')->default('balanced')->after('last_failed_step');
            }
            if (!Schema::hasColumn('videos', 'processing_options')) {
                $table->json('processing_options')->nullable()->after('processing_profile');
            }
            if (!Schema::hasColumn('videos', 'export_options')) {
                $table->json('export_options')->nullable()->after('processing_options');
            }
            if (!Schema::hasColumn('videos', 'pipeline_summary')) {
                $table->json('pipeline_summary')->nullable()->after('export_options');
            }
            if (!Schema::hasColumn('videos', 'last_processed_at')) {
                $table->timestamp('last_processed_at')->nullable()->after('pipeline_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (Schema::hasColumn('videos', 'selected_caption_style_id')) {
                $table->dropConstrainedForeignId('selected_caption_style_id');
            }
            $columns = ['processing_profile', 'processing_options', 'export_options', 'pipeline_summary', 'last_processed_at'];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('videos', $col));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
