<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\User;
use App\Services\SecurityAnalyzer;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityAnalyzer $securityAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityAnalyzer = new SecurityAnalyzer();
    }

    /**
     * 測試安全分析器基本功能
     */
    public function test_security_analyzer_can_analyze_activity(): void
    {
        // 建立測試使用者
        $user = User::factory()->create();

        // 建立測試活動
        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入',
            'subject_type' => User::class,
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0',
            'result' => 'success',
            'properties' => ['username' => $user->username]
        ]);

        // 執行安全分析
        $analysis = $this->securityAnalyzer->analyzeActivity($activity);

        // 驗證分析結果結構
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('activity_id', $analysis);
        $this->assertArrayHasKey('risk_score', $analysis);
        $this->assertArrayHasKey('risk_level', $analysis);
        $this->assertArrayHasKey('security_events', $analysis);
        $this->assertArrayHasKey('anomalies', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);

        // 驗證基本值
        $this->assertEquals($activity->id, $analysis['activity_id']);
        $this->assertIsInt($analysis['risk_score']);
        $this->assertIsString($analysis['risk_level']);
        $this->assertIsArray($analysis['security_events']);
    }

    /**
     * 測試登入失敗檢測
     */
    public function test_detects_login_failure(): void
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'type' => 'login',
            'description' => '登入失敗',
            'subject_type' => User::class,
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'result' => 'failed',
            'properties' => ['username' => $user->username, 'failure_reason' => 'invalid_password']
        ]);

        $analysis = $this->securityAnalyzer->analyzeActivity($activity);

        // 應該檢測到登入失敗事件
        $this->assertNotEmpty($analysis['security_events']);
        
        $loginFailureEvent = collect($analysis['security_events'])
            ->firstWhere('type', 'login_failure');
        
        $this->assertNotNull($loginFailureEvent);
        $this->assertEquals('檢測到登入失敗嘗試', $loginFailureEvent['description']);
    }

    /**
     * 測試風險評分計算
     */
    public function test_calculates_risk_score(): void
    {
        $user = User::factory()->create();

        // 低風險活動
        $lowRiskActivity = Activity::create([
            'type' => 'login',
            'description' => '正常登入',
            'subject_type' => User::class,
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'result' => 'success'
        ]);

        // 高風險活動
        $highRiskActivity = Activity::create([
            'type' => 'delete',
            'description' => '刪除重要資料',
            'subject_type' => User::class,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1', // 可疑 IP
            'result' => 'success',
            'created_at' => now()->setHour(2) // 深夜時間
        ]);

        $lowRiskAnalysis = $this->securityAnalyzer->analyzeActivity($lowRiskActivity);
        $highRiskAnalysis = $this->securityAnalyzer->analyzeActivity($highRiskActivity);

        // 高風險活動應該有更高的風險分數
        $this->assertGreaterThan(
            $lowRiskAnalysis['risk_score'],
            $highRiskAnalysis['risk_score']
        );
    }

    /**
     * 測試可疑 IP 檢查
     */
    public function test_checks_suspicious_ips(): void
    {
        $user = User::factory()->create();

        // 建立多個失敗的登入嘗試
        for ($i = 0; $i < 6; $i++) {
            Activity::create([
                'type' => 'login',
                'description' => '登入失敗',
                'subject_type' => User::class,
                'user_id' => $user->id,
                'ip_address' => '10.0.0.1',
                'result' => 'failed',
                'created_at' => now()->subMinutes($i)
            ]);
        }

        $suspiciousIPs = $this->securityAnalyzer->checkSuspiciousIPs();

        $this->assertNotEmpty($suspiciousIPs);
        $this->assertTrue($suspiciousIPs->contains('ip_address', '10.0.0.1'));
    }

    /**
     * 測試登入失敗監控
     */
    public function test_monitors_failed_logins(): void
    {
        $user = User::factory()->create();

        // 建立多個失敗的登入嘗試
        for ($i = 0; $i < 8; $i++) {
            Activity::create([
                'type' => 'login',
                'description' => '登入失敗',
                'subject_type' => User::class,
                'user_id' => $user->id,
                'ip_address' => '10.0.0.1',
                'result' => 'failed',
                'properties' => ['username' => $user->username],
                'created_at' => now()->subMinutes($i)
            ]);
        }

        $failedLogins = $this->securityAnalyzer->monitorFailedLogins();

        $this->assertGreaterThan(0, $failedLogins['total_failures']);
        $this->assertNotEmpty($failedLogins['by_ip']);
        
        $ipAnalysis = collect($failedLogins['by_ip'])
            ->firstWhere('ip_address', '10.0.0.1');
        
        $this->assertNotNull($ipAnalysis);
        $this->assertGreaterThanOrEqual(5, $ipAnalysis['failure_count']);
    }

    /**
     * 測試安全報告生成
     */
    public function test_generates_security_report(): void
    {
        $user = User::factory()->create();

        // 建立一些測試活動
        Activity::create([
            'type' => 'login',
            'description' => '正常登入',
            'subject_type' => User::class,
            'user_id' => $user->id,
            'result' => 'success'
        ]);

        Activity::create([
            'type' => 'delete',
            'description' => '刪除資料',
            'subject_type' => User::class,
            'user_id' => $user->id,
            'result' => 'success',
            'risk_level' => 3
        ]);

        $report = $this->securityAnalyzer->generateSecurityReport('1d');

        $this->assertIsArray($report);
        $this->assertArrayHasKey('time_range', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('top_risks', $report);
        $this->assertArrayHasKey('suspicious_ips', $report);
        $this->assertArrayHasKey('failed_logins', $report);
        $this->assertArrayHasKey('recommendations', $report);

        $this->assertEquals('1d', $report['time_range']);
        $this->assertGreaterThan(0, $report['summary']['total_activities']);
    }
}
