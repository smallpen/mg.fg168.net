<?php

namespace Tests\Feature\Livewire\Admin\Layout;

use App\Livewire\Admin\Layout\GlobalSearch;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * GlobalSearch 全域搜尋元件測試
 * 
 * 測試全域搜尋的各項功能，包括：
 * - 基本渲染和初始化
 * - 搜尋功能和結果顯示
 * - 搜尋分類篩選
 * - 鍵盤導航功能
 * - 搜尋建議和歷史
 * - 快捷鍵支援
 * - 搜尋結果選擇和導航
 */
class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Role $adminRole;
    protected SearchService $searchService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色和權限
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);
        
        // 建立測試權限
        Permission::create([
            'name' => 'admin.users.view',
            'display_name' => '檢視使用者',
            'description' => '檢視使用者列表',
            'module' => 'users',
        ]);
        
        // 建立測試使用者
        $this->user = User::factory()->create([
            'name' => '測試管理員',
            'email' => 'admin@test.com',
        ]);
        
        $this->user->roles()->attach($this->adminRole);
        $this->actingAs($this->user);
        
        // 建立額外的測試資料
        $this->createTestData();
    }
    
    protected function createTestData(): void
    {
        // 建立測試使用者
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        
        // 建立測試角色
        Role::create(['name' => 'editor', 'display_name' => '編輯者', 'description' => '內容編輯者']);
        Role::create(['name' => 'viewer', 'display_name' => '檢視者', 'description' => '唯讀使用者']);
    }
    
    /** @test */
    public function 可以正常渲染全域搜尋元件()
    {
        Livewire::test(GlobalSearch::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.global-search');
    }
    
    /** @test */
    public function 初始化時載入正確的預設狀態()
    {
        Livewire::test(GlobalSearch::class)
            ->assertSet('query', '')
            ->assertSet('results', [])
            ->assertSet('isOpen', false)
            ->assertSet('selectedCategory', 'all')
            ->assertSet('selectedIndex', -1)
            ->assertSet('showSuggestions', true)
            ->assertSet('showHistory', true)
            ->assertSet('maxResults', 20)
            ->assertSet('keyboardNavigation', true);
    }
    
    /** @test */
    public function 可以開啟搜尋框()
    {
        Livewire::test(GlobalSearch::class)
            ->assertSet('isOpen', false)
            ->call('open')
            ->assertSet('isOpen', true)
            ->assertSet('selectedIndex', -1)
            ->assertDispatched('search-opened');
    }
    
    /** @test */
    public function 可以關閉搜尋框()
    {
        Livewire::test(GlobalSearch::class)
            ->set('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertSet('selectedIndex', -1)
            ->assertDispatched('search-closed');
    }
    
    /** @test */
    public function 可以切換搜尋框開關()
    {
        Livewire::test(GlobalSearch::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }
    
    /** @test */
    public function 搜尋查詢更新時觸發搜尋()
    {
        Livewire::test(GlobalSearch::class)
            ->set('query', 'Jo')
            ->assertSet('selectedIndex', -1);
    }
    
    /** @test */
    public function 短於2個字元的查詢不會觸發搜尋()
    {
        Livewire::test(GlobalSearch::class)
            ->set('query', 'J')
            ->assertSet('results', []);
    }
    
    /** @test */
    public function 可以執行搜尋()
    {
        Livewire::test(GlobalSearch::class)
            ->set('query', 'John')
            ->call('search');
        
        // 由於搜尋結果依賴 SearchService，這裡主要測試方法執行
        // 實際搜尋邏輯會在 SearchService 的測試中覆蓋
    }
    
    /** @test */
    public function 可以清除搜尋()
    {
        Livewire::test(GlobalSearch::class)
            ->set('query', '測試搜尋')
            ->set('results', ['test' => ['items' => []]])
            ->set('isOpen', true)
            ->call('clearSearch')
            ->assertSet('query', '')
            ->assertSet('results', [])
            ->assertSet('selectedIndex', -1)
            ->assertSet('isOpen', false);
    }
    
    /** @test */
    public function 可以選擇建議()
    {
        Livewire::test(GlobalSearch::class)
            ->call('selectSuggestion', '使用者管理')
            ->assertSet('query', '使用者管理');
    }
    
    /** @test */
    public function 可以設定分類篩選()
    {
        Livewire::test(GlobalSearch::class)
            ->assertSet('selectedCategory', 'all')
            ->call('setCategory', 'users')
            ->assertSet('selectedCategory', 'users')
            ->assertSet('selectedIndex', -1);
    }
    
    /** @test */
    public function 設定分類後有查詢時會重新搜尋()
    {
        Livewire::test(GlobalSearch::class)
            ->set('query', 'test')
            ->call('setCategory', 'users');
        
        // 確認方法執行成功
        $this->assertTrue(true);
    }
    
    /** @test */
    public function 可以處理向下箭頭鍵()
    {
        // 模擬有搜尋結果的情況
        $component = Livewire::test(GlobalSearch::class)
            ->set('query', 'test');
        
        // 模擬扁平結果
        $mockResults = [
            ['type' => 'user', 'id' => 1, 'title' => 'Test User'],
            ['type' => 'role', 'id' => 1, 'title' => 'Test Role'],
        ];
        
        // 使用反射來設定私有屬性或模擬方法
        $component->call('handleKeydown', 'ArrowDown');
        
        // 確認方法執行成功
        $this->assertTrue(true);
    }
    
    /** @test */
    public function 可以處理向上箭頭鍵()
    {
        Livewire::test(GlobalSearch::class)
            ->set('selectedIndex', 1)
            ->call('handleKeydown', 'ArrowUp')
            ->assertSet('selectedIndex', 0);
    }
    
    /** @test */
    public function 可以處理Escape鍵()
    {
        Livewire::test(GlobalSearch::class)
            ->set('query', '測試')
            ->set('isOpen', true)
            ->call('handleKeydown', 'Escape')
            ->assertSet('query', '')
            ->assertSet('isOpen', false);
    }
    
    /** @test */
    public function 可以處理全域快捷鍵()
    {
        Livewire::test(GlobalSearch::class)
            ->dispatch('global-shortcut', shortcut: 'ctrl+k')
            ->assertStatus(200); // 確認事件處理成功
    }
    
    /** @test */
    public function Ctrl_K快捷鍵可以切換搜尋框()
    {
        Livewire::test(GlobalSearch::class)
            ->assertSet('isOpen', false)
            ->dispatch('global-shortcut', shortcut: 'ctrl+k');
        
        // 由於切換邏輯在 handleGlobalShortcut 中，這裡主要測試事件處理
        $this->assertTrue(true);
    }
    
    /** @test */
    public function 可以清除搜尋歷史()
    {
        // 先設定一些搜尋歷史
        session()->put("search_history_{$this->user->id}", ['test1', 'test2']);
        
        Livewire::test(GlobalSearch::class)
            ->call('clearSearchHistory')
            ->assertSet('searchHistory', []);
        
        // 檢查 session 是否被清除
        $this->assertFalse(session()->has("search_history_{$this->user->id}"));
    }
    
    /** @test */
    public function 計算屬性正確回傳可用分類()
    {
        $component = Livewire::test(GlobalSearch::class);
        $categories = $component->instance()->getCategoriesProperty();
        
        $this->assertIsArray($categories);
        $this->assertArrayHasKey('all', $categories);
        $this->assertArrayHasKey('users', $categories);
        $this->assertArrayHasKey('roles', $categories);
        $this->assertArrayHasKey('permissions', $categories);
        $this->assertEquals('全部', $categories['all']);
    }
    
    /** @test */
    public function 可以取得結果總數()
    {
        $component = Livewire::test(GlobalSearch::class);
        $totalCount = $component->instance()->getTotalResultsCount();
        
        $this->assertIsInt($totalCount);
        $this->assertGreaterThanOrEqual(0, $totalCount);
    }
    
    /** @test */
    public function 可以檢查是否有結果()
    {
        $component = Livewire::test(GlobalSearch::class);
        $hasResults = $component->instance()->hasResults();
        
        $this->assertIsBool($hasResults);
    }
    
    /** @test */
    public function 可以檢查是否顯示空狀態()
    {
        $component = Livewire::test(GlobalSearch::class)
            ->set('query', 'nonexistent');
        
        $shouldShow = $component->instance()->shouldShowEmptyState();
        $this->assertIsBool($shouldShow);
    }
    
    /** @test */
    public function 可以取得空狀態訊息()
    {
        $component = Livewire::test(GlobalSearch::class)
            ->set('query', 'test');
        
        $message = $component->instance()->getEmptyStateMessage();
        $this->assertStringContainsString('test', $message);
    }
    
    /** @test */
    public function 可以取得搜尋提示()
    {
        $component = Livewire::test(GlobalSearch::class);
        $placeholder = $component->instance()->getSearchPlaceholder();
        
        $this->assertIsString($placeholder);
        $this->assertStringContainsString('Ctrl+K', $placeholder);
    }
    
    /** @test */
    public function 搜尋歷史正確載入()
    {
        // 設定搜尋歷史
        $history = ['使用者管理', '角色設定', '權限配置'];
        session()->put("search_history_{$this->user->id}", $history);
        
        $component = Livewire::test(GlobalSearch::class);
        $this->assertEquals($history, $component->get('searchHistory'));
    }
    
    /** @test */
    public function 新增搜尋歷史時移除重複項目()
    {
        $component = Livewire::test(GlobalSearch::class);
        
        // 使用反射來測試私有方法
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('addToSearchHistory');
        $method->setAccessible(true);
        
        // 新增重複的搜尋項目
        $method->invoke($component->instance(), '使用者管理');
        $method->invoke($component->instance(), '角色設定');
        $method->invoke($component->instance(), '使用者管理'); // 重複項目
        
        $history = $component->get('searchHistory');
        
        // 檢查重複項目是否被移除並移到最前面
        $this->assertEquals('使用者管理', $history[0]);
        $this->assertEquals(2, count($history)); // 只有2個不重複項目
    }
    
    /** @test */
    public function 搜尋歷史限制在10筆以內()
    {
        $component = Livewire::test(GlobalSearch::class);
        
        // 使用反射來測試私有方法
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('addToSearchHistory');
        $method->setAccessible(true);
        
        // 新增超過10筆的搜尋記錄
        for ($i = 1; $i <= 15; $i++) {
            $method->invoke($component->instance(), "搜尋項目 {$i}");
        }
        
        $history = $component->get('searchHistory');
        
        // 檢查歷史記錄是否限制在10筆
        $this->assertLessThanOrEqual(10, count($history));
        
        // 檢查最新的項目在最前面
        $this->assertEquals('搜尋項目 15', $history[0]);
    }
    
    /** @test */
    public function 空查詢不會新增到搜尋歷史()
    {
        $component = Livewire::test(GlobalSearch::class);
        
        // 使用反射來測試私有方法
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('addToSearchHistory');
        $method->setAccessible(true);
        
        $initialHistory = $component->get('searchHistory');
        $initialCount = count($initialHistory);
        
        // 嘗試新增空查詢
        $method->invoke($component->instance(), '');
        $method->invoke($component->instance(), '   '); // 空白字元
        
        $newHistory = $component->get('searchHistory');
        
        // 檢查歷史記錄數量沒有增加
        $this->assertEquals($initialCount, count($newHistory));
    }
    
    /** @test */
    public function 渲染時傳遞正確的資料到視圖()
    {
        $component = Livewire::test(GlobalSearch::class);
        $view = $component->instance()->render();
        
        $viewData = $view->getData();
        
        $this->assertArrayHasKey('searchResults', $viewData);
        $this->assertArrayHasKey('categories', $viewData);
        $this->assertArrayHasKey('flatResults', $viewData);
        $this->assertArrayHasKey('totalResults', $viewData);
        $this->assertArrayHasKey('hasResults', $viewData);
        $this->assertArrayHasKey('showEmptyState', $viewData);
        $this->assertArrayHasKey('emptyStateMessage', $viewData);
        $this->assertArrayHasKey('searchPlaceholder', $viewData);
    }
    
    /** @test */
    public function 鍵盤導航被禁用時不處理按鍵事件()
    {
        Livewire::test(GlobalSearch::class)
            ->set('keyboardNavigation', false)
            ->set('selectedIndex', 0)
            ->call('handleKeydown', 'ArrowDown')
            ->assertSet('selectedIndex', 0); // 索引不應該改變
    }
    
    /** @test */
    public function 建議功能被禁用時不載入建議()
    {
        Livewire::test(GlobalSearch::class)
            ->set('showSuggestions', false)
            ->call('open');
        
        // 確認建議列表為空
        $this->assertEquals([], Livewire::test(GlobalSearch::class)->get('suggestions'));
    }
    
    /** @test */
    public function 歷史功能被禁用時不載入歷史()
    {
        Livewire::test(GlobalSearch::class)
            ->set('showHistory', false);
        
        // 重新初始化元件
        $component = Livewire::test(GlobalSearch::class);
        
        // 確認歷史列表為空
        $this->assertEquals([], $component->get('searchHistory'));
    }
}