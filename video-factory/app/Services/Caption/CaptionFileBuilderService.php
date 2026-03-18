<?php

namespace App\Services\Caption;

use App\Models\CaptionStyle;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

class CaptionFileBuilderService
{
    /**
     * Build SRT caption file from transcript segments.
     */
    public function buildSrt(Video $video): string
    {
        $segments = $video->transcriptSegments()->get();
        $outputDir = 'videos/captions';
        $outputFilename = $video->id . '.srt';
        $outputRelative = $outputDir . '/' . $outputFilename;

        Storage::disk($video->storage_disk)->makeDirectory($outputDir);

        $srt = '';
        foreach ($segments as $i => $seg) {
            $text = $seg->displayText();
            if (empty($text)) {
                continue;
            }

            $startTime = $this->msToSrtTime($seg->start_ms);
            $endTime = $this->msToSrtTime($seg->end_ms);

            $srt .= ($i + 1) . "\n";
            $srt .= "{$startTime} --> {$endTime}\n";
            $srt .= $text . "\n\n";
        }

        Storage::disk($video->storage_disk)->put($outputRelative, $srt);

        return $outputRelative;
    }

    /**
     * Build ASS caption file with styling from CaptionStyle.
     */
    public function buildAss(Video $video, CaptionStyle $style, ?int $suffix = null): string
    {
        $segments = $video->transcriptSegments()->get();
        $outputDir = 'videos/captions';
        $outputFilename = $video->id . ($suffix ? "_{$suffix}" : '') . '.ass';
        $outputRelative = $outputDir . '/' . $outputFilename;

        Storage::disk($video->storage_disk)->makeDirectory($outputDir);

        $fontColor = $this->hexToAss($style->font_color);
        $strokeColor = $this->hexToAss($style->stroke_color);
        $bgColor = $style->background_color ? $this->hexToAss($style->background_color) : '&H00000000';

        $ass = "[Script Info]\n";
        $ass .= "ScriptType: v4.00+\n";
        $ass .= "PlayResX: 1080\n";
        $ass .= "PlayResY: 1920\n";
        $ass .= "\n";
        $ass .= "[V4+ Styles]\n";
        $ass .= "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding\n";
        $ass .= "Style: Default,{$style->font_family},{$style->font_size},{$fontColor},&H000000FF,{$strokeColor},{$bgColor},0,0,0,0,100,100,0,0,1,{$style->stroke_width},0,2,20,20," . ($this->getMarginV($style)) . ",1\n";
        $ass .= "\n";
        $ass .= "[Events]\n";
        $ass .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";

        foreach ($segments as $seg) {
            $text = $seg->displayText();
            if (empty($text)) {
                continue;
            }

            // Wrap text at chars_per_line
            $wrapped = $this->wrapText($text, $style->chars_per_line, $style->max_lines);

            $start = $this->msToAssTime($seg->start_ms);
            $end = $this->msToAssTime($seg->end_ms);

            $ass .= "Dialogue: 0,{$start},{$end},Default,,0,0,0,,{$wrapped}\n";
        }

        Storage::disk($video->storage_disk)->put($outputRelative, $ass);

        return $outputRelative;
    }

    private function msToSrtTime(int $ms): string
    {
        $h = intdiv($ms, 3600000);
        $m = intdiv($ms % 3600000, 60000);
        $s = intdiv($ms % 60000, 1000);
        $f = $ms % 1000;
        return sprintf('%02d:%02d:%02d,%03d', $h, $m, $s, $f);
    }

    private function msToAssTime(int $ms): string
    {
        $h = intdiv($ms, 3600000);
        $m = intdiv($ms % 3600000, 60000);
        $s = intdiv($ms % 60000, 1000);
        $cs = intdiv($ms % 1000, 10);
        return sprintf('%d:%02d:%02d.%02d', $h, $m, $s, $cs);
    }

    private function hexToAss(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 6) {
            // Convert RGB hex to ASS BGR format (&HAABBGGRR)
            $r = substr($hex, 0, 2);
            $g = substr($hex, 2, 2);
            $b = substr($hex, 4, 2);
            return "&H00{$b}{$g}{$r}";
        }
        return '&H00FFFFFF';
    }

    private function getMarginV(CaptionStyle $style): int
    {
        // position_y is percentage from top, convert to margin from bottom
        // For a 1920px height, 85% from top = 15% from bottom = ~288px
        return (int) ((100 - $style->position_y) / 100 * 1920);
    }

    private function wrapText(string $text, int $charsPerLine, int $maxLines): string
    {
        if (mb_strlen($text) <= $charsPerLine) {
            return $text;
        }

        $lines = [];
        $remaining = $text;

        for ($i = 0; $i < $maxLines && mb_strlen($remaining) > 0; $i++) {
            if (mb_strlen($remaining) <= $charsPerLine) {
                $lines[] = $remaining;
                break;
            }

            // Try to break at punctuation or space
            $breakAt = $charsPerLine;
            $sub = mb_substr($remaining, 0, $charsPerLine + 5);

            // Look for Japanese punctuation breakpoints
            $punctuation = ['、', '。', '！', '？', '　', ' '];
            $bestBreak = -1;
            foreach ($punctuation as $p) {
                $pos = mb_strrpos($sub, $p, -(mb_strlen($sub) - $charsPerLine));
                if ($pos !== false && $pos > 0 && ($bestBreak === -1 || $pos > $bestBreak)) {
                    $bestBreak = $pos + mb_strlen($p);
                }
            }

            if ($bestBreak > 0) {
                $breakAt = $bestBreak;
            }

            $lines[] = mb_substr($remaining, 0, $breakAt);
            $remaining = mb_substr($remaining, $breakAt);
        }

        return implode('\N', $lines);
    }
}
