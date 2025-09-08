<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\Notification;
use App\Models\User;
use Livewire\Component;
use Illuminate\Validation\Rule;

class NotificationForm extends Component
{
    public ?Notification $notification = null;
    public bool $isEdit = false;

    // 表單欄位
    public string $title = '';
    public string $message = '';
    public string $type = 'info';
    public string $priority = 'normal';
    public array $selectedUsers = [];
    public bool $sendToAll = false;
    public string $icon = '';
    public string $color = '';
    public string $actionUrl = '';
    public bool $isBrowserNotification = false;

    // 可用選項
    public array $typeOptions = [
        'info' => '資訊',
        'success' => '成功',
        'warning' => '警告',
        'error' => '錯誤',
        'system' => '系統',
    ];

    public array $priorityOptions = [
        'low' => '低',
        'normal' => '一般',
        'high' => '高',
        'urgent' => '緊急',
    ];

    /**
     * 元件掛載
     */
    public function mount(?Notification $notification = null): void
    {
        if ($notification && $notification->exists) {
            $this->notification = $notification;
            $this->isEdit = true;
            $this->authorize('notifications.edit');
            
            // 填入現有資料
            $this->title = $notification->title;
            $this->message = $notification->message;
            $this->type = $notification->type;
            $this->priority = $notification->priority;
            $this->selectedUsers = [$notification->user_id];
            $this->icon = $notification->icon ?? '';
            $this->color = $notification->color ?? '';
            $this->actionUrl = $notification->action_url ?? '';
            $this->isBrowserNotification = $notification->is_browser_notification;
        } else {
            $this->authorize('notifications.create');
        }
    }

    /**
     * 驗證規則
     */
    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => ['required', Rule::in(array_keys($this->typeOptions))],
            'priority' => ['required', Rule::in(array_keys($this->priorityOptions))],
            'selectedUsers' => 'required_unless:sendToAll,true|array|min:1',
            'selectedUsers.*' => 'exists:users,id',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'actionUrl' => 'nullable|url|max:255',
            'isBrowserNotification' => 'boolean',
        ];
    }

    /**
     * 驗證訊息
     */
    protected function messages(): array
    {
        return [
            'title.required' => '標題為必填項目',
            'title.max' => '標題不能超過 255 個字元',
            'message.required' => '訊息內容為必填項目',
            'message.max' => '訊息內容不能超過 1000 個字元',
            'type.required' => '請選擇通知類型',
            'priority.required' => '請選擇優先級',
            'selectedUsers.required_unless' => '請選擇收件人',
            'selectedUsers.min' => '至少選擇一個收件人',
            'actionUrl.url' => '操作連結格式不正確',
        ];
    }

    /**
     * 全選使用者切換
     */
    public function updatedSendToAll(): void
    {
        if ($this->sendToAll) {
            $this->selectedUsers = User::where('is_active', true)->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    /**
     * 儲存通知
     */
    public function save(): void
    {
        $this->validate();

        try {
            if ($this->isEdit) {
                // 更新現有通知
                $this->notification->update([
                    'title' => $this->title,
                    'message' => $this->message,
                    'type' => $this->type,
                    'priority' => $this->priority,
                    'icon' => $this->icon ?: null,
                    'color' => $this->color ?: null,
                    'action_url' => $this->actionUrl ?: null,
                    'is_browser_notification' => $this->isBrowserNotification,
                ]);

                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => '通知已更新'
                ]);
            } else {
                // 建立新通知
                $users = $this->sendToAll 
                    ? User::where('is_active', true)->pluck('id')
                    : collect($this->selectedUsers);

                foreach ($users as $userId) {
                    Notification::create([
                        'user_id' => $userId,
                        'title' => $this->title,
                        'message' => $this->message,
                        'type' => $this->type,
                        'priority' => $this->priority,
                        'icon' => $this->icon ?: null,
                        'color' => $this->color ?: null,
                        'action_url' => $this->actionUrl ?: null,
                        'is_browser_notification' => $this->isBrowserNotification,
                    ]);
                }

                $count = $users->count();
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => "已成功發送通知給 {$count} 位使用者"
                ]);
            }

            $this->redirect(route('admin.notifications.index'));

        } catch (\Exception $e) {
            logger()->error('儲存通知失敗', [
                'error' => $e->getMessage(),
                'data' => $this->all(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '儲存失敗，請重試'
            ]);
        }
    }

    /**
     * 取消操作
     */
    public function cancel(): void
    {
        $this->redirect(route('admin.notifications.index'));
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        return view('livewire.admin.notifications.notification-form', [
            'users' => $users,
        ]);
    }
}