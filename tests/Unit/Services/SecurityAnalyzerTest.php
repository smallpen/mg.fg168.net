<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Services\SecurityAnalyzer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * 安全分析器服務測試
 */
class SecurityAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityAnalyzer $analyzer;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyzer = new SecurityAnalyzer();
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者'
        ]);
    }

    /** @test */
    public function it_can_analyze_login_failure_activity()
    {
        $activity = Activity::factory()->create([
            'type' => 'login',
            'result' => 'failed',
            'causer_id' => $this->testUser->id,
            'ip_address' => '192.168.1.100',
            'description' => '登入失敗'
        ]);

        $analysis = $this->analyzer->analyzeActivity($activity);

        $this->assertArrayHasKey('risk_score', $analysis);
        $this->assertArrayHasKey('risk_level', $analysis);
        $this->assertArrayHasKey('security_events', $analysis);
        
        // 登入失敗應該被檢測為安全事件
        $this->assertNotEmpty($analysis['security_events']);
        $this->assertEquals('login_failure', $analysis['security_events'][0]['type']);
    }

    /** @test */
    public function it_can_calculate_risk_score()
    {
        $activity = Activity::factory()->create([
            'type' => 'users.delete',
            'result' => 'success',
            'causer_id' => $this->testUser->id,
            'ip_address' => '10.0.0.1', // 外部 IP
            'created_at' => Carbon::now()->setHour(2) // 深夜時間
        ]);

        $riskScore = $this->analyzer->calculateRiskScore($activity);

        $this->assertIsInt($riskScore);
        $this->assertGreaterThan(0, $riskScore);
        $this->assertLessThanOrEqual(100, $riskScore);
    }

    /** @test */
    public function it_detects_privilege_escalation()
    {
        $activity = Activity::factory()->create([
            'type' => 'roles.assign',
            'description' => '指派管理員角色',
            'causer_id' => $this->testUser->id
        ]);

        $analysis = $this->analyzer->analyzeActivity($activity);
        
        $securityEvents = collect($analysis['security_events']);
        $privilegeEvent = $securityEvents->firstWhere('type', 'privilege_escalation');
        
        $this->assertNotNull($privilegeEvent);
        $this->assertEquals('high', $privilegeEvent['severity']);
    }

    /** @test */
    public function it_detects_suspicious_ip()
    {
        // 模擬可疑 IP
        cache()->put('suspicious_ips', ['10.0.0.1'], 3600);

        $activity = Activity::factory()->create([
            'type' => 'login',
            'ip_address' => '10.0.0.1',
            'causer_id' => $this->testUser->id
        ]);

        $analysis = $this->analyzer->analyzeActivity($activity);
        
        $securityEvents = collect($analysis['security_events']);
        $suspiciousIpEvent = $securityEvents->firstWhere('type', 'suspicious_ip');
        
        $this->assertNotNull($suspiciousIpEvent);
    }

    /** @test */
    public function it_can_detect_frequency_anomalies()
    {
        // 建立大量活動模擬高頻率
        $activities = collect();
        for ($i = 0; $i < 120; $i++) {
            $activities->push(Activity::factory()->create([
                'causer_id' => $this->testUser->id,
                'created_at' => Carbon::now()->subMinutes($i)
            ]));
        }

        $anomalies = $this->analyzer->detectAnomalies($activities);
        
        $frequencyAnomalies = collect($anomalies)->where('type', 'high_frequency');
        $this->assertNotEmpty($frequencyAnomalies);
    }

    /** @test */
    public function it_can_identify_user_patterns()
    {
        // 建立使用者活動模式
        Activity::factory()->count(10)->create([
            'causer_id' => $this->testUser->id,
            'type' => 'users.view',
            'created_at' => Carbon::now()->subDays(1)
        ]);

        Activity::factory()->count(5)->create([
            'causer_id' => $this->testUser->id,
            'type' => 'users.edit',
            'created_at' => Carbon::now()->subDays(2)
        ]);

        $patterns = $this->analyzer->identifyPatterns($this->testUser->id, '7d');

        $this->assertArrayHasKey('total_activities', $patterns);
        $this->assertArrayHasKey('activity_types', $patterns);
        $this->assertArrayHasKey('time_patterns', $patterns);
        $this->assertArrayHasKey('anomaly_score', $patterns);
        
        $this->assertEquals(15, $patterns['total_activities']);
    }

    /** @test */
    public function it_can_generate_security_report()
    {
        // 建立不同風險等級的活動
        Activity::factory()->create([
            'type' => 'login',
            'result' => 'failed',
            'risk_level' => SecurityAnalyzer::RISK_LEVELS['high'],
            'created_at' => Carbon::now()->subDays(1)
        ]);

        Activity::factory()->create([
            'type' => 'users.delete',
            'risk_level' => SecurityAnalyzer::RISK_LEVELS['medium'],
            'created_at' => Carbon::now()->subDays(2)
        ]);

        $report = $this->analyzer->generateSecurityReport('7d');

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('top_risks', $report);
        $this->assertArrayHasKey('recommendations', $report);
        
        $this->assertGreaterThan(0, $report['summary']['total_activities']);
    }

    /** @test */
    public function it_can_check_suspicious_ips()
    {
        // 建立來自同一 IP 的多次失敗登入
        for ($i = 0; $i < 8; $i++) {
            Activity::factory()->create([
                'type' => 'login',
                'result' => 'failed',
                'ip_address' => '10.0.0.1',
                'created_at' => Carbon::now()->subHours($i)
            ]);
        }

        $suspiciousIPs = $this->analyzer->checkSuspiciousIPs();

        $this->assertInstanceOf(Collection::class, $suspiciousIPs);
        $this->assertNotEmpty($suspiciousIPs);
        
        $firstIP = $suspiciousIPs->first();
        $this->assertEquals('10.0.0.1', $firstIP['ip_address']);
        $this->assertGreaterThan(70, $firstIP['risk_score']);
    }

    /** @test */
    public function it_can_monitor_failed_logins()
    {
        // 建立失敗登入記錄
        Activity::factory()->count(5)->create([
            'type' => 'login',
            'result' => 'failed',
            'ip_address' => '10.0.0.1',
            'created_at' => Carbon::now()->subHours(1)
        ]);

        Activity::factory()->count(3)->create([
            'type' => 'login',
            'result' => 'failed',
            'ip_address' => '10.0.0.2',
            'created_at' => Carbon::now()->subHours(2)
        ]);

        $analysis = $this->analyzer->monitorFailedLogins();

        $this->assertArrayHasKey('total_failures', $analysis);
        $this->assertArrayHasKey('unique_ips', $analysis);
        $this->assertArrayHasKey('brute_force_attempts', $analysis);
        
        $this->assertEquals(8, $analysis['total_failures']);
        $this->assertEquals(2, $analysis['unique_ips']);
    }

    /** @test */
    public function it_can_generate_security_alert()
    {
        $activity = Activity::factory()->create([
            'type' => 'login',
            'result' => 'failed',
            'causer_id' => $this->testUser->id,
            'ip_address' => '10.0.0.1'
        ]);

        $securityEvents = [
            [
                'type' => 'login_failure',
                'description' => '登入失敗嘗試',
                'severity' => 'high'
            ]
        ];

        $alert = $this->analyzer->generateSecurityAlert($activity, $securityEvents);

        $this->assertInstanceOf(SecurityAlert::class, $alert);
        $this->assertEquals($activity->id, $alert->activity_id);
        $this->assertEquals('login_failure', $alert->type);
        $this->assertEquals('high', $alert->severity);
    }

    /** @test */
    public function it_does_not_generate_alert_for_low_risk_events()
    {
        $activity = Activity::factory()->create([
            'type' => 'dashboard.view',
            'result' => 'success',
            'causer_id' => $this->testUser->id
        ]);

        $securityEvents = [
            [
                'type' => 'normal_access',
                'description' => '正常存取',
                'severity' => 'low'
            ]
        ];

        $alert = $this->analyzer->generateSecurityAlert($activity, $securityEvents);

        $this->assertNull($alert);
    }

    /** @test */
    public function it_calculates_higher_risk_for_off_hours_activity()
    {
        $normalHoursActivity = Activity::factory()->create([
            'type' => 'users.view',
            'causer_id' => $this->testUser->id,
            'created_at' => Carbon::now()->setHour(14) // 下午 2 點
        ]);

        $offHoursActivity = Activity::factory()->create([
            'type' => 'users.view',
            'causer_id' => $this->testUser->id,
            'created_at' => Carbon::now()->setHour(2) // 凌晨 2 點
        ]);

        $normalRisk = $this->analyzer->calculateRiskScore($normalHoursActivity);
        $offHoursRisk = $this->analyzer->calculateRiskScore($offHoursActivity);

        $this->assertGreaterThan($normalRisk, $offHoursRisk);
    }

    /** @test */
    public function it_detects_bulk_operations()
    {
        $activity = Activity::factory()->create([
            'type' => 'users.create',
            'description' => '批量建立使用者',
            'properties' => ['batch_size' => 15],
            'causer_id' => $this->testUser->id
        ]);

        $analysis = $this->analyzer->analyzeActivity($activity);
        
        $securityEvents = collect($analysis['security_events']);
        $bulkEvent = $securityEvents->firstWhere('type', 'bulk_operation');
        
        $this->assertNotNull($bulkEvent);
        $this->assertEquals('medium', $bulkEvent['severity']);
    }
}