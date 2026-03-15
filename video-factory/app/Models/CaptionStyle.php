<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptionStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'font_family',
        'font_size',
        'font_color',
        'stroke_color',
        'stroke_width',
        'background_color',
        'position_y',
        'max_lines',
        'chars_per_line',
        'template_json',
    ];

    protected $casts = [
        'font_size' => 'integer',
        'stroke_width' => 'integer',
        'position_y' => 'integer',
        'max_lines' => 'integer',
        'chars_per_line' => 'integer',
        'template_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
