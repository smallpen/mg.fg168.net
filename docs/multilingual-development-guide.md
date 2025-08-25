# 新功能多語系開發指南

## 概述

本指南詳細說明如何在開發新功能時正確實作多語系支援，確保所有新功能都能在不同語言環境下正常運作。

## 開發流程

### 1. 規劃階段

#### 多語系需求分析
在開始開發新功能前，需要考慮以下多語系需求：

- **使用者介面文字**：所有顯示給使用者的文字
- **錯誤訊息**：驗證錯誤、系統錯誤等訊息
- **通知訊息**：成功、警告、資訊類通知
- **電子郵件內容**：系統發送的郵件內容
- **PDF 報告**：生成的文件內容
- **API 回應**：API 錯誤訊息和狀態文字

#### 翻譯鍵規劃
```php
// 良好的翻譯鍵結構規劃
'notifications' => [
    'title' => '通知管理',
    'subtitle' => '系統通知設定',
    
    'actions' => [
        'create' => '建立通知',
        'edit' => '編輯通知',
        'delete' => '刪除通知',
        'send' => '發送通知',
        'mark_read' => '標記為已讀',
    ],
    
    'fields' => [
        'title' => '通知標題',
        'content' => '通知內容',
        'type' => '通知類型',
        'recipient' => '接收者',
        'send_at' => '發送時間',
    ],
    
    'messages' => [
        'created' => '通知建立成功',
        'updated' => '通知更新成功',
        'deleted' => '通知刪除成功',
        'sent' => '通知發送成功',
        'send_failed' => '通知發送失敗',
    ],
    
    'validation' => [
        'title_required' => '通知標題為必填項目',
        'content_required' => '通知內容為必填項目',
        'recipient_invalid' => '接收者格式不正確',
    ],
    
    'types' => [
        'info' => '資訊',
        'warning' => '警告',
        'error' => '錯誤',
        'success' => '成功',
    ],
],
```

### 2. 實作階段

#### 步驟 1：建立語言檔案

```bash
# 建立新功能的語言檔案
touch lang/zh_TW/notifications.php
touch lang/en/notifications.php
```

**正體中文版本 (lang/zh_TW/notifications.php)：**
```php
<?php

return [
    'title' => '通知管理',
    'subtitle' => '管理系統通知和使用者通知設定',
    
    'actions' => [
        'create' => '建立通知',
        'edit' => '編輯通知',
        'delete' => '刪除通知',
        'send' => '發送通知',
        'send_now' => '立即發送',
        'schedule' => '排程發送',
        'mark_read' => '標記為已讀',
        'mark_unread' => '標記為未讀',
        'mark_all_read' => '全部標記為已讀',
    ],
    
    'fields' => [
        'title' => '通知標題',
        'content' => '通知內容',
        'type' => '通知類型',
        'priority' => '優先級',
        'recipient' => '接收者',
        'recipients' => '接收者清單',
        'send_at' => '發送時間',
        'created_at' => '建立時間',
        'read_at' => '閱讀時間',
        'status' => '狀態',
    ],
    
    'messages' => [
        'created' => '通知建立成功',
        'updated' => '通知更新成功',
        'deleted' => '通知刪除成功',
        'sent' => '通知發送成功',
        'send_failed' => '通知發送失敗：:error',
        'marked_read' => '通知已標記為已讀',
        'marked_unread' => '通知已標記為未讀',
        'all_marked_read' => '所有通知已標記為已讀',
        'no_notifications' => '目前沒有通知',
        'loading' => '載入中...',
    ],
    
    'validation' => [
        'title_required' => '通知標題為必填項目',
        'title_max' => '通知標題不能超過 :max 個字元',
        'content_required' => '通知內容為必填項目',
        'content_max' => '通知內容不能超過 :max 個字元',
        'type_required' => '請選擇通知類型',
        'type_invalid' => '通知類型無效',
        'recipient_required' => '請選擇接收者',
        'recipient_invalid' => '接收者格式不正確',
        'send_at_future' => '發送時間必須是未來時間',
    ],
    
    'types' => [
        'info' => '資訊',
        'warning' => '警告',
        'error' => '錯誤',
        'success' => '成功',
        'announcement' => '公告',
    ],
    
    'priorities' => [
        'low' => '低',
        'normal' => '一般',
        'high' => '高',
        'urgent' => '緊急',
    ],
    
    'statuses' => [
        'draft' => '草稿',
        'scheduled' => '已排程',
        'sent' => '已發送',
        'failed' => '發送失敗',
    ],
    
    'filters' => [
        'all' => '全部',
        'unread' => '未讀',
        'read' => '已讀',
        'today' => '今天',
        'this_week' => '本週',
        'this_month' => '本月',
    ],
    
    'placeholders' => [
        'search' => '搜尋通知...',
        'title' => '請輸入通知標題',
        'content' => '請輸入通知內容',
        'select_type' => '請選擇通知類型',
        'select_recipients' => '請選擇接收者',
    ],
    
    'confirmations' => [
        'delete' => '確定要刪除這個通知嗎？',
        'delete_multiple' => '確定要刪除選中的 :count 個通知嗎？',
        'send_now' => '確定要立即發送這個通知嗎？',
        'mark_all_read' => '確定要將所有通知標記為已讀嗎？',
    ],
];
```

