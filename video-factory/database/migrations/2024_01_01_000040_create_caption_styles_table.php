<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caption_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('font_family')->default('Noto Sans JP');
            $table->unsignedInteger('font_size')->default(48);
            $table->string('font_color')->default('#FFFFFF');
            $table->string('stroke_color')->default('#000000');
            $table->unsignedInteger('stroke_width')->default(3);
            $table->string('background_color')->nullable();
            $table->unsignedInteger('position_y')->default(85);
            $table->unsignedInteger('max_lines')->default(2);
            $table->unsignedInteger('chars_per_line')->default(18);
            $table->json('template_json')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caption_styles');
    }
};
