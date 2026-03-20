<?php

namespace Tests\Unit;

use App\Services\Caption\TranscriptNormalizerService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TranscriptNormalizerServiceTest extends TestCase
{
    private TranscriptNormalizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TranscriptNormalizerService();
    }

    private function ruleBasedClean(string $text): string
    {
        $ref = new ReflectionMethod(TranscriptNormalizerService::class, 'ruleBasedClean');
        return $ref->invoke($this->service, $text);
    }

    public function test_removes_filler_ee(): void
    {
        $this->assertSame('今日は天気がいいですね', $this->ruleBasedClean('えー今日は天気がいいですね'));
    }

    public function test_removes_filler_eetto_with_comma(): void
    {
        $this->assertSame('始めましょう', $this->ruleBasedClean('えーっと、始めましょう'));
    }

    public function test_removes_filler_ano_with_comma(): void
    {
        $this->assertSame('これは重要です', $this->ruleBasedClean('あの、これは重要です'));
    }

    public function test_removes_filler_anoo(): void
    {
        $this->assertSame('次の話題ですが', $this->ruleBasedClean('あのー、次の話題ですが'));
    }

    public function test_removes_filler_maa(): void
    {
        $this->assertSame('そうですね', $this->ruleBasedClean('まあ、そうですね'));
    }

    public function test_removes_multiple_fillers(): void
    {
        $this->assertSame('今日のテーマです', $this->ruleBasedClean('えー、あの、今日のテーマです'));
    }

    public function test_trims_whitespace(): void
    {
        $this->assertSame('テスト', $this->ruleBasedClean('  テスト  '));
    }

    public function test_no_fillers_returns_unchanged(): void
    {
        $this->assertSame('普通のテキストです', $this->ruleBasedClean('普通のテキストです'));
    }

    public function test_empty_string(): void
    {
        $this->assertSame('', $this->ruleBasedClean(''));
    }

    public function test_only_fillers_returns_empty(): void
    {
        $this->assertSame('', $this->ruleBasedClean('えー、あの、'));
    }
}
