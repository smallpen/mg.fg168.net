<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationCenter extends Component
{
    use WithPagination;

    /**
     * 通知狀態
     */
    public bool $isOpen = false;
    public string $filter = 'all'; // all, unread, security, system, user_action, report
    public int $perPage = 10;

    /**
     * 服務依賴
     */
    protected NotificationService $notificationService;

    /**
     * 初始化
     */
    public function boot(NotificationService $notificationService): void
    {
        $this->notificationService = $notificationService;
    }

    /**
     * 計算屬性：獲取通知列表
     */
    public function getNotificationsProperty(): LengthAwarePaginator
    {
        $filters = [
            'per_page' => $this->perPage,
        ];

        // 套用篩選條件
        if ($this->filter !== 'all') {
            if (in_array($this->filter, ['unread', 'read'])) {
                $filters['status'] = $this->filter;
            } else {
                $filters['type'] = $this->filter;
            }
        }

        return $this->notificationService->getUserNotifications(auth()->user(), $filters);
    }

    /**
     * 計算屬性：獲取未讀通知數量
     */
    public function getUnreadCountProperty(): int
    {
        return $this->notificationService->getUnreadCount(auth()->user());
    }

    /**
     * 計算屬性：獲取通知類型列表
     */
    public function getNotificationTypesProperty(): array
    {
        return $this->notificationService->getNotificationTypes();
    }

    /**
     * 計算屬性：獲取最近通知（用於下拉面板）
     */
    public function getRecentNotificationsProperty()
    {
        return $this->notificationService->getRecentNotifications(auth()->user(), 5);
    }

    /**
     * 切換通知面板開關
     */
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
        
        // 如果打開面板，重置分頁
        if ($this->isOpen) {
            $this->resetPage();
        }
    }

    /**
     * 關閉通知面板
     */
    public function close(): void
    {
        $this->isOpen = false;
    }

    /**
     * 標記單個通知為已讀
     */
    public function markAsRead(int $notificationId): void
    {
        $success = $this->notificationService->markAsRead($notificationId, auth()->user());
        
        if ($success) {
            $this->dispatch('notification-read', notificationId: $notificationId);
            
            // 如果當前篩選是未讀，重新載入
            if ($this->filter === 'unread') {
                $this->resetPage();
            }
        }
    }

    /**
     * 標記所有通知為已讀
     */
    public function markAllAsRead(): void
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());
        
        if ($count > 0) {
            $this->dispatch('all-notifications-read', count: $count);
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已標記 {$count} 筆通知為已讀"
            ]);
            
            // 重新載入通知列表
            $this->resetPage();
        }
    }

    /**
     * 刪除通知
     */
    public function deleteNotification(int $notificationId): void
    {
        $success = $this->notificationService->deleteNotification($notificationId, auth()->user());
        
        if ($success) {
            $this->dispatch('notification-deleted', notificationId: $notificationId);
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '通知已刪除'
            ]);
            
            // 重新載入通知列表
            $this->resetPage();
        }
    }

    /**
     * 設定篩選條件
     */
    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    /**
     * 點擊通知項目
     */
    public function clickNotification(int $notificationId): void
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        
        if (!$notification) {
            return;
        }

        // 標記為已讀
        if ($notification->isUnread()) {
            $this->markAsRead($notificationId);
        }

        // 如果有動作 URL，導航到該頁面
        if ($notification->action_url) {
            $this->dispatch('navigate-to', url: $notification->action_url);
        }

        // 關閉通知面板
        $this->close();
    }

    /**
     * 載入更多通知
     */
    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    /**
     * 重新整理通知
     */
    public function refresh(): void
    {
        $this->resetPage();
        $this->dispatch('notifications-refreshed');
    }

    /**
     * 監聽新通知事件
     */
    #[On('notification-received')]
    public function handleNewNotification(array $notification): void
    {
        // 重新載入通知列表
        $this->resetPage();
        
        // 如果是高優先級通知，顯示瀏覽器通知
        if (in_array($notification['priority'] ?? 'normal', ['high', 'urgent'])) {
            $this->showBrowserNotification($notification);
        }
        
        // 發送前端通知更新事件
        $this->dispatch('notification-count-updated', count: $this->unreadCount);
    }

    /**
     * 顯示瀏覽器通知
     */
    public function showBrowserNotification(array $notification): void
    {
        $this->dispatch('show-browser-notification', [
            'title' => $notification['title'] ?? '新通知',
            'body' => $notification['message'] ?? '',
            'icon' => '/images/notification-icon.png',
            'tag' => 'admin-notification-' . ($notification['id'] ?? time()),
            'requireInteraction' => in_array($notification['priority'] ?? 'normal', ['urgent']),
        ]);
    }

    /**
     * 獲取篩選標籤
     */
    public function getFilterLabel(string $filter): string
    {
        $labels = [
            'all' => '全部',
            'unread' => '未讀',
            'read' => '已讀',
            'security' => '安全事件',
            'system' => '系統通知',
            'user_action' => '使用者操作',
            'report' => '統計報告',
        ];

        return $labels[$filter] ?? '未知';
    }

    /**
     * 檢查是否有未讀的高優先級通知
     */
    public function getHasHighPriorityUnreadProperty(): bool
    {
        return $this->notificationService->hasUnreadHighPriorityNotifications(auth()->user());
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.notification-center', [
            'notifications' => $this->notifications,
            'unreadCount' => $this->unreadCount,
            'notificationTypes' => $this->notificationTypes,
            'recentNotifications' => $this->recentNotifications,
            'hasHighPriorityUnread' => $this->hasHighPriorityUnread,
        ]);
    }
}
