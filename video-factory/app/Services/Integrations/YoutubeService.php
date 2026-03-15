<?php

namespace App\Services\Integrations;

use App\Models\PlatformAccount;
use App\Models\PublishTask;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class YoutubeService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const UPLOAD_URL = 'https://www.googleapis.com/upload/youtube/v3/videos';

    /**
     * Get OAuth authorization URL.
     */
    public function getAuthUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => config('services.youtube.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('YouTube token exchange failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(PlatformAccount $account): string
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('YouTube token refresh failed: ' . $response->body());
        }

        $data = $response->json();

        $account->update([
            'access_token' => $data['access_token'],
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $data['access_token'];
    }

    /**
     * Upload video to YouTube using resumable upload.
     */
    public function upload(PublishTask $publishTask, PlatformAccount $account, string $videoPath): array
    {
        $accessToken = $account->isExpired()
            ? $this->refreshToken($account)
            : $account->access_token;

        // Step 1: Initialize resumable upload
        $metadata = [
            'snippet' => [
                'title' => $publishTask->title ?? 'Untitled',
                'description' => $publishTask->description ?? '',
                'tags' => $publishTask->tags_json ?? [],
                'categoryId' => '22', // People & Blogs
            ],
            'status' => [
                'privacyStatus' => $publishTask->privacy_status ?? 'private',
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        $initResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'X-Upload-Content-Type' => 'video/mp4',
        ])->post(self::UPLOAD_URL . '?uploadType=resumable&part=snippet,status', $metadata);

        if ($initResponse->failed()) {
            throw new RuntimeException('YouTube upload init failed: ' . $initResponse->body());
        }

        $uploadUrl = $initResponse->header('Location');
        if (empty($uploadUrl)) {
            throw new RuntimeException('YouTube upload URL not returned');
        }

        // Step 2: Upload the video file
        $videoContent = file_get_contents($videoPath);

        $uploadResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'video/mp4',
        ])->timeout(1800)->withBody($videoContent, 'video/mp4')->put($uploadUrl);

        if ($uploadResponse->failed()) {
            throw new RuntimeException('YouTube upload failed: ' . $uploadResponse->body());
        }

        $result = $uploadResponse->json();

        return [
            'external_id' => $result['id'] ?? null,
            'external_url' => isset($result['id']) ? 'https://www.youtube.com/watch?v=' . $result['id'] : null,
            'response' => $result,
        ];
    }
}
