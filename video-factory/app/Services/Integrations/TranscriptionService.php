<?php

namespace App\Services\Integrations;

use App\Models\TranscriptSegment;
use App\Models\Video;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TranscriptionService
{
    /**
     * Transcribe audio using OpenAI Whisper API.
     */
    public function transcribe(Video $video): void
    {
        $audioPath = Storage::disk($video->storage_disk)->path($video->audio_path);
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            $video->transcriptSegments()->delete();
            $video->transcriptSegments()->create([
                'seq' => 1,
                'start_ms' => 0,
                'end_ms' => (int) (($video->duration_sec ?? 10) * 1000),
                'text_raw' => 'OpenAI APIキー未設定のため、ローカル確認用のダミー字幕を生成しました。',
                'text_normalized' => 'ローカル確認用のダミー字幕です。',
            ]);

            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])
            ->timeout(300)
            ->attach('file', file_get_contents($audioPath), basename($audioPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => config('services.openai.whisper_model', 'whisper-1'),
                'language' => 'ja',
                'response_format' => 'verbose_json',
                'timestamp_granularities' => ['segment'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Whisper API failed: ' . $response->body());
        }

        $data = $response->json();
        $segments = $data['segments'] ?? [];

        // Clear existing segments
        $video->transcriptSegments()->delete();

        foreach ($segments as $i => $seg) {
            $video->transcriptSegments()->create([
                'seq' => $i + 1,
                'start_ms' => (int) (($seg['start'] ?? 0) * 1000),
                'end_ms' => (int) (($seg['end'] ?? 0) * 1000),
                'text_raw' => trim($seg['text'] ?? ''),
                'confidence' => $seg['avg_logprob'] ?? null,
            ]);
        }

        // Save raw transcript JSON
        $transcriptDir = 'videos/transcripts';
        Storage::disk($video->storage_disk)->makeDirectory($transcriptDir);
        Storage::disk($video->storage_disk)->put(
            $transcriptDir . '/' . $video->id . '.json',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
