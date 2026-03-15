<?php

namespace App\Services\Integrations;

use App\Models\PlatformAccount;
use App\Models\PublishTask;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TikTokService
{
    private const AUTH_URL = 'https://www.tiktok.com/v2/auth/authorize/';
    private const TOKEN_URL = 'https://open.tiktokapis.com/v2/oauth/token/';
    private const PUBLISH_URL = 'https://open.tiktokapis.com/v2/post/publish/video/init/';

    /**
     * Get OAuth authorization URL.
     */
    public function getAuthUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_key' => config('services.tiktok.client_key'),
            'response_type' => 'code',
            'scope' => 'video.publish,video.upload',
            'redirect_uri' => $redirectUri,
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_key' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('TikTok token exchange failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(PlatformAccount $account): string
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_key' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('TikTok token refresh failed: ' . $response->body());
        }

        $data = $response->json();

        $account->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 86400),
        ]);

        return $data['access_token'];
    }

    /**
     * Upload video to TikTok as a draft (or publish directly).
     */
    public function upload(
        PublishTask $publishTask,
        PlatformAccount $account,
        string $videoPath,
        bool $asDraft = true
    ): array {
        $accessToken = $account->isExpired()
            ? $this->refreshToken($account)
            : $account->access_token;

        $fileSize = filesize($videoPath);

        // Step 1: Initialize the upload
        $initPayload = [
            'post_info' => [
                'title' => $publishTask->title ?? '',
                'privacy_level' => $asDraft ? 'SELF_ONLY' : 'PUBLIC_TO_EVERYONE',
                'disable_duet' => false,
                'disable_comment' => false,
                'disable_stitch' => false,
            ],
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'video_size' => $fileSize,
            ],
        ];

        $initResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post(self::PUBLISH_URL, $initPayload);

        if ($initResponse->failed()) {
            throw new RuntimeException('TikTok publish init failed: ' . $initResponse->body());
        }

        $initData = $initResponse->json();
        $uploadUrl = $initData['data']['upload_url'] ?? null;
        $publishId = $initData['data']['publish_id'] ?? null;

        if (empty($uploadUrl)) {
            throw new RuntimeException('TikTok upload URL not returned');
        }

        // Step 2: Upload the video file
        $videoContent = file_get_contents($videoPath);

        $uploadResponse = Http::withHeaders([
            'Content-Type' => 'video/mp4',
            'Content-Range' => 'bytes 0-' . ($fileSize - 1) . '/' . $fileSize,
        ])->timeout(1800)->withBody($videoContent, 'video/mp4')->put($uploadUrl);

        if ($uploadResponse->failed()) {
            throw new RuntimeException('TikTok video upload failed: ' . $uploadResponse->body());
        }

        return [
            'external_id' => $publishId,
            'external_url' => null, // TikTok doesn't return URL immediately
            'response' => $initData,
        ];
    }
}
