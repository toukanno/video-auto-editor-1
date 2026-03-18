<?php

namespace App\Services\Integrations;

use App\Models\Video;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TranscriptionService
{
    public function transcribe(Video $video): array
    {
        $audioPath = Storage::disk($video->storage_disk)->path($video->audio_path);
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return $this->transcribeFallback($video);
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
        $this->persistSegments($video, $data['segments'] ?? []);
        $this->storeTranscriptJson($video, $data);

        return ['mode' => 'openai', 'segments' => count($data['segments'] ?? [])];
    }

    private function transcribeFallback(Video $video): array
    {
        $duration = max((float) $video->duration_sec, 1.0);
        $parts = min(6, max(1, (int) ceil($duration / 15)));
        $segments = [];

        for ($i = 0; $i < $parts; $i++) {
            $start = ($duration / $parts) * $i;
            $end = min($duration, ($duration / $parts) * ($i + 1));
            $segments[] = [
                'start' => round($start, 2),
                'end' => round($end, 2),
                'text' => '音声認識API未設定のため、ローカル確認用の仮字幕です。設定画面からAPIキーを追加すると高精度化できます。',
                'avg_logprob' => -0.1,
            ];
        }

        $this->persistSegments($video, $segments);
        $this->storeTranscriptJson($video, ['mode' => 'fallback', 'segments' => $segments]);

        return ['mode' => 'fallback', 'segments' => count($segments)];
    }

    private function persistSegments(Video $video, array $segments): void
    {
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
    }

    private function storeTranscriptJson(Video $video, array $payload): void
    {
        Storage::disk($video->storage_disk)->makeDirectory('videos/transcripts');
        Storage::disk($video->storage_disk)->put(
            'videos/transcripts/' . $video->id . '.json',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
