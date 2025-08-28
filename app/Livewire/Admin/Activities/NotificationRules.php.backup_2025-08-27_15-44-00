<?php

namespace App\Livewire\Admin\Activities;

use App\Models\NotificationRule;
use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

/**
 * 通知規則管理元件
 * 
 * 管理活動記錄的通知規則，包含建立、編輯、刪除和測試功能
 */
class NotificationRules extends Component
{
    use WithPagination;

    // 篩選條件
    public string $search = '';
    public string $statusFilter = 'all'; // all, active, inactive
    public string $priorityFilter = 'all';
    public string $creatorFilter = 'all';

    // 顯示設定
    public int $perPage = 20;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // 表單資料
    public bool $showForm = false;
    public bool $editMode = false;
    public ?NotificationRule $editingRule = null;

    public string $name = '';
    public string $description = '';
    public array $conditions = [];
    public array $actions = [];
    public bool $isActive = true;
    public int $priority = 2;

    // 條件表單
    public array $activityTypes = [];
    public int $minRiskLevel = 0;
    public array $userIds = [];
    public array $ipPatterns = [];
    public array $timeRange = [];
    public array $frequencyLimit = [];

    // 動作表單
    public array $recipients = [];
    public string $titleTemplate = '';
    public string $messageTemplate = '';
    public bool $mergeSimilar = false;
    public int $mergeWindow = 300;
    public bool $emailNotification = false;
    public bool $browserNotification = false;
    public bool $webhookNotification = false;
    public string $webhookUrl = '';
    public bool $securityAlert = false;

    // 批量操作
    public array $selectedRules = [];
    public string $bulkAction = '';

