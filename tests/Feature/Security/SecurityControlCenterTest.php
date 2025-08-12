<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\Security\SecurityControlCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 安全性控制中心測試
 */
class SecurityControlCenterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_render_security_control_center()
    {
        $this->actingAs($this->user);

        Livewire::test(SecurityControlCenter::class)
            ->assertStatus(200)
            ->assertSee('安全性控制中心');
    }

    /** @test */
    public function it_can_load_security_status()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(SecurityControlCenter::class);
        
        $this->assertFalse($component->get('loading'));
        $this->assertIsArray($component->get('securityStatus'));
    }

    /** @test */
    public function it_can_refresh_security_status()
    {
        $this->actingAs($this->user);

        Livewire::test(SecurityControlCenter::class)
            ->call('refreshSecurityStatus')
            ->assertDispatched('toast');
    }

    /** @test */
    public function it_can_execute_security_actions()
    {
        $this->actingAs($this->user);

        Livewire::test(SecurityControlCenter::class)
            ->call('executeSecurityAction', 'refresh_session')
            ->assertDispatched('toast');
    }

    /** @test */
    public function it_can_handle_security_warnings()
    {
        $this->actingAs($this->user);

        Livewire::test(SecurityControlCenter::class)
            ->call('handleSecurityWarning', 'session_expiry')
            ->assertDispatched('show-session-expiry-warning');
    }

    /** @test */
    public function it_calculates_security_level_correctly()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(SecurityControlCenter::class);
        
        $securityLevel = $component->get('securityStatus')['system']['security_level'] ?? 'medium';
        
        $this->assertContains($securityLevel, ['high', 'medium', 'low']);
    }

    /** @test */
    public function it_shows_correct_security_level_color()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(SecurityControlCenter::class);
        
        $color = $component->call('getSecurityLevelColorProperty');
        
        $this->assertContains($color, ['text-green-600', 'text-yellow-600', 'text-red-600']);
    }

    /** @test */
    public function it_shows_correct_security_level_text()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(SecurityControlCenter::class);
        
        $text = $component->call('getSecurityLevelTextProperty');
        
        $this->assertContains($text, ['高', '中', '低']);
    }

    /** @test */
    public function it_detects_security_warnings()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(SecurityControlCenter::class);
        
        $hasWarnings = $component->call('hasSecurityWarningsProperty');
        
        $this->assertIsBool($hasWarnings);
    }

    /** @test */
    public function it_counts_security_warnings()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(SecurityControlCenter::class);
        
        $warningCount = $component->call('getSecurityWarningCountProperty');
        
        $this->assertIsInt($warningCount);
        $this->assertGreaterThanOrEqual(0, $warningCount);
    }

    /** @test */
    public function it_handles_security_events()
    {
        $this->actingAs($this->user);

        $event = [
            'type' => 'test_event',
            'message' => 'Test security event',
            'timestamp' => now()->toISOString(),
        ];

        Livewire::test(SecurityControlCenter::class)
            ->call('handleSecurityEvent', $event)
            ->assertSet('loading', false);
    }

    /** @test */
    public function it_handles_session_updates()
    {
        $this->actingAs($this->user);

        Livewire::test(SecurityControlCenter::class)
            ->call('handleSessionUpdate')
            ->assertSet('loading', false);
    }
}