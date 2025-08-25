<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Notification;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Carbon\Carbon;

/**
 * 通知列表元件
 * 
 * 顯示和管理活動記錄相關的通知
 */
class NotificationList extends Component
{
    use WithPagination;

    // 篩選條件
    public string $search = '';
    public string $typeFilter = 'all';
    public string $priorityFilter = 'all';
    public string $statusFilter = 'all'; // all, read, unread
    public string $userFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';

    // 顯示設定
    public int $perPage = 20;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // 批量操作
    public array $selectedNotifications = [];
    public string $bulkAction = '';

    // 詳情顯示
    public ?Notification $selectedNotification = null;
    public bool $showDetail = false;

    protected array $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
        'priorityFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('activity_logs.view');
        
        // 設定預設日期範圍（最近 7 天）
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
    }

    public function render()
    {
        $notifications = $this->getNotificationsQuery()->paginate($this->perPage);
        
        return view('livewire.admin.activities.notification-list', [
            'notifications' => $notifications,
            'users' => $this->getUsers(),
            'statistics' => $this->getStatistics(),
            'typeOptions' => $this->getTypeOptions(),
        ]);
    }

    /**
     * 取得通知查詢
     */
    protected function getNotificationsQuery()
    {
        $query = Notification::with(['user'])
            ->where('type', 'activity_log');

        // 搜尋
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('message', 'like', "%{$this->search}%")
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('name', 'like', "%{$this->search}%")
                               ->orWhere('username', 'like', "%{$this->search}%");
                  });
            });
        }

        // 類型篩選
        if ($this->typeFilter !== 'all') {
            $query->where('data->activity_type', $this->typeFilter);
        }

        // 優先級篩選
        if ($this->priorityFilter !== 'all') {
            $query->where('priority', $this->priorityFilter);
        }

        // 狀態篩選
        if ($this->statusFilter === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($this->statusFilter === 'unread') {
            $query->whereNull('read_at');
        }

        // 使用者篩選
        if ($this->userFilter !== 'all') {
            $query->where('user_id', $this->userFilter);
        }

        // 日期範圍篩選
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // 排序
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    /**
     * 顯示通知詳情
     */
    public function showDetail(Notification $notification): void
    {
        $this->selectedNotification = $notification;
        $this->showDetail = true;

        // 如果是未讀通知，標記為已讀
        if ($notification->isUnread()) {
            $notification->markAsRead();
        }
    }

    /**
     * 關閉詳情
     */
    public function closeDetail(): void
    {
        $this->selectedNotification = null;
        $this->showDetail = false;
    }

    /**
     * 標記為已讀
     */
    public function markAsRead(Notification $notification): void
    {
        try {
            $notification->markAsRead();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => '通知已標記為已讀'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 標記為未讀
     */
    public function markAsUnread(Notification $notification): void
    {
        try {
            $notification->markAsUnread();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => '通知已標記為未讀'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 刪除通知
     */
    public function deleteNotification(Notification $notification): void
    {
        $this->authorize('activity_logs.delete');

        try {
            $notification->delete();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => '通知已刪除'
            ]);

            // 如果正在顯示詳情，關閉詳情視窗
            if ($this->selectedNotification && $this->selectedNotification->id === $notification->id) {
                $this->closeDetail();
            }

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '刪除失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 執行批量操作
     */
    public function executeBulkAction(): void
    {
        if (empty($this->selectedNotifications) || empty($this->bulkAction)) {
            return;
        }

        try {
            $notifications = Notification::whereIn('id', $this->selectedNotifications);

            switch ($this->bulkAction) {
                case 'mark_read':
                    $notifications->update(['read_at' => Carbon::now()]);
                    $message = '已標記選中的通知為已讀';
                    break;

                case 'mark_unread':
                    $notifications->update(['read_at' => null]);
                    $message = '已標記選中的通知為未讀';
                    break;

                case 'delete':
                    $this->authorize('activity_logs.delete');
                    $notifications->delete();
                    $message = '已刪除選中的通知';
                    break;

                default:
                    throw new \InvalidArgumentException('無效的批量操作');
            }

            $this->selectedNotifications = [];
            $this->bulkAction = '';

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '批量操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 標記所有為已讀
     */
    public function markAllAsRead(): void
    {
        try {
            $this->getNotificationsQuery()
                ->whereNull('read_at')
                ->update(['read_at' => Carbon::now()]);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => '所有通知已標記為已讀'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 清除篩選
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->priorityFilter = 'all';
        $this->statusFilter = 'all';
        $this->userFilter = 'all';
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->resetPage();
    }

    /**
     * 取得使用者列表
     */
    protected function getUsers()
    {
        return User::select('id', 'name', 'username')
            ->whereHas('notifications', function ($query) {
                $query->where('type', 'activity_log');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * 取得統計資訊
     */
    protected function getStatistics(): array
    {
        $baseQuery = Notification::where('type', 'activity_log');

        return [
            'total' => $baseQuery->count(),
            'unread' => $baseQuery->whereNull('read_at')->count(),
            'today' => $baseQuery->whereDate('created_at', Carbon::today())->count(),
            'this_week' => $baseQuery->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
        ];
    }

    /**
     * 取得類型選項
     */
    protected function getTypeOptions(): array
    {
        return [
            'login' => '登入',
            'logout' => '登出',
            'create' => '建立',
            'update' => '更新',
            'delete' => '刪除',
            'security' => '安全事件',
            'system' => '系統事件',
        ];
    }

    /**
     * 更新排序
     */
    public function updateSort(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * 監聽新通知事件
     */
    #[On('notification-received')]
    public function refreshNotifications(): void
    {
        // 重新載入通知列表
        $this->resetPage();
    }

    /**
     * 更新每頁顯示數量
     */
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * 更新搜尋條件
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * 更新篩選條件
     */
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }
}
