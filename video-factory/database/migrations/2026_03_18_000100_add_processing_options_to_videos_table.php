<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('preferred_caption_style_id')->nullable()->after('last_failed_step')->constrained('caption_styles')->nullOnDelete();
            $table->json('processing_options')->nullable()->after('preferred_caption_style_id');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_caption_style_id');
            $table->dropColumn('processing_options');
        });
    }
};