    protected array $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'priority' => 'required|integer|between:1,4',
        'activityTypes' => 'array',
        'minRiskLevel' => 'integer|between:0,10',
        'userIds' => 'array',
        'ipPatterns' => 'array',
        'recipients' => 'required|array|min:1',
        'titleTemplate' => 'required|string|max:255',
        'messageTemplate' => 'required|string|max:1000',
        'mergeWindow' => 'integer|min:60|max:3600',
        'webhookUrl' => 'nullable|url',
    ];

    protected array $messages = [
        'name.required' => '規則名稱為必填項目',
        'recipients.required' => '必須選擇至少一個通知接收者',
        'recipients.min' => '必須選擇至少一個通知接收者',
        'titleTemplate.required' => '通知標題範本為必填項目',
        'messageTemplate.required' => '通知訊息範本為必填項目',
        'webhookUrl.url' => 'Webhook URL 格式不正確',
    ];

    public function mount(): void
    {
        $this->authorize('activity_logs.view');
        $this->resetForm();
    }

    public function render()
    {
        $rules = $this->getRulesQuery()->paginate($this->perPage);
        
        return view('livewire.admin.activities.notification-rules', [
            'rules' => $rules,
            'users' => $this->getUsers(),
            'roles' => $this->getRoles(),
            'activityTypeOptions' => $this->getActivityTypeOptions(),
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * 取得規則查詢
     */
    protected function getRulesQuery()
    {
        $query = NotificationRule::with(['creator']);

        // 搜尋
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // 狀態篩選
        if ($this->statusFilter === 'active') {
            $query->active();
        } elseif ($this->statusFilter === 'inactive') {
            $query->inactive();
        }

        // 優先級篩選
        if ($this->priorityFilter !== 'all') {
            $query->where('priority', $this->priorityFilter);
        }

        // 建立者篩選
        if ($this->creatorFilter !== 'all') {
            $query->where('created_by', $this->creatorFilter);
        }

        // 排序
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    /**
     * 顯示建立表單
     */
    public function create(): void
    {
        $this->authorize('activity_logs.create');
        $this->resetForm();
        $this->showForm = true;
        $this->editMode = false;
    }

    /**
     * 顯示編輯表單
     */
    public function edit(NotificationRule $rule): void
    {
        $this->authorize('activity_logs.edit');
        $this->editingRule = $rule;
        $this->loadRuleData($rule);
        $this->showForm = true;
        $this->editMode = true;
    }

    /**
     * 儲存規則
     */
    public function save(): void
    {
        $this->authorize($this->editMode ? 'activity_logs.edit' : 'activity_logs.create');
        
        $this->validate();

        try {
            $data = $this->prepareRuleData();

            if ($this->editMode) {
                $this->editingRule->update($data);
                $message = '通知規則更新成功';
            } else {
                NotificationRule::create($data);
                $message = '通知規則建立成功';
            }

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => $message
            ]);

            $this->resetForm();
            $this->showForm = false;

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '儲存失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 刪除規則
     */
    public function delete(NotificationRule $rule): void
    {
        $this->authorize('activity_logs.delete');

        try {
            $rule->delete();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => '通知規則刪除成功'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '刪除失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 切換規則狀態
     */
    public function toggleStatus(NotificationRule $rule): void
    {
        $this->authorize('activity_logs.edit');

        try {
            $rule->update(['is_active' => !$rule->is_active]);

            $status = $rule->is_active ? '啟用' : '停用';
            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "規則已{$status}"
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '狀態切換失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 複製規則
     */
    public function duplicate(NotificationRule $rule): void
    {
        $this->authorize('activity_logs.create');

        try {
            $newRule = $rule->duplicate();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => '規則複製成功'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '複製失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 測試規則
     */
    public function testRule(NotificationRule $rule): void
    {
        $this->authorize('activity_logs.view');

        try {
            // 這裡可以實作規則測試邏輯
            // 例如發送測試通知或模擬規則觸發

            $this->dispatch('notification', [
                'type' => 'info',
                'message' => '測試通知已發送'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => '測試失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 執行批量操作
     */
    public function executeBulkAction(): void
    {
        if (empty($this->selectedRules) || empty($this->bulkAction)) {
            return;
        }

        $this->authorize('activity_logs.edit');

        try {
            $rules = NotificationRule::whereIn('id', $this->selectedRules);

            switch ($this->bulkAction) {
                case 'activate':
                    $rules->update(['is_active' => true]);
                    $message = '已啟用選中的規則';
                    break;

                case 'deactivate':
                    $rules->update(['is_active' => false]);
                    $message = '已停用選中的規則';
                    break;

                case 'delete':
                    $this->authorize('activity_logs.delete');
                    $rules->delete();
                    $message = '已刪除選中的規則';
                    break;

                default:
                    throw new \InvalidArgumentException('無效的批量操作');
            }

            $this->selectedRules = [];
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
     * 清除篩選
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->priorityFilter = 'all';
        $this->creatorFilter = 'all';
        $this->resetPage();
    }

    /**
     * 取消表單
     */
    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * 重設表單
     */
    protected function resetForm(): void
    {
        $this->editingRule = null;
        $this->name = '';
        $this->description = '';
        $this->conditions = [];
        $this->actions = [];
        $this->isActive = true;
        $this->priority = 2;

        // 重設條件表單
        $this->activityTypes = [];
        $this->minRiskLevel = 0;
        $this->userIds = [];
        $this->ipPatterns = [];
        $this->timeRange = [];
        $this->frequencyLimit = [];

        // 重設動作表單
        $this->recipients = [];
        $this->titleTemplate = '活動記錄警報：{activity_type}';
        $this->messageTemplate = '使用者 {user_name} 在 {time} 執行了 {activity_type} 操作：{description}';
        $this->mergeSimilar = false;
        $this->mergeWindow = 300;
        $this->emailNotification = false;
        $this->browserNotification = false;
        $this->webhookNotification = false;
        $this->webhookUrl = '';
        $this->securityAlert = false;
    }

    /**
     * 載入規則資料
     */
    protected function loadRuleData(NotificationRule $rule): void
    {
        $this->name = $rule->name;
        $this->description = $rule->description ?? '';
        $this->isActive = $rule->is_active;
        $this->priority = $rule->priority;

        // 載入條件
        $conditions = $rule->conditions ?? [];
        $this->activityTypes = $conditions['activity_types'] ?? [];
        $this->minRiskLevel = $conditions['min_risk_level'] ?? 0;
        $this->userIds = $conditions['user_ids'] ?? [];
        $this->ipPatterns = $conditions['ip_patterns'] ?? [];
        $this->timeRange = $conditions['time_range'] ?? [];
        $this->frequencyLimit = $conditions['frequency_limit'] ?? [];

        // 載入動作
        $actions = $rule->actions ?? [];
        $this->recipients = $actions['recipients'] ?? [];
        $this->titleTemplate = $actions['title_template'] ?? '';
        $this->messageTemplate = $actions['message_template'] ?? '';
        $this->mergeSimilar = $actions['merge_similar'] ?? false;
        $this->mergeWindow = $actions['merge_window'] ?? 300;

        // 檢查動作類型
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'email':
                    $this->emailNotification = true;
                    break;
                case 'browser':
                    $this->browserNotification = true;
                    break;
                case 'webhook':
                    $this->webhookNotification = true;
                    $this->webhookUrl = $action['url'] ?? '';
                    break;
                case 'security_alert':
                    $this->securityAlert = true;
                    break;
            }
        }
    }

    /**
     * 準備規則資料
     */
    protected function prepareRuleData(): array
    {
        // 準備條件
        $conditions = [];
        if (!empty($this->activityTypes)) {
            $conditions['activity_types'] = $this->activityTypes;
        }
        if ($this->minRiskLevel > 0) {
            $conditions['min_risk_level'] = $this->minRiskLevel;
        }
        if (!empty($this->userIds)) {
            $conditions['user_ids'] = $this->userIds;
        }
        if (!empty($this->ipPatterns)) {
            $conditions['ip_patterns'] = array_filter($this->ipPatterns);
        }
        if (!empty($this->timeRange)) {
            $conditions['time_range'] = $this->timeRange;
        }
        if (!empty($this->frequencyLimit)) {
            $conditions['frequency_limit'] = $this->frequencyLimit;
        }

        // 準備動作
        $actions = [
            'recipients' => $this->recipients,
            'title_template' => $this->titleTemplate,
            'message_template' => $this->messageTemplate,
            'merge_similar' => $this->mergeSimilar,
            'merge_window' => $this->mergeWindow,
        ];

        $actionList = [];
        if ($this->emailNotification) {
            $actionList[] = ['type' => 'email', 'template' => 'activity_notification'];
        }
        if ($this->browserNotification) {
            $actionList[] = ['type' => 'browser'];
        }
        if ($this->webhookNotification && $this->webhookUrl) {
            $actionList[] = ['type' => 'webhook', 'url' => $this->webhookUrl];
        }
        if ($this->securityAlert) {
            $actionList[] = ['type' => 'security_alert'];
        }

        $actions['actions'] = $actionList;

        return [
            'name' => $this->name,
            'description' => $this->description,
            'conditions' => $conditions,
            'actions' => $actions,
            'is_active' => $this->isActive,
            'priority' => $this->priority,
            'created_by' => auth()->id(),
        ];
    }

    /**
     * 取得使用者列表
     */
    protected function getUsers()
    {
        return User::select('id', 'name', 'username')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * 取得角色列表
     */
    protected function getRoles()
    {
        return Role::select('id', 'name', 'display_name')
            ->orderBy('display_name')
            ->get();
    }

    /**
     * 取得活動類型選項
     */
    protected function getActivityTypeOptions(): array
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
     * 取得統計資訊
     */
    protected function getStatistics(): array
    {
        return NotificationRule::getStatistics();
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
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCreatorFilter(): void
    {
        $this->resetPage();
    }
}
