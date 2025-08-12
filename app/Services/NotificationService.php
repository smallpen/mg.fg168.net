<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class NotificationService
{
    /**
     * 獲取使用者通知
     */
    public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->notifications()->latest();

        // 套用篩選條件
        if (isset($filters['type']) && $filters['type'] !== 'all') {
            $query->ofType($filters['type']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'unread') {
                $query->unread();
            } elseif ($filters['status'] === 'read') {
                $query->read();
            }
        }

        if (isset($filters['priority'])) {
            $query->ofPriority($filters['priority']);
        }

        if (isset($filters['days'])) {
            $query->recent((int) $filters['days']);
        }

        $perPage = $filters['per_page'] ?? 10;
        
        return $query->paginate($perPage);
    }

    /**
     * 建立通知
     */
    public function createNotification(array $data): Notification
    {
        // 設定預設值
        $data = array_merge([
            'priority' => Notification::PRIORITY_NORMAL,
            'is_browser_notification' => false,
        ], $data);

        // 根據類型設定預設圖示和顏色
        if (!isset($data['icon']) || !isset($data['color'])) {
            $typeConfig = Notification::getTypeConfig();
            $config = $typeConfig[$data['type']] ?? $typeConfig[Notification::TYPE_SYSTEM];
            
            $data['icon'] = $data['icon'] ?? $config['icon'];
            $data['color'] = $data['color'] ?? $config['color'];
        }

        $notification = Notification::create($data);

        // 如果是高優先級通知，發送瀏覽器通知
        if (in_array($data['priority'], [Notification::PRIORITY_HIGH, Notification::PRIORITY_URGENT])) {
            $this->sendBrowserNotification($notification->user, [
                'title' => $notification->title,
                'message' => $notification->message,
                'icon' => $notification->icon,
                'url' => $notification->action_url,
            ]);
        }

        return $notification;
    }

    /**
     * 批量建立通知
     */
    public function createBulkNotifications(array $users, array $notificationData): Collection
    {
        $notifications = collect();

        foreach ($users as $user) {
            $data = array_merge($notificationData, ['user_id' => $user->id]);
            $notifications->push($this->createNotification($data));
        }

        return $notifications;
    }

    /**
     * 標記通知為已讀
     */
    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * 標記所有通知為已讀
     */
    public function markAllAsRead(User $user): int
    {
        return $user->notifications()
            ->unread()
            ->update(['read_at' => Carbon::now()]);
    }

    /**
     * 刪除通知
     */
    public function deleteNotification(int $notificationId, User $user): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * 獲取未讀通知數量
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    /**
     * 獲取最近通知
     */
    public function getRecentNotifications(User $user, int $limit = 10): Collection
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * 獲取通知統計
     */
    public function getNotificationStats(User $user): array
    {
        $notifications = $user->notifications();

        return [
            'total' => $notifications->count(),
            'unread' => $notifications->unread()->count(),
            'today' => $notifications->whereDate('created_at', Carbon::today())->count(),
            'this_week' => $notifications->where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'by_type' => $notifications->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_priority' => $notifications->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
        ];
    }

    /**
     * 發送瀏覽器通知
     */
    public function sendBrowserNotification(User $user, array $data): void
    {
        // 這裡可以整合 WebSocket 或 Server-Sent Events
        // 目前先記錄到日誌，實際實作時可以使用 Pusher、Laravel Echo 等
        \Log::info('Browser notification sent', [
            'user_id' => $user->id,
            'data' => $data,
        ]);

        // 標記通知需要顯示瀏覽器通知
        if (isset($data['notification_id'])) {
            Notification::find($data['notification_id'])?->update([
                'is_browser_notification' => true
            ]);
        }
    }

    /**
     * 清理舊通知
     */
    public function cleanupOldNotifications(int $daysToKeep = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return Notification::where('created_at', '<', $cutoffDate)
            ->where('read_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * 建立系統通知
     */
    public function createSystemNotification(User $user, string $title, string $message, array $options = []): Notification
    {
        return $this->createNotification(array_merge([
            'user_id' => $user->id,
            'type' => Notification::TYPE_SYSTEM,
            'title' => $title,
            'message' => $message,
        ], $options));
    }

    /**
     * 建立安全通知
     */
    public function createSecurityNotification(User $user, string $title, string $message, array $options = []): Notification
    {
        return $this->createNotification(array_merge([
            'user_id' => $user->id,
            'type' => Notification::TYPE_SECURITY,
            'title' => $title,
            'message' => $message,
            'priority' => Notification::PRIORITY_HIGH,
        ], $options));
    }

    /**
     * 建立使用者操作通知
     */
    public function createUserActionNotification(User $user, string $title, string $message, array $options = []): Notification
    {
        return $this->createNotification(array_merge([
            'user_id' => $user->id,
            'type' => Notification::TYPE_USER_ACTION,
            'title' => $title,
            'message' => $message,
        ], $options));
    }

    /**
     * 建立報告通知
     */
    public function createReportNotification(User $user, string $title, string $message, array $options = []): Notification
    {
        return $this->createNotification(array_merge([
            'user_id' => $user->id,
            'type' => Notification::TYPE_REPORT,
            'title' => $title,
            'message' => $message,
        ], $options));
    }

    /**
     * 獲取通知類型列表
     */
    public function getNotificationTypes(): array
    {
        return Notification::getTypeConfig();
    }

    /**
     * 檢查使用者是否有未讀的高優先級通知
     */
    public function hasUnreadHighPriorityNotifications(User $user): bool
    {
        return $user->notifications()
            ->unread()
            ->highPriority()
            ->exists();
    }
}