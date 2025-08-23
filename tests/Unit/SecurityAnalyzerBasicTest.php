<?php

namespace Tests\Unit;

use App\Services\SecurityAnalyzer;
use Tests\TestCase;

/**
 * 基本安全分析器測試
 */
class SecurityAnalyzerBasicTest extends TestCase
{
    /** @test */
    public function security_analyzer_can_be_instantiated()
    {
        $analyzer = new SecurityAnalyzer();
        
        $this->assertInstanceOf(SecurityAnalyzer::class, $analyzer);
    }

    /** @test */
    public function security_analyzer_has_required_constants()
    {
        $this->assertArrayHasKey('login_failure', SecurityAnalyzer::SECURITY_EVENT_TYPES);
        $this->assertArrayHasKey('low', SecurityAnalyzer::RISK_LEVELS);
        $this->assertEquals(1, SecurityAnalyzer::RISK_LEVELS['low']);
    }
}