**英文版本 (lang/en/notifications.php)：**
```php
<?php

return [
    'title' => 'Notification Management',
    'subtitle' => 'Manage system notifications and user notification settings',
    
    'actions' => [
        'create' => 'Create Notification',
        'edit' => 'Edit Notification',
        'delete' => 'Delete Notification',
        'send' => 'Send Notification',
        'send_now' => 'Send Now',
        'schedule' => 'Schedule Send',
        'mark_read' => 'Mark as Read',
        'mark_unread' => 'Mark as Unread',
        'mark_all_read' => 'Mark All as Read',
    ],
    
    'fields' => [
        'title' => 'Notification Title',
        'content' => 'Notification Content',
        'type' => 'Notification Type',
        'priority' => 'Priority',
        'recipient' => 'Recipient',
        'recipients' => 'Recipients',
        'send_at' => 'Send At',
        'created_at' => 'Created At',
        'read_at' => 'Read At',
        'status' => 'Status',
    ],
    
    'messages' => [
        'created' => 'Notification created successfully',
        'updated' => 'Notification updated successfully',
        'deleted' => 'Notification deleted successfully',
        'sent' => 'Notification sent successfully',
        'send_failed' => 'Failed to send notification: :error',
        'marked_read' => 'Notification marked as read',
        'marked_unread' => 'Notification marked as unread',
        'all_marked_read' => 'All notifications marked as read',
        'no_notifications' => 'No notifications available',
        'loading' => 'Loading...',
    ],
    
    'validation' => [
        'title_required' => 'Notification title is required',
        'title_max' => 'Notification title may not be greater than :max characters',
        'content_required' => 'Notification content is required',
        'content_max' => 'Notification content may not be greater than :max characters',
        'type_required' => 'Please select a notification type',
        'type_invalid' => 'Invalid notification type',
        'recipient_required' => 'Please select recipients',
        'recipient_invalid' => 'Invalid recipient format',
        'send_at_future' => 'Send time must be in the future',
    ],
    
    'types' => [
        'info' => 'Information',
        'warning' => 'Warning',
        'error' => 'Error',
        'success' => 'Success',
        'announcement' => 'Announcement',
    ],
    
    'priorities' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    
    'statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sent' => 'Sent',
        'failed' => 'Failed',
    ],
    
    'filters' => [
        'all' => 'All',
        'unread' => 'Unread',
        'read' => 'Read',
        'today' => 'Today',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
    ],
    
    'placeholders' => [
        'search' => 'Search notifications...',
        'title' => 'Enter notification title',
        'content' => 'Enter notification content',
        'select_type' => 'Select notification type',
        'select_recipients' => 'Select recipients',
    ],
    
    'confirmations' => [
        'delete' => 'Are you sure you want to delete this notification?',
        'delete_multiple' => 'Are you sure you want to delete the selected :count notifications?',
        'send_now' => 'Are you sure you want to send this notification now?',
        'mark_all_read' => 'Are you sure you want to mark all notifications as read?',
    ],
];
```

