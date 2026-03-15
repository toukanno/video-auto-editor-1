<?php

namespace App\Services\Caption;

use App\Models\CaptionStyle;
use App\Models\User;

class CaptionStyleService
{
    /**
     * Create a default caption style for a user if none exists.
     */
    public function ensureDefault(User $user): CaptionStyle
    {
        return CaptionStyle::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'デフォルト'],
            [
                'font_family' => 'Noto Sans JP',
                'font_size' => 48,
                'font_color' => '#FFFFFF',
                'stroke_color' => '#000000',
                'stroke_width' => 3,
                'background_color' => null,
                'position_y' => 85,
                'max_lines' => 2,
                'chars_per_line' => 18,
            ]
        );
    }

    /**
     * Duplicate a caption style.
     */
    public function duplicate(CaptionStyle $style): CaptionStyle
    {
        $new = $style->replicate();
        $new->name = $style->name . ' (コピー)';
        $new->save();
        return $new;
    }
}
