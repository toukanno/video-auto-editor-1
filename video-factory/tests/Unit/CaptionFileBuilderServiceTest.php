<?php

namespace Tests\Unit;

use App\Services\Caption\CaptionFileBuilderService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CaptionFileBuilderServiceTest extends TestCase
{
    private CaptionFileBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaptionFileBuilderService();
    }

    private function invokePrivate(string $method, array $args): mixed
    {
        $ref = new ReflectionMethod(CaptionFileBuilderService::class, $method);
        return $ref->invoke($this->service, ...$args);
    }

    // ── msToSrtTime ──

    public function test_ms_to_srt_time_zero(): void
    {
        $this->assertSame('00:00:00,000', $this->invokePrivate('msToSrtTime', [0]));
    }

    public function test_ms_to_srt_time_simple(): void
    {
        // 1500ms = 1s 500ms
        $this->assertSame('00:00:01,500', $this->invokePrivate('msToSrtTime', [1500]));
    }

    public function test_ms_to_srt_time_full(): void
    {
        // 1h 23m 45s 678ms = 5025678ms
        $this->assertSame('01:23:45,678', $this->invokePrivate('msToSrtTime', [5025678]));
    }

    public function test_ms_to_srt_time_exact_minute(): void
    {
        // 60000ms = 1m
        $this->assertSame('00:01:00,000', $this->invokePrivate('msToSrtTime', [60000]));
    }

    // ── msToAssTime ──

    public function test_ms_to_ass_time_zero(): void
    {
        $this->assertSame('0:00:00.00', $this->invokePrivate('msToAssTime', [0]));
    }

    public function test_ms_to_ass_time_simple(): void
    {
        // 1500ms = 1s 50cs
        $this->assertSame('0:00:01.50', $this->invokePrivate('msToAssTime', [1500]));
    }

    public function test_ms_to_ass_time_full(): void
    {
        // 1h 23m 45s 670ms = 5025670ms → 67cs
        $this->assertSame('1:23:45.67', $this->invokePrivate('msToAssTime', [5025670]));
    }

    public function test_ms_to_ass_time_truncates_below_10ms(): void
    {
        // 1005ms → 1s 0cs (5ms truncated to 0cs)
        $this->assertSame('0:00:01.00', $this->invokePrivate('msToAssTime', [1005]));
    }

    // ── hexToAss ──

    public function test_hex_to_ass_rgb_to_bgr(): void
    {
        // #FF0000 (red) → &H000000FF (BGR)
        $this->assertSame('&H000000FF', $this->invokePrivate('hexToAss', ['#FF0000']));
    }

    public function test_hex_to_ass_white(): void
    {
        $this->assertSame('&H00FFFFFF', $this->invokePrivate('hexToAss', ['#FFFFFF']));
    }

    public function test_hex_to_ass_without_hash(): void
    {
        // 00FF00 (green) → &H0000FF00
        $this->assertSame('&H0000FF00', $this->invokePrivate('hexToAss', ['00FF00']));
    }

    public function test_hex_to_ass_invalid_returns_white(): void
    {
        $this->assertSame('&H00FFFFFF', $this->invokePrivate('hexToAss', ['invalid']));
    }

    public function test_hex_to_ass_short_hex_returns_white(): void
    {
        $this->assertSame('&H00FFFFFF', $this->invokePrivate('hexToAss', ['#FFF']));
    }

    // ── wrapText ──

    public function test_wrap_text_short_returns_unchanged(): void
    {
        $this->assertSame('短いテキスト', $this->invokePrivate('wrapText', ['短いテキスト', 15, 2]));
    }

    public function test_wrap_text_breaks_at_punctuation(): void
    {
        // 20 chars total, charsPerLine=10, should break after 、
        $text = 'これはテスト、ここで折り返しになるはず';
        $result = $this->invokePrivate('wrapText', [$text, 10, 2]);
        $this->assertStringContainsString('\N', $result);
    }

    public function test_wrap_text_respects_max_lines(): void
    {
        $text = str_repeat('あ', 50);
        $result = $this->invokePrivate('wrapText', [$text, 10, 2]);
        $parts = explode('\N', $result);
        $this->assertLessThanOrEqual(2, count($parts));
    }
}