#### 步驟 2：在 Controller 中使用翻譯

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $this->authorize('notifications.view');
        
        $notifications = Notification::paginate(15);
        
        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'title' => __('notifications.title'),
        ]);
    }
    
    public function create()
    {
        $this->authorize('notifications.create');
        
        return view('admin.notifications.create', [
            'title' => __('notifications.actions.create'),
        ]);
    }
    
    public function store(Request $request)
    {
        $this->authorize('notifications.create');
        
        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required|max:1000',
            'type' => 'required|in:info,warning,error,success,announcement',
        ], [
            'title.required' => __('notifications.validation.title_required'),
            'title.max' => __('notifications.validation.title_max', ['max' => 255]),
            'content.required' => __('notifications.validation.content_required'),
            'content.max' => __('notifications.validation.content_max', ['max' => 1000]),
            'type.required' => __('notifications.validation.type_required'),
            'type.in' => __('notifications.validation.type_invalid'),
        ]);
        
        try {
            $notification = Notification::create($request->all());
            
            return redirect()
                ->route('admin.notifications.index')
                ->with('success', __('notifications.messages.created'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('notifications.messages.send_failed', ['error' => $e->getMessage()]));
        }
    }
    
    public function destroy(Notification $notification)
    {
        $this->authorize('notifications.delete');
        
        try {
            $notification->delete();
            
            return redirect()
                ->route('admin.notifications.index')
                ->with('success', __('notifications.messages.deleted'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('common.messages.error'));
        }
    }
}
```

#### 步驟 3：在 Blade 模板中使用翻譯

```blade
{{-- resources/views/admin/notifications/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', __('notifications.title'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ __('notifications.title') }}</h3>
                    @can('notifications.create')
                        <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> {{ __('notifications.actions.create') }}
                        </a>
                    @endcan
                </div>
                
                <div class="card-body">
                    @if($notifications->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('notifications.fields.title') }}</th>
                                        <th>{{ __('notifications.fields.type') }}</th>
                                        <th>{{ __('notifications.fields.status') }}</th>
                                        <th>{{ __('notifications.fields.created_at') }}</th>
                                        <th>{{ __('common.actions.title') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifications as $notification)
                                        <tr>
                                            <td>{{ $notification->title }}</td>
                                            <td>
                                                <span class="badge badge-{{ $notification->type }}">
                                                    {{ __('notifications.types.' . $notification->type) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    {{ __('notifications.statuses.' . $notification->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $notification->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @can('notifications.edit')
                                                    <a href="{{ route('admin.notifications.edit', $notification) }}" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="{{ __('notifications.actions.edit') }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                
                                                @can('notifications.delete')
                                                    <form method="POST" 
                                                          action="{{ route('admin.notifications.destroy', $notification) }}" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('{{ __('notifications.confirmations.delete') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="{{ __('notifications.actions.delete') }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{ $notifications->links() }}
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">{{ __('notifications.messages.no_notifications') }}</p>
                            @can('notifications.create')
                                <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                                    {{ __('notifications.actions.create') }}
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### 步驟 4：在 Livewire 元件中使用翻譯

```php
<?php

namespace App\Livewire\Admin\Notifications;

use Livewire\Component;
use App\Models\Notification;

class NotificationList extends Component
{
    public $search = '';
    public $filter = 'all';
    public $selectedNotifications = [];
    
    protected $listeners = ['refreshNotifications' => '$refresh'];
    
    public function markAsRead($notificationId)
    {
        $this->authorize('notifications.edit');
        
        $notification = Notification::findOrFail($notificationId);
        $notification->markAsRead();
        
        $this->dispatch('notification', [
            'type' => 'success',
            'message' => __('notifications.messages.marked_read')
        ]);
        
        $this->dispatch('refreshNotifications');
    }
    
    public function markAllAsRead()
    {
        $this->authorize('notifications.edit');
        
        Notification::whereNull('read_at')->update(['read_at' => now()]);
        
        $this->dispatch('notification', [
            'type' => 'success',
            'message' => __('notifications.messages.all_marked_read')
        ]);
        
        $this->dispatch('refreshNotifications');
    }
    
    public function deleteSelected()
    {
        $this->authorize('notifications.delete');
        
        if (empty($this->selectedNotifications)) {
            $this->dispatch('notification', [
                'type' => 'warning',
                'message' => __('common.messages.no_items_selected')
            ]);
            return;
        }
        
        Notification::whereIn('id', $this->selectedNotifications)->delete();
        
        $this->dispatch('notification', [
            'type' => 'success',
            'message' => __('notifications.messages.deleted')
        ]);
        
        $this->selectedNotifications = [];
        $this->dispatch('refreshNotifications');
    }
    
    public function render()
    {
        $notifications = Notification::query()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('content', 'like', '%' . $this->search . '%');
            })
            ->when($this->filter !== 'all', function ($query) {
                switch ($this->filter) {
                    case 'unread':
                        $query->whereNull('read_at');
                        break;
                    case 'read':
                        $query->whereNotNull('read_at');
                        break;
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                }
            })
            ->latest()
            ->paginate(15);
            
        return view('livewire.admin.notifications.notification-list', [
            'notifications' => $notifications,
        ]);
    }
}
```

對應的 Livewire 視圖：

```blade
{{-- resources/views/livewire/admin/notifications/notification-list.blade.php --}}
<div>
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" 
                   wire:model.live="search" 
                   class="form-control" 
                   placeholder="{{ __('notifications.placeholders.search') }}">
        </div>
        <div class="col-md-3">
            <select wire:model.live="filter" class="form-control">
                <option value="all">{{ __('notifications.filters.all') }}</option>
                <option value="unread">{{ __('notifications.filters.unread') }}</option>
                <option value="read">{{ __('notifications.filters.read') }}</option>
                <option value="today">{{ __('notifications.filters.today') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="btn-group w-100">
                <button wire:click="markAllAsRead" 
                        class="btn btn-outline-primary"
                        onclick="return confirm('{{ __('notifications.confirmations.mark_all_read') }}')">
                    {{ __('notifications.actions.mark_all_read') }}
                </button>
                <button wire:click="deleteSelected" 
                        class="btn btn-outline-danger"
                        onclick="return confirm('{{ __('notifications.confirmations.delete_multiple', ['count' => count($selectedNotifications)]) }}')">
                    {{ __('common.actions.delete_selected') }}
                </button>
            </div>
        </div>
    </div>
    
    @if($notifications->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" 
                                   wire:model="selectAll" 
                                   class="form-check-input">
                        </th>
                        <th>{{ __('notifications.fields.title') }}</th>
                        <th>{{ __('notifications.fields.type') }}</th>
                        <th>{{ __('notifications.fields.created_at') }}</th>
                        <th>{{ __('common.actions.title') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $notification)
                        <tr class="{{ $notification->read_at ? '' : 'table-warning' }}">
                            <td>
                                <input type="checkbox" 
                                       wire:model="selectedNotifications" 
                                       value="{{ $notification->id }}"
                                       class="form-check-input">
                            </td>
                            <td>
                                <strong>{{ $notification->title }}</strong>
                                @if(!$notification->read_at)
                                    <span class="badge badge-primary badge-sm">{{ __('notifications.filters.unread') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $notification->type }}">
                                    {{ __('notifications.types.' . $notification->type) }}
                                </span>
                            </td>
                            <td>{{ $notification->created_at->diffForHumans() }}</td>
                            <td>
                                @if(!$notification->read_at)
                                    <button wire:click="markAsRead({{ $notification->id }})" 
                                            class="btn btn-sm btn-outline-success"
                                            title="{{ __('notifications.actions.mark_read') }}">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{ $notifications->links() }}
    @else
        <div class="text-center py-5">
            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
            <p class="text-muted">{{ __('notifications.messages.no_notifications') }}</p>
        </div>
    @endif
</div>
```

#### 步驟 5：在 JavaScript 中使用翻譯

```blade
{{-- 在 Blade 模板中傳遞翻譯到 JavaScript --}}
@push('scripts')
<script>
    // 傳遞翻譯到 JavaScript
    window.translations = {
        notifications: @json(__('notifications')),
        common: @json(__('common'))
    };
    
    // 翻譯函數
    function __(key, replace = {}) {
        let translation = key.split('.').reduce((obj, k) => obj && obj[k], window.translations) || key;
        
        Object.keys(replace).forEach(k => {
            translation = translation.replace(`:${k}`, replace[k]);
        });
        
        return translation;
    }
    
    // 使用範例
    document.addEventListener('DOMContentLoaded', function() {
        // 動態更新按鈕文字
        const createBtn = document.querySelector('#create-notification-btn');
        if (createBtn) {
            createBtn.textContent = __('notifications.actions.create');
        }
        
        // 確認對話框
        const deleteButtons = document.querySelectorAll('.delete-notification');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm(__('notifications.confirmations.delete'))) {
                    e.preventDefault();
                }
            });
        });
        
        // 動態載入通知
        function loadNotifications() {
            fetch('/api/notifications')
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        document.querySelector('#notifications-container').innerHTML = 
                            `<p class="text-muted">${__('notifications.messages.no_notifications')}</p>`;
                    }
                })
                .catch(error => {
                    console.error(__('common.messages.error'), error);
                });
        }
    });
</script>
@endpush
```

### 3. 測試階段

#### 步驟 1：撰寫多語系單元測試

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationMultilingualTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed();
        $this->actingAs(User::factory()->create());
    }
    
    public function test_notification_index_displays_chinese_text()
    {
        app()->setLocale('zh_TW');
        
        $response = $this->get('/admin/notifications');
        
        $response->assertStatus(200);
        $response->assertSee('通知管理');
        $response->assertSee('建立通知');
    }
    
    public function test_notification_index_displays_english_text()
    {
        app()->setLocale('en');
        
        $response = $this->get('/admin/notifications');
        
        $response->assertStatus(200);
        $response->assertSee('Notification Management');
        $response->assertSee('Create Notification');
    }
    
    public function test_notification_validation_messages_in_chinese()
    {
        app()->setLocale('zh_TW');
        
        $response = $this->post('/admin/notifications', []);
        
        $response->assertSessionHasErrors([
            'title' => '通知標題為必填項目',
            'content' => '通知內容為必填項目',
        ]);
    }
    
    public function test_notification_validation_messages_in_english()
    {
        app()->setLocale('en');
        
        $response = $this->post('/admin/notifications', []);
        
        $response->assertSessionHasErrors([
            'title' => 'Notification title is required',
            'content' => 'Notification content is required',
        ]);
    }
    
    public function test_notification_success_message_in_chinese()
    {
        app()->setLocale('zh_TW');
        
        $response = $this->post('/admin/notifications', [
            'title' => '測試通知',
            'content' => '測試內容',
            'type' => 'info',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success', '通知建立成功');
    }
    
    public function test_notification_types_translation()
    {
        $notification = Notification::factory()->create(['type' => 'info']);
        
        // 測試中文翻譯
        app()->setLocale('zh_TW');
        $this->assertEquals('資訊', __('notifications.types.info'));
        
        // 測試英文翻譯
        app()->setLocale('en');
        $this->assertEquals('Information', __('notifications.types.info'));
    }
}
```

#### 步驟 2：撰寫 Livewire 多語系測試

```php
<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Livewire\Admin\Notifications\NotificationList;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationListMultilingualTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed();
        $this->actingAs(User::factory()->create());
    }
    
    public function test_component_displays_chinese_text()
    {
        app()->setLocale('zh_TW');
        
        Notification::factory()->create(['title' => '測試通知']);
        
        Livewire::test(NotificationList::class)
            ->assertSee('全部')
            ->assertSee('未讀')
            ->assertSee('已讀')
            ->assertSee('測試通知');
    }
    
    public function test_component_displays_english_text()
    {
        app()->setLocale('en');
        
        Notification::factory()->create(['title' => 'Test Notification']);
        
        Livewire::test(NotificationList::class)
            ->assertSee('All')
            ->assertSee('Unread')
            ->assertSee('Read')
            ->assertSee('Test Notification');
    }
    
    public function test_mark_as_read_success_message_in_chinese()
    {
        app()->setLocale('zh_TW');
        
        $notification = Notification::factory()->create();
        
        Livewire::test(NotificationList::class)
            ->call('markAsRead', $notification->id)
            ->assertDispatched('notification', function ($event) {
                return $event['message'] === '通知已標記為已讀';
            });
    }
    
    public function test_search_placeholder_translation()
    {
        app()->setLocale('zh_TW');
        
        Livewire::test(NotificationList::class)
            ->assertSee('搜尋通知...');
            
        app()->setLocale('en');
        
        Livewire::test(NotificationList::class)
            ->assertSee('Search notifications...');
    }
}
```

#### 步驟 3：撰寫端到端測試

```php
<?php
// tests/Browser/NotificationMultilingualTest.php

namespace Tests\Browser;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Notification;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class NotificationMultilingualTest extends DuskTestCase
{
    use DatabaseMigrations;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }
    
    public function test_language_switching_on_notification_page()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/notifications')
                    ->assertSee('通知管理') // 預設中文
                    ->click('.language-selector')
                    ->click('[data-locale="en"]')
                    ->waitForText('Notification Management')
                    ->assertSee('Create Notification')
                    ->click('.language-selector')
                    ->click('[data-locale="zh_TW"]')
                    ->waitForText('通知管理')
                    ->assertSee('建立通知');
        });
    }
    
    public function test_notification_creation_form_translation()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            // 測試中文表單
            $browser->loginAs($user)
                    ->visit('/admin/notifications/create')
                    ->assertSee('建立通知')
                    ->assertSee('通知標題')
                    ->assertSee('通知內容')
                    ->assertSee('通知類型');
                    
            // 切換到英文
            $browser->click('.language-selector')
                    ->click('[data-locale="en"]')
                    ->waitForText('Create Notification')
                    ->assertSee('Notification Title')
                    ->assertSee('Notification Content')
                    ->assertSee('Notification Type');
        });
    }
    
    public function test_notification_validation_messages()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            // 測試中文驗證訊息
            $browser->loginAs($user)
                    ->visit('/admin/notifications/create')
                    ->press('儲存')
                    ->waitForText('通知標題為必填項目')
                    ->assertSee('通知內容為必填項目');
                    
            // 切換到英文測試驗證訊息
            $browser->click('.language-selector')
                    ->click('[data-locale="en"]')
                    ->waitForText('Create Notification')
                    ->press('Save')
                    ->waitForText('Notification title is required')
                    ->assertSee('Notification content is required');
        });
    }
}
```

### 4. 部署和維護

#### 部署檢查清單

```bash
#!/bin/bash
# deploy-multilingual-check.sh

