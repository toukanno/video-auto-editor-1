<?php

namespace Tests\Unit;

use App\Services\Video\SilenceDetectionService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SilenceDetectionServiceTest extends TestCase
{
    private SilenceDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SilenceDetectionService();
    }

    private function parseSilenceOutput(string $output): array
    {
        $ref = new ReflectionMethod(SilenceDetectionService::class, 'parseSilenceOutput');
        return $ref->invoke($this->service, $output);
    }

    public function test_parse_single_silence_segment(): void
    {
        $output = <<<'EOF'
[silencedetect @ 0x1234] silence_start: 1.5
[silencedetect @ 0x1234] silence_end: 3.2 | silence_duration: 1.7
EOF;

        $segments = $this->parseSilenceOutput($output);

        $this->assertCount(1, $segments);
        $this->assertEqualsWithDelta(1.5, $segments[0]['start'], 0.001);
        $this->assertEqualsWithDelta(3.2, $segments[0]['end'], 0.001);
        $this->assertEqualsWithDelta(1.7, $segments[0]['duration'], 0.001);
    }

    public function test_parse_multiple_silence_segments(): void
    {
        $output = <<<'EOF'
[silencedetect @ 0x1234] silence_start: 0.0
[silencedetect @ 0x1234] silence_end: 0.8 | silence_duration: 0.8
[silencedetect @ 0x1234] silence_start: 5.2
[silencedetect @ 0x1234] silence_end: 6.0 | silence_duration: 0.8
[silencedetect @ 0x1234] silence_start: 12.3
[silencedetect @ 0x1234] silence_end: 14.1 | silence_duration: 1.8
EOF;

        $segments = $this->parseSilenceOutput($output);

        $this->assertCount(3, $segments);
        $this->assertEqualsWithDelta(0.0, $segments[0]['start'], 0.001);
        $this->assertEqualsWithDelta(5.2, $segments[1]['start'], 0.001);
        $this->assertEqualsWithDelta(14.1, $segments[2]['end'], 0.001);
    }

    public function test_parse_empty_output(): void
    {
        $this->assertSame([], $this->parseSilenceOutput(''));
    }

    public function test_parse_output_with_no_silence(): void
    {
        $output = <<<'EOF'
frame=12345 fps=100 q=-0.0 Lsize=N/A time=00:01:30.00 bitrate=N/A speed=200x
EOF;

        $this->assertSame([], $this->parseSilenceOutput($output));
    }

    public function test_parse_ignores_orphan_silence_start(): void
    {
        // silence_start without matching silence_end
        $output = <<<'EOF'
[silencedetect @ 0x1234] silence_start: 10.0
EOF;

        $this->assertSame([], $this->parseSilenceOutput($output));
    }
}
