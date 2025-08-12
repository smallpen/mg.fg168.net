<?php

namespace Tests\Feature;

use App\Services\KeyboardShortcutService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 鍵盤快捷鍵功能測試
 */
class KeyboardShortcutTest extends TestCase
{
    use RefreshDatabase;

    protected KeyboardShortcutService $shortcutService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->shortcutService = app(KeyboardShortcutService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_get_default_shortcuts()
    {
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        
        $this->assertNotEmpty($shortcuts);
        $this->assertTrue($shortcuts->has('ctrl+shift+d'));
        $this->assertTrue($shortcuts->has('ctrl+k'));
        $this->assertTrue($shortcuts->has('escape'));
    }

    /** @test */
    public function it_can_register_new_shortcut()
    {
        $key = 'ctrl+shift+x';
        $config = [
            'action' => 'navigate',
            'target' => '/admin/test',
            'description' => '測試快捷鍵',
            'category' => 'custom',
            'enabled' => true,
        ];

        $result = $this->shortcutService->registerShortcut($key, $config, $this->user->id);
        
        $this->assertTrue($result);
        
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertTrue($shortcuts->has($key));
        $this->assertEquals($config['description'], $shortcuts->get($key)['description']);
    }

    /** @test */
    public function it_prevents_duplicate_shortcuts()
    {
        $key = 'ctrl+shift+d'; // 已存在的快捷鍵
        $config = [
            'action' => 'navigate',
            'target' => '/admin/test',
            'description' => '重複的快捷鍵',
            'category' => 'custom',
            'enabled' => true,
        ];

        $result = $this->shortcutService->registerShortcut($key, $config, $this->user->id);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_validates_shortcut_key_format()
    {
        // 有效的快捷鍵格式
        $this->assertTrue($this->shortcutService->isValidShortcutKey('ctrl+k'));
        $this->assertTrue($this->shortcutService->isValidShortcutKey('ctrl+shift+n'));
        $this->assertTrue($this->shortcutService->isValidShortcutKey('alt+f4'));
        $this->assertTrue($this->shortcutService->isValidShortcutKey('escape'));
        
        // 無效的快捷鍵格式
        $this->assertFalse($this->shortcutService->isValidShortcutKey('k')); // 沒有修飾鍵
        $this->assertFalse($this->shortcutService->isValidShortcutKey('ctrl+')); // 不完整
        $this->assertFalse($this->shortcutService->isValidShortcutKey('invalid+key')); // 無效修飾鍵
    }

    /** @test */
    public function it_can_update_shortcut()
    {
        $key = 'ctrl+shift+x';
        $originalConfig = [
            'action' => 'navigate',
            'target' => '/admin/test',
            'description' => '原始說明',
            'category' => 'custom',
            'enabled' => true,
        ];

        $this->shortcutService->registerShortcut($key, $originalConfig, $this->user->id);

        $updatedConfig = [
            'action' => 'toggle-search',
            'target' => null,
            'description' => '更新後的說明',
            'category' => 'function',
            'enabled' => true,
        ];

        $result = $this->shortcutService->updateShortcut($key, $updatedConfig, $this->user->id);
        
        $this->assertTrue($result);
        
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertEquals($updatedConfig['description'], $shortcuts->get($key)['description']);
        $this->assertEquals($updatedConfig['action'], $shortcuts->get($key)['action']);
    }

    /** @test */
    public function it_can_remove_shortcut()
    {
        $key = 'ctrl+shift+x';
        $config = [
            'action' => 'navigate',
            'target' => '/admin/test',
            'description' => '測試快捷鍵',
            'category' => 'custom',
            'enabled' => true,
        ];

        $this->shortcutService->registerShortcut($key, $config, $this->user->id);
        
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertTrue($shortcuts->has($key));

        $result = $this->shortcutService->removeShortcut($key, $this->user->id);
        
        $this->assertTrue($result);
        
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertFalse($shortcuts->has($key));
    }

    /** @test */
    public function it_can_detect_conflicts()
    {
        $existingKey = 'ctrl+shift+d';
        
        $this->assertTrue($this->shortcutService->hasConflict($existingKey, $this->user->id));
        $this->assertFalse($this->shortcutService->hasConflict('ctrl+shift+z', $this->user->id));
    }

    /** @test */
    public function it_can_get_shortcuts_by_category()
    {
        $navigationShortcuts = $this->shortcutService->getShortcutsByCategory('navigation', $this->user->id);
        $functionShortcuts = $this->shortcutService->getShortcutsByCategory('function', $this->user->id);
        
        $this->assertNotEmpty($navigationShortcuts);
        $this->assertNotEmpty($functionShortcuts);
        
        // 檢查分類正確性
        foreach ($navigationShortcuts as $shortcut) {
            $this->assertEquals('navigation', $shortcut['category']);
        }
        
        foreach ($functionShortcuts as $shortcut) {
            $this->assertEquals('function', $shortcut['category']);
        }
    }

    /** @test */
    public function it_can_reset_to_defaults()
    {
        // 新增自訂快捷鍵
        $customKey = 'ctrl+shift+x';
        $customConfig = [
            'action' => 'navigate',
            'target' => '/admin/test',
            'description' => '自訂快捷鍵',
            'category' => 'custom',
            'enabled' => true,
        ];

        $this->shortcutService->registerShortcut($customKey, $customConfig, $this->user->id);
        
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertTrue($shortcuts->has($customKey));

        // 重置為預設
        $result = $this->shortcutService->resetToDefaults($this->user->id);
        
        $this->assertTrue($result);
        
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertFalse($shortcuts->has($customKey));
        $this->assertTrue($shortcuts->has('ctrl+shift+d')); // 預設快捷鍵仍存在
    }

    /** @test */
    public function it_can_export_shortcuts()
    {
        $shortcuts = $this->shortcutService->exportShortcuts($this->user->id);
        
        $this->assertIsArray($shortcuts);
        $this->assertNotEmpty($shortcuts);
        $this->assertArrayHasKey('ctrl+shift+d', $shortcuts);
    }

    /** @test */
    public function it_can_import_shortcuts()
    {
        $importShortcuts = [
            'ctrl+shift+x' => [
                'action' => 'navigate',
                'target' => '/admin/test1',
                'description' => '匯入的快捷鍵 1',
                'category' => 'custom',
                'enabled' => true,
            ],
            'ctrl+shift+y' => [
                'action' => 'toggle-search',
                'target' => null,
                'description' => '匯入的快捷鍵 2',
                'category' => 'function',
                'enabled' => true,
            ],
        ];

        $results = $this->shortcutService->importShortcuts($importShortcuts, false, $this->user->id);
        
        $this->assertEquals(2, $results['imported']);
        $this->assertEquals(0, $results['skipped']);
        $this->assertEmpty($results['conflicts']);
        
        $shortcuts = $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertTrue($shortcuts->has('ctrl+shift+x'));
        $this->assertTrue($shortcuts->has('ctrl+shift+y'));
    }

    /** @test */
    public function it_handles_import_conflicts()
    {
        $importShortcuts = [
            'ctrl+shift+d' => [ // 已存在的快捷鍵
                'action' => 'navigate',
                'target' => '/admin/conflict',
                'description' => '衝突的快捷鍵',
                'category' => 'custom',
                'enabled' => true,
            ],
            'ctrl+shift+z' => [ // 新的快捷鍵
                'action' => 'toggle-search',
                'target' => null,
                'description' => '新的快捷鍵',
                'category' => 'function',
                'enabled' => true,
            ],
        ];

        $results = $this->shortcutService->importShortcuts($importShortcuts, false, $this->user->id);
        
        $this->assertEquals(1, $results['imported']); // 只匯入了新的快捷鍵
        $this->assertEquals(1, $results['skipped']); // 跳過了衝突的快捷鍵
        $this->assertContains('ctrl+shift+d', $results['conflicts']);
    }

    /** @test */
    public function it_caches_user_shortcuts()
    {
        // 清除快取
        Cache::forget("keyboard_shortcuts_{$this->user->id}");
        
        // 第一次呼叫應該建立快取
        $shortcuts1 = $this->shortcutService->getUserShortcuts($this->user->id);
        
        // 檢查快取是否存在
        $this->assertTrue(Cache::has("keyboard_shortcuts_{$this->user->id}"));
        
        // 第二次呼叫應該從快取讀取
        $shortcuts2 = $this->shortcutService->getUserShortcuts($this->user->id);
        
        $this->assertEquals($shortcuts1->toArray(), $shortcuts2->toArray());
    }

    /** @test */
    public function it_clears_cache_when_shortcuts_change()
    {
        // 建立快取
        $this->shortcutService->getUserShortcuts($this->user->id);
        $this->assertTrue(Cache::has("keyboard_shortcuts_{$this->user->id}"));
        
        // 新增快捷鍵應該清除快取
        $this->shortcutService->registerShortcut('ctrl+shift+x', [
            'action' => 'navigate',
            'target' => '/admin/test',
            'description' => '測試快捷鍵',
            'category' => 'custom',
            'enabled' => true,
        ], $this->user->id);
        
        // 快取應該被清除
        $this->assertFalse(Cache::has("keyboard_shortcuts_{$this->user->id}"));
    }
}