echo "=== 多語系功能部署檢查 ==="

# 1. 檢查語言檔案完整性
echo "1. 檢查語言檔案完整性..."
docker-compose exec app php artisan lang:check --strict
if [ $? -ne 0 ]; then
    echo "❌ 語言檔案檢查失敗"
    exit 1
fi

# 2. 檢查新增的翻譯鍵
echo "2. 檢查新增的翻譯鍵..."
NEW_KEYS=$(git diff HEAD~1 --name-only | grep "lang/" | wc -l)
if [ $NEW_KEYS -gt 0 ]; then
    echo "發現 $NEW_KEYS 個語言檔案變更，執行完整性檢查..."
    docker-compose exec app php artisan lang:compare zh_TW en
fi

# 3. 執行多語系測試
echo "3. 執行多語系測試..."
docker-compose exec app php artisan test --testsuite=Multilingual
if [ $? -ne 0 ]; then
    echo "❌ 多語系測試失敗"
    exit 1
fi

# 4. 檢查硬編碼文字
echo "4. 檢查硬編碼文字..."
HARDCODED=$(grep -r "通知管理\|Notification Management" resources/views/ --exclude-dir=node_modules | grep -v "__(" | wc -l)
if [ $HARDCODED -gt 0 ]; then
    echo "⚠️  發現可能的硬編碼文字，請檢查"
    grep -r "通知管理\|Notification Management" resources/views/ --exclude-dir=node_modules | grep -v "__("
