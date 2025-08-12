<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Session 安全性測試
 */
class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected SessionSecurityService $sessionService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sessionService = app(SessionSecurityService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_check_session_security_status()
    {
        $this->actingAs($this->user);
        
        $securityCheck = $this->sessionService->checkSessionSecurity($this->user);
        
        $this->assertIsArray($securityCheck);
        $this->assertArrayHasKey('is_expired', $securityCheck);
        $this->assertArrayHasKey('needs_refresh', $securityCheck);
        $this->assertArrayHasKey('suspicious_activity', $securityCheck);
        $this->assertArrayHasKey('concurrent_sessions', $securityCheck);
    }

    /** @test */
    public function it_detects_expired_sessions()
    {
        $this->actingAs($this->user);
        
        // 設定過期的最後活動時間
        session(['last_activity' => now()->subMinutes(35)->timestamp]);
        
        $isExpired = $this->sessionService->isSessionExpired();
        
        $this->assertTrue($isExpired);
    }

    /** @test */
    public function it_detects_sessions_needing_refresh()
    {
        $this->actingAs($this->user);
        
        // 設定需要刷新的最後活動時間（25 分鐘前）
        session(['last_activity' => now()->subMinutes(25)->timestamp]);
        
        $needsRefresh = $this->sessionService->needsRefresh();
        
        $this->assertTrue($needsRefresh);
    }

    /** @test */
    public function it_can_refresh_session()
    {
        $this->actingAs($this->user);
        
        $oldSessionId = session()->getId();
        
        $this->sessionService->refreshSession();
        
        $newSessionId = session()->getId();
        
        $this->assertNotEquals($oldSessionId, $newSessionId);
        $this->assertNotNull(session('last_activity'));
    }

    /** @test */
    public function it_detects_suspicious_ip_change()
    {
        $this->actingAs($this->user);
        
        // 設定舊的 IP
        session(['last_ip' => '192.168.1.100']);
        
        // 模擬新的 IP
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.200');
        
        $hasSuspiciousActivity = $this->sessionService->detectSuspiciousActivity($this->user);
        
        $this->assertTrue($hasSuspiciousActivity);
    }

    /** @test */
    public function it_detects_suspicious_user_agent_change()
    {
        $this->actingAs($this->user);
        
        // 設定舊的 User Agent
        session(['last_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36']);
        
        // 模擬新的 User Agent
        $this->app['request']->server->set('HTTP_USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36');
        
        $hasSuspiciousActivity = $this->sessionService->detectSuspiciousActivity($this->user);
        
        $this->assertTrue($hasSuspiciousActivity);
    }

    /** @test */
    public function it_can_get_concurrent_sessions()
    {
        $this->actingAs($this->user);
        
        // 建立多個 Session 記錄
        DB::table('sessions')->insert([
            [
                'id' => 'session1',
                'user_id' => $this->user->id,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'session2',
                'user_id' => $this->user->id,
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
        ]);
        
        $sessions = $this->sessionService->getConcurrentSessions($this->user);
        
        $this->assertCount(2, $sessions);
        $this->assertEquals('192.168.1.100', $sessions[0]['ip_address']);
        $this->assertEquals('192.168.1.101', $sessions[1]['ip_address']);
    }

    /** @test */
    public function it_can_terminate_other_sessions()
    {
        $this->actingAs($this->user);
        
        // 建立其他 Session
        DB::table('sessions')->insert([
            'id' => 'other_session',
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
        
        $success = $this->sessionService->terminateOtherSessions($this->user, 'password');
        
        $this->assertTrue($success);
        
        // 驗證其他 Session 已被刪除
        $remainingSessions = DB::table('sessions')
            ->where('user_id', $this->user->id)
            ->where('id', '!=', session()->getId())
            ->count();
            
        $this->assertEquals(0, $remainingSessions);
    }

    /** @test */
    public function it_can_force_logout_user()
    {
        $this->actingAs($this->user);
        
        // 建立 Session 記錄
        DB::table('sessions')->insert([
            'id' => session()->getId(),
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
        
        $this->sessionService->forceLogout($this->user, 'security');
        
        // 驗證所有 Session 已被刪除
        $sessionCount = DB::table('sessions')
            ->where('user_id', $this->user->id)
            ->count();
            
        $this->assertEquals(0, $sessionCount);
    }

    /** @test */
    public function it_can_cleanup_expired_sessions()
    {
        // 建立過期的 Session
        DB::table('sessions')->insert([
            [
                'id' => 'expired_session',
                'user_id' => $this->user->id,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->subHours(3)->timestamp, // 3 小時前
            ],
            [
                'id' => 'active_session',
                'user_id' => $this->user->id,
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp, // 現在
            ],
        ]);
        
        $cleanedCount = $this->sessionService->cleanupExpiredSessions();
        
        $this->assertEquals(1, $cleanedCount);
        
        // 驗證只有活躍的 Session 保留
        $remainingSessions = DB::table('sessions')->count();
        $this->assertEquals(1, $remainingSessions);
    }

    /** @test */
    public function it_can_get_session_stats()
    {
        // 建立不同類型的 Session
        DB::table('sessions')->insert([
            [
                'id' => 'auth_session',
                'user_id' => $this->user->id,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'guest_session',
                'user_id' => null,
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'expired_session',
                'user_id' => $this->user->id,
                'ip_address' => '192.168.1.102',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 10)',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->subHours(2)->timestamp, // 過期
            ],
        ]);
        
        $stats = $this->sessionService->getSessionStats();
        
        $this->assertEquals(3, $stats['total_sessions']);
        $this->assertEquals(2, $stats['active_sessions']); // 2 個活躍
        $this->assertEquals(1, $stats['authenticated_sessions']); // 1 個已認證
        $this->assertEquals(1, $stats['guest_sessions']); // 1 個訪客
    }

    /** @test */
    public function it_shows_idle_timeout_warning()
    {
        $this->actingAs($this->user);
        
        // 設定接近過期的最後活動時間（26 分鐘前）
        session(['last_activity' => now()->subMinutes(26)->timestamp]);
        
        $shouldShowWarning = $this->sessionService->shouldShowIdleWarning();
        
        $this->assertTrue($shouldShowWarning);
    }

    /** @test */
    public function it_does_not_show_warning_for_active_sessions()
    {
        $this->actingAs($this->user);
        
        // 設定最近的活動時間（5 分鐘前）
        session(['last_activity' => now()->subMinutes(5)->timestamp]);
        
        $shouldShowWarning = $this->sessionService->shouldShowIdleWarning();
        
        $this->assertFalse($shouldShowWarning);
    }
}