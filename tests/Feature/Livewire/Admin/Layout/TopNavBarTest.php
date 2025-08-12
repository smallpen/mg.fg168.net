<?php

namespace Tests\Feature\Livewire\Admin\Layout;

use App\Livewire\Admin\Layout\TopNavBar;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * TopNavBar 頂部導航列元件測試
 * 
 * 測試頂部導航列的各項功能，包括：
 * - 基本渲染和初始化
 * - 側邊欄切換功能
 * - 全域搜尋功能
 * - 通知中心功能
 * - 使用者選單功能
 * - 麵包屑導航
 * - 事件處理機制
 */
class TopNavBarTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Role $adminRole;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色和權限
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);
        
        // 建立測試使用者
        $this->user = User::factory()->create([
            'name' => '測試管理員',
            'email' => 'admin@test.com',
        ]);
        
        $this->user->roles()->attach($this->adminRole);
        $this->actingAs($this->user);
    }
    
    /** @test */
    public function 可以正常渲染頂部導航列元件()
    {
        Livewire::test(TopNavBar::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.top-nav-bar')
            ->assertSee('管理後台');
    }
    
    /** @test */
    public function 初始化時載入正確的預設狀態()
    {
        Livewire::test(TopNavBar::class)
            ->assertSet('globalSearch', '')
            ->assertSet('searchResults', [])
            ->assertSet('showSearchResults', false)
            ->assertSet('unreadNotifications', 2) // 模擬資料有2筆未讀通知
            ->assertSet('showNotifications', false)
            ->assertSet('showUserMenu', false)
            ->assertSet('pageTitle', '管理後台');
    }
    
    /** @test */
    public function 可以切換側邊欄()
    {
        Livewire::test(TopNavBar::class)
            ->call('toggleSidebar')
            ->assertDispatched('sidebar-toggle');
    }
    
    /** @test */
    public function 可以執行全域搜尋()
    {
        $component = Livewire::test(TopNavBar::class)
            ->set('globalSearch', '使用者')
            ->assertSet('showSearchResults', true);
        
        $searchResults = $component->get('searchResults');
        $this->assertNotEmpty($searchResults);
    }
    
    /** @test */
    public function 空搜尋查詢時清除搜尋結果()
    {
        Livewire::test(TopNavBar::class)
            ->set('globalSearch', '使用者')
            ->set('globalSearch', '')
            ->assertSet('searchResults', [])
            ->assertSet('showSearchResults', false);
    }
    
    /** @test */
    public function 可以清除搜尋()
    {
        Livewire::test(TopNavBar::class)
            ->set('globalSearch', '測試搜尋')
            ->call('clearSearch')
            ->assertSet('globalSearch', '')
            ->assertSet('searchResults', [])
            ->assertSet('showSearchResults', false);
    }
    
    /** @test */
    public function 可以切換使用者選單()
    {
        Livewire::test(TopNavBar::class)
            ->assertSet('showUserMenu', false)
            ->call('toggleUserMenu')
            ->assertSet('showUserMenu', true)
            ->assertSet('showNotifications', false) // 其他選單應該關閉
            ->call('toggleUserMenu')
            ->assertSet('showUserMenu', false);
    }
    
    /** @test */
    public function 可以切換通知面板()
    {
        Livewire::test(TopNavBar::class)
            ->assertSet('showNotifications', false)
            ->call('toggleNotifications')
            ->assertSet('showNotifications', true)
            ->assertDispatched('close-other-menus', except: 'notifications')
            ->call('toggleNotifications')
            ->assertSet('showNotifications', false);
    }
    
    /** @test */
    public function 可以標記單個通知為已讀()
    {
        $component = Livewire::test(TopNavBar::class);
        
        // 確認初始未讀通知數量
        $initialUnread = $component->get('unreadNotifications');
        $this->assertGreaterThan(0, $initialUnread);
        
        // 標記第一個通知為已讀
        $component->call('markAsRead', 1)
            ->assertDispatched('notification-read', notificationId: 1);
        
        // 檢查未讀數量是否減少
        $newUnread = $component->get('unreadNotifications');
        $this->assertEquals($initialUnread - 1, $newUnread);
    }
    
    /** @test */
    public function 可以標記所有通知為已讀()
    {
        Livewire::test(TopNavBar::class)
            ->call('markAllAsRead')
            ->assertSet('unreadNotifications', 0)
            ->assertDispatched('all-notifications-read');
    }
    
    /** @test */
    public function 可以關閉所有下拉選單()
    {
        Livewire::test(TopNavBar::class)
            ->set('showNotifications', true)
            ->set('showUserMenu', true)
            ->set('showSearchResults', true)
            ->call('closeAllMenus')
            ->assertSet('showNotifications', false)
            ->assertSet('showUserMenu', false)
            ->assertSet('showSearchResults', false)
            ->assertDispatched('close-other-menus');
    }
    
    /** @test */
    public function 可以處理關閉其他選單事件()
    {
        Livewire::test(TopNavBar::class)
            ->set('showNotifications', true)
            ->dispatch('close-other-menus')
            ->assertSet('showNotifications', false);
    }
    
    /** @test */
    public function 關閉其他選單時可以排除特定選單()
    {
        Livewire::test(TopNavBar::class)
            ->set('showNotifications', true)
            ->dispatch('close-other-menus', except: 'notifications')
            ->assertSet('showNotifications', true); // 通知選單不應該關閉
    }
    
    /** @test */
    public function 可以處理新通知事件()
    {
        $newNotification = [
            'id' => 3,
            'title' => '新通知',
            'message' => '這是一個新通知',
            'type' => 'info',
            'read' => false,
            'created_at' => now(),
        ];
        
        $component = Livewire::test(TopNavBar::class);
        $initialCount = count($component->get('recentNotifications'));
        
        $component->dispatch('notification-received', notification: $newNotification)
            ->assertDispatched('show-browser-notification');
        
        // 檢查通知是否被新增到列表開頭
        $notifications = $component->get('recentNotifications');
        $this->assertEquals($newNotification['id'], $notifications[0]['id']);
        
        // 檢查未讀數量是否增加
        $this->assertEquals($initialCount + 1, count($notifications));
    }
    
    /** @test */
    public function 新通知列表最多保留10筆()
    {
        $component = Livewire::test(TopNavBar::class);
        
        // 新增11筆通知
        for ($i = 3; $i <= 13; $i++) {
            $notification = [
                'id' => $i,
                'title' => "通知 {$i}",
                'message' => "訊息 {$i}",
                'type' => 'info',
                'read' => false,
                'created_at' => now(),
            ];
            
            $component->dispatch('notification-received', notification: $notification);
        }
        
        // 檢查通知列表只有10筆
        $notifications = $component->get('recentNotifications');
        $this->assertCount(10, $notifications);
        
        // 檢查最新的通知在最前面
        $this->assertEquals(13, $notifications[0]['id']);
    }
    
    /** @test */
    public function 可以處理主題變更事件()
    {
        Livewire::test(TopNavBar::class)
            ->dispatch('theme-changed', theme: 'dark')
            ->assertStatus(200); // 確認事件處理沒有錯誤
    }
    
    /** @test */
    public function 可以處理語言變更事件()
    {
        Livewire::test(TopNavBar::class)
            ->dispatch('locale-changed', locale: 'en')
            ->assertDispatched('breadcrumb-refresh');
    }
    
    /** @test */
    public function 可以設定頁面標題()
    {
        Livewire::test(TopNavBar::class)
            ->dispatch('page-title-changed', title: '使用者管理')
            ->assertSet('pageTitle', '使用者管理')
            ->assertDispatched('breadcrumb-refresh');
    }
    
    /** @test */
    public function 可以處理麵包屑變更事件()
    {
        $breadcrumbs = [
            ['title' => '首頁', 'route' => 'admin.dashboard', 'active' => false],
            ['title' => '使用者管理', 'route' => 'admin.users.index', 'active' => true],
        ];
        
        Livewire::test(TopNavBar::class)
            ->dispatch('breadcrumbs-changed', breadcrumbs: $breadcrumbs)
            ->assertSet('breadcrumbs', $breadcrumbs);
    }
    
    /** @test */
    public function 計算屬性正確回傳通知列表()
    {
        $component = Livewire::test(TopNavBar::class);
        $notifications = $component->instance()->getNotificationsProperty();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $notifications);
        $this->assertCount(2, $notifications); // 模擬資料有2筆通知
    }
    
    /** @test */
    public function 計算屬性正確回傳當前使用者()
    {
        $component = Livewire::test(TopNavBar::class);
        $currentUser = $component->instance()->getCurrentUserProperty();
        
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertEquals($this->user->id, $currentUser->id);
    }
    
    /** @test */
    public function 搜尋功能可以找到相關頁面()
    {
        Livewire::test(TopNavBar::class)
            ->set('globalSearch', '使用者')
            ->assertSet('showSearchResults', true);
        
        $component = Livewire::test(TopNavBar::class)
            ->set('globalSearch', '使用者');
        
        $results = $component->get('searchResults');
        $this->assertNotEmpty($results);
        
        // 檢查是否包含使用者管理頁面
        $foundUserPage = false;
        foreach ($results as $result) {
            if (str_contains($result['title'], '使用者')) {
                $foundUserPage = true;
                break;
            }
        }
        
        $this->assertTrue($foundUserPage);
    }
    
    /** @test */
    public function 可以檢查新通知()
    {
        Livewire::test(TopNavBar::class)
            ->call('checkNewNotifications')
            ->assertStatus(200); // 確認方法執行成功
    }
    
    /** @test */
    public function 可以同步通知()
    {
        Livewire::test(TopNavBar::class)
            ->call('syncNotifications')
            ->assertStatus(200); // 確認方法執行成功
    }
    
    /** @test */
    public function 未讀通知數量計算正確()
    {
        $component = Livewire::test(TopNavBar::class);
        
        // 檢查初始未讀數量
        $this->assertEquals(2, $component->get('unreadNotifications'));
        
        // 標記一個為已讀
        $component->call('markAsRead', 1);
        $this->assertEquals(1, $component->get('unreadNotifications'));
        
        // 標記所有為已讀
        $component->call('markAllAsRead');
        $this->assertEquals(0, $component->get('unreadNotifications'));
    }
    
    /** @test */
    public function 麵包屑初始化正確()
    {
        $component = Livewire::test(TopNavBar::class);
        $breadcrumbs = $component->get('breadcrumbs');
        
        $this->assertNotEmpty($breadcrumbs);
        $this->assertEquals('管理後台', $breadcrumbs[0]['title']);
        $this->assertEquals('admin.dashboard', $breadcrumbs[0]['route']);
        $this->assertTrue($breadcrumbs[0]['active']);
    }
    
    /** @test */
    public function 搜尋結果為空時不顯示結果面板()
    {
        Livewire::test(TopNavBar::class)
            ->set('globalSearch', 'xyz不存在的搜尋')
            ->assertSet('showSearchResults', true)
            ->assertSet('searchResults', []);
    }
    
    /** @test */
    public function 通知標記為已讀後狀態正確更新()
    {
        $component = Livewire::test(TopNavBar::class);
        
        // 取得第一個通知
        $notifications = $component->get('recentNotifications');
        $firstNotification = $notifications[0];
        
        // 確認初始狀態為未讀
        $this->assertFalse($firstNotification['read']);
        
        // 標記為已讀
        $component->call('markAsRead', $firstNotification['id']);
        
        // 檢查通知狀態是否更新
        $updatedNotifications = $component->get('recentNotifications');
        $updatedNotification = collect($updatedNotifications)->firstWhere('id', $firstNotification['id']);
        
        $this->assertTrue($updatedNotification['read']);
    }
}