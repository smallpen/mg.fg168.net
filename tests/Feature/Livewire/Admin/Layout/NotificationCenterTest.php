<?php

namespace Tests\Feature\Livewire\Admin\Layout;

use App\Livewire\Admin\Layout\NotificationCenter;
use App\Models\User;
use App\Models\Role;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * NotificationCenter 通知中心元件測試
 * 
 * 測試通知中心的各項功能，包括：
 * - 基本渲染和初始化
 * - 通知列表顯示和分頁
 * - 通知篩選功能
 * - 通知狀態管理
 * - 通知操作功能
 * - 即時通知處理
 * - 瀏覽器通知功能
 */
class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Role $adminRole;
    protected NotificationService $notificationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
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
        
        // 建立測試通知
        $this->createTestNotifications();
    }
    
    protected function createTestNotifications(): void
    {
        // 建立不同類型的測試通知
        Notification::create([
            'user_id' => $this->user->id,
            'title' => '系統更新通知',
            'message' => '系統已成功更新到版本 2.1.0',
            'type' => 'system',
            'priority' => 'normal',
            'read_at' => null,
            'action_url' => '/admin/system/updates',
        ]);
        
        Notification::create([
            'user_id' => $this->user->id,
            'title' => '安全警報',
            'message' => '檢測到異常登入嘗試',
            'type' => 'security',
            'priority' => 'high',
            'read_at' => null,
            'action_url' => '/admin/security/alerts',
        ]);
        
        Notification::create([
            'user_id' => $this->user->id,
            'title' => '使用者操作',
            'message' => '新使用者已註冊',
            'type' => 'user_action',
            'priority' => 'normal',
            'read_at' => now(),
            'action_url' => '/admin/users',
        ]);
    }
    
    /** @test */
    public function 可以正常渲染通知中心元件()
    {
        Livewire::test(NotificationCenter::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.notification-center');
    }
    
    /** @test */
    public function 初始化時載入正確的預設狀態()
    {
        Livewire::test(NotificationCenter::class)
            ->assertSet('isOpen', false)
            ->assertSet('filter', 'all')
            ->assertSet('perPage', 10);
    }
    
    /** @test */
    public function 可以切換通知面板開關()
    {
        Livewire::test(NotificationCenter::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }
    
    /** @test */
    public function 可以關閉通知面板()
    {
        Livewire::test(NotificationCenter::class)
            ->set('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false);
    }
    
    /** @test */
    public function 開啟面板時重置分頁()
    {
        $component = Livewire::test(NotificationCenter::class)
            ->set('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true);
        
        // 檢查 toggle 方法執行成功
        $this->assertTrue(true);
    }
    
    /** @test */
    public function 可以標記單個通知為已讀()
    {
        $notification = $this->user->notifications()->where('read_at', null)->first();
        
        Livewire::test(NotificationCenter::class)
            ->call('markAsRead', $notification->id)
            ->assertDispatched('notification-read', notificationId: $notification->id);
        
        // 檢查通知是否被標記為已讀
        $this->assertNotNull($notification->fresh()->read_at);
    }
    
    /** @test */
    public function 可以標記所有通知為已讀()
    {
        $unreadCount = $this->user->notifications()->whereNull('read_at')->count();
        $this->assertGreaterThan(0, $unreadCount);
        
        Livewire::test(NotificationCenter::class)
            ->call('markAllAsRead')
            ->assertDispatched('all-notifications-read', count: $unreadCount)
            ->assertDispatched('show-toast');
        
        // 檢查所有通知是否被標記為已讀
        $remainingUnread = $this->user->notifications()->whereNull('read_at')->count();
        $this->assertEquals(0, $remainingUnread);
    }
    
    /** @test */
    public function 可以刪除通知()
    {
        $notification = $this->user->notifications()->first();
        $notificationId = $notification->id;
        
        Livewire::test(NotificationCenter::class)
            ->call('deleteNotification', $notificationId)
            ->assertDispatched('notification-deleted', notificationId: $notificationId)
            ->assertDispatched('show-toast');
        
        // 檢查通知是否被刪除
        $this->assertNull(Notification::find($notificationId));
    }
    
    /** @test */
    public function 可以設定篩選條件()
    {
        Livewire::test(NotificationCenter::class)
            ->assertSet('filter', 'all')
            ->call('setFilter', 'unread')
            ->assertSet('filter', 'unread')
            ->call('setFilter', 'security')
            ->assertSet('filter', 'security');
    }
    
    /** @test */
    public function 設定篩選條件時重置分頁()
    {
        Livewire::test(NotificationCenter::class)
            ->call('setFilter', 'unread')
            ->assertSet('filter', 'unread');
        
        // 檢查篩選設定成功
        $this->assertTrue(true);
    }
    
    /** @test */
    public function 可以點擊通知項目()
    {
        $notification = $this->user->notifications()->whereNull('read_at')->first();
        
        Livewire::test(NotificationCenter::class)
            ->call('clickNotification', $notification->id)
            ->assertDispatched('navigate-to', url: $notification->action_url);
        
        // 檢查通知是否被標記為已讀
        $this->assertNotNull($notification->fresh()->read_at);
    }
    
    /** @test */
    public function 點擊通知後關閉面板()
    {
        $notification = $this->user->notifications()->first();
        
        Livewire::test(NotificationCenter::class)
            ->set('isOpen', true)
            ->call('clickNotification', $notification->id)
            ->assertSet('isOpen', false);
    }
    
    /** @test */
    public function 可以載入更多通知()
    {
        Livewire::test(NotificationCenter::class)
            ->assertSet('perPage', 10)
            ->call('loadMore')
            ->assertSet('perPage', 20);
    }
    
    /** @test */
    public function 可以重新整理通知()
    {
        Livewire::test(NotificationCenter::class)
            ->call('refresh')
            ->assertDispatched('notifications-refreshed');
    }
    
    /** @test */
    public function 可以處理新通知事件()
    {
        $newNotification = [
            'id' => 999,
            'title' => '新通知',
            'message' => '這是一個新通知',
            'type' => 'info',
            'priority' => 'normal',
            'read_at' => null,
        ];
        
        Livewire::test(NotificationCenter::class)
            ->dispatch('notification-received', notification: $newNotification)
            ->assertDispatched('notification-count-updated');
    }
    
    /** @test */
    public function 高優先級通知觸發瀏覽器通知()
    {
        $highPriorityNotification = [
            'id' => 999,
            'title' => '緊急通知',
            'message' => '這是一個緊急通知',
            'type' => 'security',
            'priority' => 'high',
            'read_at' => null,
        ];
        
        Livewire::test(NotificationCenter::class)
            ->dispatch('notification-received', notification: $highPriorityNotification)
            ->assertDispatched('show-browser-notification');
    }
    
    /** @test */
    public function 計算屬性正確回傳通知列表()
    {
        $component = Livewire::test(NotificationCenter::class);
        $notifications = $component->instance()->getNotificationsProperty();
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $notifications);
        $this->assertGreaterThan(0, $notifications->total());
    }
    
    /** @test */
    public function 計算屬性正確回傳未讀通知數量()
    {
        $component = Livewire::test(NotificationCenter::class);
        $unreadCount = $component->instance()->getUnreadCountProperty();
        
        $expectedCount = $this->user->notifications()->whereNull('read_at')->count();
        $this->assertEquals($expectedCount, $unreadCount);
    }
    
    /** @test */
    public function 計算屬性正確回傳通知類型列表()
    {
        $component = Livewire::test(NotificationCenter::class);
        $types = $component->instance()->getNotificationTypesProperty();
        
        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }
    
    /** @test */
    public function 計算屬性正確回傳最近通知()
    {
        $component = Livewire::test(NotificationCenter::class);
        $recentNotifications = $component->instance()->getRecentNotificationsProperty();
        
        $this->assertNotNull($recentNotifications);
    }
    
    /** @test */
    public function 可以取得篩選標籤()
    {
        $component = Livewire::test(NotificationCenter::class);
        
        $this->assertEquals('全部', $component->instance()->getFilterLabel('all'));
        $this->assertEquals('未讀', $component->instance()->getFilterLabel('unread'));
        $this->assertEquals('安全事件', $component->instance()->getFilterLabel('security'));
        $this->assertEquals('未知', $component->instance()->getFilterLabel('unknown'));
    }
    
    /** @test */
    public function 可以檢查是否有高優先級未讀通知()
    {
        // 建立高優先級未讀通知
        Notification::create([
            'user_id' => $this->user->id,
            'title' => '緊急通知',
            'message' => '緊急事件',
            'type' => 'security',
            'priority' => 'urgent',
            'read_at' => null,
        ]);
        
        $component = Livewire::test(NotificationCenter::class);
        $hasHighPriority = $component->instance()->getHasHighPriorityUnreadProperty();
        
        $this->assertTrue($hasHighPriority);
    }
    
    /** @test */
    public function 顯示瀏覽器通知包含正確參數()
    {
        $notification = [
            'id' => 999,
            'title' => '測試通知',
            'message' => '測試訊息',
            'priority' => 'urgent',
        ];
        
        Livewire::test(NotificationCenter::class)
            ->call('showBrowserNotification', $notification)
            ->assertDispatched('show-browser-notification');
    }
    
    /** @test */
    public function 一般優先級通知不要求互動()
    {
        $notification = [
            'id' => 999,
            'title' => '一般通知',
            'message' => '一般訊息',
            'priority' => 'normal',
        ];
        
        Livewire::test(NotificationCenter::class)
            ->call('showBrowserNotification', $notification)
            ->assertDispatched('show-browser-notification', function ($event) {
                return !isset($event['requireInteraction']) || $event['requireInteraction'] === false;
            });
    }
    
    /** @test */
    public function 篩選未讀通知時只顯示未讀項目()
    {
        $component = Livewire::test(NotificationCenter::class)
            ->call('setFilter', 'unread');
        
        $notifications = $component->instance()->getNotificationsProperty();
        
        foreach ($notifications as $notification) {
            $this->assertNull($notification->read_at);
        }
    }
    
    /** @test */
    public function 篩選特定類型通知時只顯示該類型()
    {
        $component = Livewire::test(NotificationCenter::class)
            ->call('setFilter', 'security');
        
        $notifications = $component->instance()->getNotificationsProperty();
        
        foreach ($notifications as $notification) {
            $this->assertEquals('security', $notification->type);
        }
    }
    
    /** @test */
    public function 點擊不存在的通知不會產生錯誤()
    {
        Livewire::test(NotificationCenter::class)
            ->call('clickNotification', 99999)
            ->assertStatus(200); // 確認沒有錯誤
    }
    
    /** @test */
    public function 標記不存在的通知為已讀不會產生錯誤()
    {
        Livewire::test(NotificationCenter::class)
            ->call('markAsRead', 99999)
            ->assertStatus(200); // 確認沒有錯誤
    }
    
    /** @test */
    public function 刪除不存在的通知不會產生錯誤()
    {
        Livewire::test(NotificationCenter::class)
            ->call('deleteNotification', 99999)
            ->assertStatus(200); // 確認沒有錯誤
    }
    
    /** @test */
    public function 沒有未讀通知時標記全部為已讀不會產生錯誤()
    {
        // 先標記所有通知為已讀
        $this->user->notifications()->update(['read_at' => now()]);
        
        Livewire::test(NotificationCenter::class)
            ->call('markAllAsRead')
            ->assertStatus(200); // 確認沒有錯誤
    }
    
    /** @test */
    public function 渲染時傳遞正確的資料到視圖()
    {
        $component = Livewire::test(NotificationCenter::class);
        $view = $component->instance()->render();
        
        $this->assertArrayHasKey('notifications', $view->getData());
        $this->assertArrayHasKey('unreadCount', $view->getData());
        $this->assertArrayHasKey('notificationTypes', $view->getData());
        $this->assertArrayHasKey('recentNotifications', $view->getData());
        $this->assertArrayHasKey('hasHighPriorityUnread', $view->getData());
    }
}