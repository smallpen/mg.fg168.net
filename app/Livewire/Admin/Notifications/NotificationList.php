<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\Notification;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    // 搜尋和篩選屬性
    public string $search = '';
    public string $typeFilter = 'all';
    public string $priorityFilter = 'all';
    public string $statusFilter = 'all';
    public string $userFilter = 'all';

    // 分頁設定
    public int $perPage = 25;
    public array $perPageOptions = [10, 25, 50, 100];

    // 選擇項目
    public array $selectedItems = [];
    public bool $selectAll = false;

    // URL 查詢字串屬性
    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
        'priorityFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'userFilter' => ['except' => 'all'],
        'perPage' => ['except' => 25],
    ];

    /**
     * 元件掛載
     */
    public function mount(): void
    {
        $this->authorize('notifications.view');
    }

    /**
     * 重置篩選條件
     */
    public function resetFilters(): void
    {
        try {
            $this->search = '';
            $this->typeFilter = 'all';
            $this->priorityFilter = 'all';
            $this->statusFilter = 'all';
            $this->userFilter = 'all';
            $this->selectedItems = [];
            $this->selectAll = false;
            
            $this->resetPage();
            $this->resetValidation();
            
            // 發送前端重置事件
            $this->dispatch('reset-form-elements');
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '篩選條件已清除'
            ]);
            
        } catch (\Exception $e) {
            logger()->error('重置篩選條件失敗', [
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }
    }

    /**
     * 每頁顯示筆數更新
     */
    public function updatedPerPage(): void
    {
        if (!in_array($this->perPage, $this->perPageOptions)) {
            $this->perPage = 25;
        }
        
        $this->resetPage();
    }

    /**
     * 建立新通知
     */
    public function createNotification(): void
    {
        $this->authorize('notifications.create');
        
        $this->redirect(route('admin.notifications.create'));
    }

    /**
     * 標記為已讀
     */
    public function markAsRead(int $notificationId): void
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->markAsRead();
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '通知已標記為已讀'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗，請重試'
            ]);
        }
    }

    /**
     * 刪除通知
     */
    public function deleteNotification(int $notificationId): void
    {
        $this->authorize('notifications.delete');
        
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->delete();
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '通知已刪除'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '刪除失敗，請重試'
            ]);
        }
    }

    /**
     * 批次操作
     */
    public function bulkAction(string $action): void
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => '請選擇要操作的項目'
            ]);
            return;
        }

        try {
            $count = count($this->selectedItems);
            
            switch ($action) {
                case 'mark_read':
                    Notification::whereIn('id', $this->selectedItems)
                        ->whereNull('read_at')
                        ->update(['read_at' => now()]);
                    
                    $this->dispatch('show-toast', [
                        'type' => 'success',
                        'message' => "已標記 {$count} 個通知為已讀"
                    ]);
                    break;
                    
                case 'delete':
                    $this->authorize('notifications.delete');
                    
                    Notification::whereIn('id', $this->selectedItems)->delete();
                    
                    $this->dispatch('show-toast', [
                        'type' => 'success',
                        'message' => "已刪除 {$count} 個通知"
                    ]);
                    break;
            }
            
            $this->selectedItems = [];
            $this->selectAll = false;
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '批次操作失敗，請重試'
            ]);
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        $query = Notification::with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('message', 'like', '%' . $this->search . '%')
                      ->orWhere('type', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->priorityFilter !== 'all', function ($query) {
                $query->where('priority', $this->priorityFilter);
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'read') {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            })
            ->when($this->userFilter !== 'all', function ($query) {
                $query->where('user_id', $this->userFilter);
            })
            ->orderBy('created_at', 'desc');

        $notifications = $query->paginate($this->perPage);
        
        // 取得篩選選項
        $users = User::where('is_active', true)->get(['id', 'name', 'username']);
        $types = Notification::distinct()->pluck('type')->filter()->sort();
        
        return view('livewire.admin.notifications.notification-list', [
            'notifications' => $notifications,
            'users' => $users,
            'types' => $types,
        ]);
    }
}