fi

echo "✅ 多語系功能部署檢查完成"
```

#### 監控腳本

```bash
#!/bin/bash
# monitor-multilingual.sh

echo "=== 多語系功能監控報告 ==="
echo "日期: $(date)"

# 1. 檢查缺少翻譯鍵的錯誤
echo "1. 缺少翻譯鍵錯誤統計:"
MISSING_KEYS=$(docker-compose exec app grep -c "Missing translation" storage/logs/multilingual.log 2>/dev/null || echo "0")
echo "   過去24小時: $MISSING_KEYS 個錯誤"

if [ $MISSING_KEYS -gt 10 ]; then
    echo "   ⚠️  警告: 缺少翻譯鍵錯誤過多"
    echo "   最近的錯誤:"
    docker-compose exec app tail -5 storage/logs/multilingual.log
fi

# 2. 語言使用統計
echo "2. 使用者語言偏好統計:"
docker-compose exec app php artisan tinker --execute="
\$stats = DB::table('users')->select('locale', DB::raw('count(*) as count'))->groupBy('locale')->get();
foreach (\$stats as \$stat) {
    echo \"   {\$stat->locale}: {\$stat->count} 使用者\" . PHP_EOL;
}
"

# 3. 語言切換成功率
echo "3. 語言切換統計:"
SWITCH_SUCCESS=$(docker-compose exec app grep -c "Language switched" storage/logs/laravel.log 2>/dev/null || echo "0")
SWITCH_FAILED=$(docker-compose exec app grep -c "Language switch failed" storage/logs/laravel.log 2>/dev/null || echo "0")
echo "   成功: $SWITCH_SUCCESS 次"
echo "   失敗: $SWITCH_FAILED 次"

