<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use Livewire\Livewire;
use App\Livewire\Admin\Layout\NotificationCenter;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->notificationService = app(NotificationService::class);
    }

    /** @test */
    public function it_can_create_notification()
    {
        $notificationData = [
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_SYSTEM,
            'title' => '測試通知',
            'message' => '這是一個測試通知',
            'priority' => Notification::PRIORITY_NORMAL,
        ];

        $notification = $this->notificationService->createNotification($notificationData);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals('測試通知', $notification->title);
        $this->assertTrue($notification->isUnread());
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $this->assertTrue($notification->isUnread());

        $result = $this->notificationService->markAsRead($notification->id, $this->user);

        $this->assertTrue($result);
        $notification->refresh();
        $this->assertTrue($notification->isRead());
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        // 建立多個未讀通知
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $unreadCount = $this->notificationService->getUnreadCount($this->user);
        $this->assertEquals(3, $unreadCount);

        $markedCount = $this->notificationService->markAllAsRead($this->user);

        $this->assertEquals(3, $markedCount);
        $this->assertEquals(0, $this->notificationService->getUnreadCount($this->user));
    }

    /** @test */
    public function it_can_delete_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $result = $this->notificationService->deleteNotification($notification->id, $this->user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function it_can_get_user_notifications_with_filters()
    {
        // 建立不同類型的通知
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_SECURITY,
            'read_at' => null,
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_SYSTEM,
            'read_at' => now(),
        ]);

        // 測試未讀篩選
        $unreadNotifications = $this->notificationService->getUserNotifications($this->user, [
            'status' => 'unread'
        ]);
        $this->assertEquals(1, $unreadNotifications->count());

        // 測試類型篩選
        $securityNotifications = $this->notificationService->getUserNotifications($this->user, [
            'type' => Notification::TYPE_SECURITY
        ]);
        $this->assertEquals(1, $securityNotifications->count());
    }

    /** @test */
    public function notification_center_component_renders_correctly()
    {
        $this->actingAs($this->user);

        Livewire::test(NotificationCenter::class)
            ->assertSee('通知中心')
            ->assertSet('isOpen', false)
            ->assertSet('filter', 'all');
    }

    /** @test */
    public function notification_center_can_toggle_open_state()
    {
        $this->actingAs($this->user);

        Livewire::test(NotificationCenter::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }

    /** @test */
    public function notification_center_displays_unread_count()
    {
        $this->actingAs($this->user);

        // 建立未讀通知
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Livewire::test(NotificationCenter::class)
            ->assertSee('5'); // 應該顯示未讀數量
    }

    /** @test */
    public function notification_center_can_mark_all_as_read()
    {
        $this->actingAs($this->user);

        // 建立未讀通知
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Livewire::test(NotificationCenter::class)
            ->call('markAllAsRead')
            ->assertDispatched('all-notifications-read');

        $this->assertEquals(0, $this->notificationService->getUnreadCount($this->user));
    }

    /** @test */
    public function notification_center_can_filter_notifications()
    {
        $this->actingAs($this->user);

        // 建立不同類型的通知
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_SECURITY,
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_SYSTEM,
        ]);

        Livewire::test(NotificationCenter::class)
            ->call('setFilter', 'security')
            ->assertSet('filter', 'security');
    }

    /** @test */
    public function notification_center_can_delete_notification()
    {
        $this->actingAs($this->user);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(NotificationCenter::class)
            ->call('deleteNotification', $notification->id)
            ->assertDispatched('notification-deleted');

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function notification_service_can_create_different_types_of_notifications()
    {
        // 測試系統通知
        $systemNotification = $this->notificationService->createSystemNotification(
            $this->user,
            '系統更新',
            '系統已更新到新版本'
        );
        $this->assertEquals(Notification::TYPE_SYSTEM, $systemNotification->type);

        // 測試安全通知
        $securityNotification = $this->notificationService->createSecurityNotification(
            $this->user,
            '安全警報',
            '檢測到異常登入'
        );
        $this->assertEquals(Notification::TYPE_SECURITY, $securityNotification->type);
        $this->assertEquals(Notification::PRIORITY_HIGH, $securityNotification->priority);

        // 測試使用者操作通知
        $userActionNotification = $this->notificationService->createUserActionNotification(
            $this->user,
            '使用者操作',
            '新使用者已註冊'
        );
        $this->assertEquals(Notification::TYPE_USER_ACTION, $userActionNotification->type);

        // 測試報告通知
        $reportNotification = $this->notificationService->createReportNotification(
            $this->user,
            '統計報告',
            '月度報告已生成'
        );
        $this->assertEquals(Notification::TYPE_REPORT, $reportNotification->type);
    }

    /** @test */
    public function notification_model_has_correct_relationships_and_scopes()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        // 測試關聯
        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($this->user->id, $notification->user->id);

        // 測試查詢範圍
        $this->assertEquals(1, Notification::unread()->count());
        $this->assertEquals(0, Notification::read()->count());

        // 標記為已讀後測試
        $notification->markAsRead();
        $this->assertEquals(0, Notification::unread()->count());
        $this->assertEquals(1, Notification::read()->count());
    }
}
