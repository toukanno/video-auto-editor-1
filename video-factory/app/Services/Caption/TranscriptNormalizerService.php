<?php

namespace App\Services\Caption;

use App\Models\TranscriptSegment;
use App\Models\Video;
use Illuminate\Support\Facades\Http;

class TranscriptNormalizerService
{
    /**
     * Normalize transcript segments using LLM API.
     * Removes filler words, optimizes line breaks, extracts emphasis.
     */
    public function normalize(Video $video): void
    {
        $segments = $video->transcriptSegments()->get();

        if ($segments->isEmpty()) {
            return;
        }

        // Process in chunks to avoid token limits
        $chunks = $segments->chunk(20);

        foreach ($chunks as $chunk) {
            $rawTexts = $chunk->map(fn (TranscriptSegment $s) => [
                'id' => $s->id,
                'text' => $s->text_raw,
            ])->values()->toArray();

            $normalized = $this->callLlm($rawTexts);

            foreach ($normalized as $item) {
                TranscriptSegment::where('id', $item['id'])->update([
                    'text_normalized' => $item['text'],
                ]);
            }
        }
    }

    /**
     * Simple rule-based normalization as fallback.
     */
    public function normalizeWithRules(Video $video): void
    {
        $segments = $video->transcriptSegments()->get();

        foreach ($segments as $segment) {
            $text = $segment->text_raw;

            // Remove common Japanese filler words
            $fillers = ['えー、', 'えーっと、', 'あのー、', 'あの、', 'まあ、', 'えー', 'えーっと', 'あのー', 'あの'];
            $text = str_replace($fillers, '', $text);

            // Trim whitespace
            $text = trim($text);

            if (!empty($text)) {
                $segment->update(['text_normalized' => $text]);
            }
        }
    }

    private function callLlm(array $texts): array
    {
        $apiKey = config('services.openai.api_key') ?: config('services.anthropic.api_key');
        $baseUrl = config('services.llm.base_url', 'https://api.openai.com/v1');

        if (empty($apiKey)) {
            // Fallback to rule-based normalization
            return array_map(fn ($t) => ['id' => $t['id'], 'text' => $this->ruleBasedClean($t['text'])], $texts);
        }

        $textsJson = json_encode($texts, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
以下の字幕テキストをショート動画向けに整形してください。

条件:
- 1行15文字前後、最大2行
- 口癖（えー、あのー、まあ等）は削除
- 句読点で適切に区切る
- 意味が通じる最小限の表現にする
- 元のid をそのまま返す

入力:
{$textsJson}

JSON配列で返してください:
[{"id": 1, "text": "整形後テキスト"}, ...]
PROMPT;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->timeout(60)->post($baseUrl . '/chat/completions', [
            'model' => config('services.llm.model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ]);

        if ($response->failed()) {
            return array_map(fn ($t) => ['id' => $t['id'], 'text' => $this->ruleBasedClean($t['text'])], $texts);
        }

        $content = $response->json('choices.0.message.content', '');
        // Extract JSON from response
        if (preg_match('/\[.*\]/s', $content, $matches)) {
            $parsed = json_decode($matches[0], true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return array_map(fn ($t) => ['id' => $t['id'], 'text' => $this->ruleBasedClean($t['text'])], $texts);
    }

    private function ruleBasedClean(string $text): string
    {
        $fillers = ['えー、', 'えーっと、', 'あのー、', 'あの、', 'まあ、', 'えー', 'えーっと', 'あのー', 'あの'];
        $text = str_replace($fillers, '', $text);
        return trim($text);
    }
}