# 4. 效能統計
echo "4. 語言檔案載入效能:"
docker-compose exec app php artisan tinker --execute="
\$start = microtime(true);
app('translator')->getLoader()->load('zh_TW', 'notifications');
\$time = (microtime(true) - \$start) * 1000;
echo '   語言檔案載入時間: ' . round(\$time, 2) . ' ms' . PHP_EOL;
"

echo "=== 監控報告完成 ==="
```

## 最佳實踐總結

### 1. 開發階段最佳實踐

- **同步開發**: 新功能開發時同步建立翻譯檔案
- **結構化翻譯鍵**: 使用清晰的層級結構組織翻譯鍵
- **參數化翻譯**: 使用參數而非字串拼接
- **一致性檢查**: 定期檢查翻譯術語的一致性
- **測試驅動**: 為多語系功能撰寫完整的測試

### 2. 程式碼品質標準

- **避免硬編碼**: 所有使用者可見文字都必須使用翻譯函數
- **錯誤處理**: 適當處理翻譯鍵不存在的情況
- **效能考量**: 合理使用快取機制
- **無障礙支援**: 確保多語系功能支援無障礙操作

### 3. 團隊協作規範

- **程式碼審核**: 翻譯相關變更必須經過審核
- **文檔維護**: 及時更新多語系相關文檔
- **知識分享**: 定期分享多語系開發經驗
- **持續改進**: 根據使用者回饋持續改進翻譯品質

這個開發指南提供了完整的多語系功能開發流程，確保新功能能夠正確支援多語系環境。