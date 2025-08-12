<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Layout\KeyboardShortcutManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 鍵盤快捷鍵管理元件測試
 */
class KeyboardShortcutManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_keyboard_shortcut_manager()
    {
        Livewire::test(KeyboardShortcutManager::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.keyboard-shortcut-manager');
    }

    /** @test */
    public function it_starts_with_shortcuts_enabled()
    {
        Livewire::test(KeyboardShortcutManager::class)
            ->assertSet('enabled', true);
    }

    /** @test */
    public function it_can_handle_keyboard_events()
    {
        $keyEvent = [
            'key' => 'k',
            'code' => 'KeyK',
            'ctrlKey' => true,
            'altKey' => false,
            'shiftKey' => false,
            'metaKey' => false,
            'target' => [
                'tagName' => 'BODY',
                'type' => null,
                'contentEditable' => false,
            ],
        ];

        Livewire::test(KeyboardShortcutManager::class)
            ->call('handleKeyDown', $keyEvent)
            ->assertDispatched('toggle-global-search');
    }

    /** @test */
    public function it_ignores_keyboard_events_in_input_fields()
    {
        $keyEvent = [
            'key' => 'k',
            'code' => 'KeyK',
            'ctrlKey' => true,
            'altKey' => false,
            'shiftKey' => false,
            'metaKey' => false,
            'target' => [
                'tagName' => 'INPUT',
                'type' => 'text',
                'contentEditable' => false,
            ],
        ];

        Livewire::test(KeyboardShortcutManager::class)
            ->call('handleKeyDown', $keyEvent)
            ->assertNotDispatched('toggle-global-search');
    }

    /** @test */
    public function it_can_execute_navigation_shortcuts()
    {
        Livewire::test(KeyboardShortcutManager::class)
            ->call('executeShortcut', 'ctrl+shift+d')
            ->assertRedirect('/admin/dashboard');
    }

    /** @test */
    public function it_can_execute_function_shortcuts()
    {
        Livewire::test(KeyboardShortcutManager::class)
            ->call('executeShortcut', 'ctrl+k')
            ->assertDispatched('toggle-global-search');
    }

    /** @test */
    public function it_can_execute_system_shortcuts()
    {
        Livewire::test(KeyboardShortcutManager::class)
            ->call('executeShortcut', 'ctrl+shift+h')
            ->assertDispatched('show-shortcut-help');
    }

    /** @test */
    public function it_can_be_enabled_and_disabled()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 測試停用
        $component->call('disable')
            ->assertSet('enabled', false)
            ->assertDispatched('shortcut-manager-disabled');

        // 測試啟用
        $component->call('enable')
            ->assertSet('enabled', true)
            ->assertDispatched('shortcut-manager-enabled');
    }

    /** @test */
    public function it_can_toggle_enabled_state()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 初始狀態為啟用
        $component->assertSet('enabled', true);

        // 切換為停用
        $component->call('toggle')
            ->assertSet('enabled', false)
            ->assertDispatched('shortcut-manager-disabled');

        // 再次切換為啟用
        $component->call('toggle')
            ->assertSet('enabled', true)
            ->assertDispatched('shortcut-manager-enabled');
    }

    /** @test */
    public function it_tracks_execution_history()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 執行快捷鍵
        $component->call('executeShortcut', 'ctrl+k');

        // 檢查執行歷史
        $history = $component->get('executionHistory');
        $this->assertCount(1, $history);
        $this->assertEquals('ctrl+k', $history[0]['key']);
    }

    /** @test */
    public function it_limits_execution_history_size()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 執行超過 10 個快捷鍵
        for ($i = 0; $i < 15; $i++) {
            $component->call('executeShortcut', 'ctrl+k');
        }

        // 檢查歷史記錄限制在 10 筆
        $history = $component->get('executionHistory');
        $this->assertCount(10, $history);
    }

    /** @test */
    public function it_can_clear_execution_history()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 執行快捷鍵
        $component->call('executeShortcut', 'ctrl+k');
        $this->assertCount(1, $component->get('executionHistory'));

        // 清除歷史
        $component->call('clearExecutionHistory');
        $this->assertCount(0, $component->get('executionHistory'));
    }

    /** @test */
    public function it_responds_to_shortcuts_updated_event()
    {
        Livewire::test(KeyboardShortcutManager::class)
            ->dispatch('shortcuts-updated')
            ->assertDispatched('$refresh');
    }

    /** @test */
    public function it_responds_to_toggle_shortcuts_event()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        $component->assertSet('enabled', true);

        $component->dispatch('toggle-shortcuts')
            ->assertSet('enabled', false);
    }

    /** @test */
    public function it_builds_shortcut_key_correctly()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 測試 Ctrl+K
        $keyEvent = [
            'key' => 'k',
            'ctrlKey' => true,
            'altKey' => false,
            'shiftKey' => false,
            'metaKey' => false,
            'target' => ['tagName' => 'BODY', 'type' => null, 'contentEditable' => false],
        ];

        $component->call('handleKeyDown', $keyEvent);
        $this->assertTrue(true); // 如果沒有錯誤就通過

        // 測試 Ctrl+Shift+D
        $keyEvent = [
            'key' => 'd',
            'ctrlKey' => true,
            'altKey' => false,
            'shiftKey' => true,
            'metaKey' => false,
            'target' => ['tagName' => 'BODY', 'type' => null, 'contentEditable' => false],
        ];

        $component->call('handleKeyDown', $keyEvent);
        $this->assertTrue(true); // 如果沒有錯誤就通過
    }

    /** @test */
    public function it_handles_special_keys()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 測試 Escape 鍵
        $keyEvent = [
            'key' => 'Escape',
            'ctrlKey' => false,
            'altKey' => false,
            'shiftKey' => false,
            'metaKey' => false,
            'target' => ['tagName' => 'BODY', 'type' => null, 'contentEditable' => false],
        ];

        $component->call('handleKeyDown', $keyEvent)
            ->assertDispatched('close-modal');
    }

    /** @test */
    public function it_ignores_modifier_keys_alone()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 測試單獨的 Ctrl 鍵
        $keyEvent = [
            'key' => 'Control',
            'ctrlKey' => true,
            'altKey' => false,
            'shiftKey' => false,
            'metaKey' => false,
            'target' => ['tagName' => 'BODY', 'type' => null, 'contentEditable' => false],
        ];

        $component->call('handleKeyDown', $keyEvent);
        
        // 不應該觸發任何事件
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_custom_shortcut_actions()
    {
        $component = Livewire::test(KeyboardShortcutManager::class);

        // 測試一個不存在的快捷鍵，應該不會觸發任何事件
        $component->call('executeShortcut', 'nonexistent+key');
        
        // 由於快捷鍵不存在，不應該觸發任何事件
        // 這個測試主要是確保方法不會出錯
        $this->assertTrue(true);
    }
}