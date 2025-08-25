<div class="space-y-6">
    <!-- 頁面標題和統計 -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">活動通知</h2>
                <p class="text-gray-600">檢視和管理活動記錄相關的通知</p>
            </div>
            <div class="flex space-x-2">
                <button wire:click="markAllAsRead" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                    全部標記為已讀
                </button>
            </div>
        </div>

        <!-- 統計資訊 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ $statistics['total'] }}</div>
                <div class="text-sm text-blue-600">總通知數</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-red-600">{{ $statistics['unread'] }}</div>
                <div class="text-sm text-red-600">未讀通知</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ $statistics['today'] }}</div>
                <div class="text-sm text-green-600">今日通知</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ $statistics['this_week'] }}</div>
                <div class="text-sm text-yellow-600">本週通知</div>
            </div>
        </div>
    </div>

    <!-- 篩選和搜尋 -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
            <!-- 搜尋 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">搜尋</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="搜尋通知內容..."
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- 類型篩選 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">活動類型</label>
                <select wire:model.live="typeFilter" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">全部類型</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 優先級篩選 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">優先級</label>
                <select wire:model.live="priorityFilter" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">全部優先級</option>
                    <option value="low">低</option>
                    <option value="normal">一般</option>
                    <option value="high">高</option>
                    <option value="urgent">緊急</option>
                </select>
            </div>

            <!-- 狀態篩選 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">讀取狀態</label>
                <select wire:model.live="statusFilter" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">全部狀態</option>
                    <option value="unread">未讀</option>
                    <option value="read">已讀</option>
                </select>
            </div>

            <!-- 使用者篩選 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">接收者</label>
                <select wire:model.live="userFilter" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">全部使用者</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 清除篩選 -->
            <div class="flex items-end">
                <button wire:click="clearFilters" 
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                    清除篩選
                </button>
            </div>
        </div>

        <!-- 日期範圍 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">開始日期</label>
                <input type="date" 
                       wire:model.live="dateFrom"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">結束日期</label>
                <input type="date" 
                       wire:model.live="dateTo"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <!-- 批量操作 -->
        @if(!empty($selectedNotifications))
            <div class="flex items-center space-x-4 mb-4 p-3 bg-blue-50 rounded-lg">
                <span class="text-sm text-blue-700">已選擇 {{ count($selectedNotifications) }} 個通知</span>
                <select wire:model="bulkAction" 
                        class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">選擇操作...</option>
                    <option value="mark_read">標記為已讀</option>
                    <option value="mark_unread">標記為未讀</option>
                    @can('activity_logs.delete')
                        <option value="delete">刪除</option>
                    @endcan
                </select>
                <button wire:click="executeBulkAction" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                    執行
                </button>
            </div>
        @endif
    </div>

    <!-- 通知列表 -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" 
                                   wire:model="selectAll"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            狀態
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="updateSort('title')">
                            通知內容
                            @if($sortField === 'title')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="updateSort('priority')">
                            優先級
                            @if($sortField === 'priority')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            接收者
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="updateSort('created_at')">
                            時間
                            @if($sortField === 'created_at')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($notifications as $notification)
                        <tr class="hover:bg-gray-50 {{ $notification->isUnread() ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4">
                                <input type="checkbox" 
                                       wire:model="selectedNotifications" 
                                       value="{{ $notification->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4">
                                @if($notification->isUnread())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        未讀
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        已讀
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-start space-x-3">
                                    @if($notification->icon)
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-{{ $notification->color ?? 'gray' }}-100 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-{{ $notification->color ?? 'gray' }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 {{ $notification->isUnread() ? 'font-bold' : '' }}">
                                            {{ $notification->title }}
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ Str::limit($notification->message, 100) }}
                                        </p>
                                        @if($notification->data && isset($notification->data['count']) && $notification->data['count'] > 1)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                                合併 {{ $notification->data['count'] }} 個事件
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           bg-{{ $notification->priority_color }}-100 text-{{ $notification->priority_color }}-800">
                                    {{ $notification->priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $notification->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $notification->user->username }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $notification->created_at->format('Y-m-d H:i') }}</div>
                                <div class="text-sm text-gray-500">{{ $notification->relative_time }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <button wire:click="showDetail({{ $notification->id }})"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="檢視詳情">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                @if($notification->isUnread())
                                    <button wire:click="markAsRead({{ $notification->id }})"
                                            class="text-green-600 hover:text-green-900"
                                            title="標記為已讀">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                @else
                                    <button wire:click="markAsUnread({{ $notification->id }})"
                                            class="text-yellow-600 hover:text-yellow-900"
                                            title="標記為未讀">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </button>
                                @endif

                                @if($notification->action_url)
                                    <a href="{{ $notification->action_url }}"
                                       class="text-indigo-600 hover:text-indigo-900"
                                       title="前往相關頁面">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @endif

                                @can('activity_logs.delete')
                                    <button wire:click="deleteNotification({{ $notification->id }})"
                                            wire:confirm="確定要刪除這個通知嗎？"
                                            class="text-red-600 hover:text-red-900"
                                            title="刪除">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M15 17h5l-5 5v-5zM9 7H4l5-5v5z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">沒有通知</h3>
                                <p class="mt-1 text-sm text-gray-500">目前沒有符合條件的通知記錄。</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 分頁 -->
        @if($notifications->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

    <!-- 通知詳情模態 -->
    @if($showDetail && $selectedNotification)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <!-- 詳情標題 -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">通知詳情</h3>
                        <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- 詳情內容 -->
                    <div class="space-y-4">
                        <!-- 基本資訊 -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">基本資訊</h4>
                            <dl class="grid grid-cols-1 gap-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">標題：</dt>
                                    <dd class="text-gray-900 font-medium">{{ $selectedNotification->title }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">優先級：</dt>
                                    <dd>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                   bg-{{ $selectedNotification->priority_color }}-100 text-{{ $selectedNotification->priority_color }}-800">
                                            {{ $selectedNotification->priority_label }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">接收者：</dt>
                                    <dd class="text-gray-900">{{ $selectedNotification->user->name }} ({{ $selectedNotification->user->username }})</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">建立時間：</dt>
                                    <dd class="text-gray-900">{{ $selectedNotification->created_at->format('Y-m-d H:i:s') }}</dd>
                                </div>
                                @if($selectedNotification->read_at)
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">讀取時間：</dt>
                                        <dd class="text-gray-900">{{ $selectedNotification->read_at->format('Y-m-d H:i:s') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- 通知內容 -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">通知內容</h4>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $selectedNotification->message }}</p>
                        </div>

                        <!-- 相關資料 -->
                        @if($selectedNotification->data)
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">相關資料</h4>
                                <dl class="grid grid-cols-1 gap-2 text-sm">
                                    @if(isset($selectedNotification->data['activity_id']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">活動 ID：</dt>
                                            <dd class="text-gray-900">{{ $selectedNotification->data['activity_id'] }}</dd>
                                        </div>
                                    @endif
                                    @if(isset($selectedNotification->data['activity_type']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">活動類型：</dt>
                                            <dd class="text-gray-900">{{ $typeOptions[$selectedNotification->data['activity_type']] ?? $selectedNotification->data['activity_type'] }}</dd>
                                        </div>
                                    @endif
                                    @if(isset($selectedNotification->data['risk_level']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">風險等級：</dt>
                                            <dd class="text-gray-900">{{ $selectedNotification->data['risk_level'] }}/10</dd>
                                        </div>
                                    @endif
                                    @if(isset($selectedNotification->data['count']) && $selectedNotification->data['count'] > 1)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">合併事件數：</dt>
                                            <dd class="text-gray-900">{{ $selectedNotification->data['count'] }} 個</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif

                        <!-- 操作按鈕 -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            @if($selectedNotification->action_url)
                                <a href="{{ $selectedNotification->action_url }}"
                                   class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                    前往相關頁面
                                </a>
                            @endif
                            
                            @if($selectedNotification->isUnread())
                                <button wire:click="markAsRead({{ $selectedNotification->id }})"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    標記為已讀
                                </button>
                            @endif
                            
                            <button wire:click="closeDetail"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                關閉
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // 處理通知顯示
    document.addEventListener('livewire:init', () => {
        Livewire.on('notification', (event) => {
            const { type, message } = event;
            
            // 這裡可以整合您的通知系統
            if (type === 'success') {
                // 顯示成功通知
                console.log('Success:', message);
            } else if (type === 'error') {
                // 顯示錯誤通知
                console.log('Error:', message);
            }
        });

        // 監聽新通知
        Livewire.on('notification-received', () => {
            // 可以在這裡添加聲音提示或其他效果
            console.log('New notification received');
        });
    });
</script>
@endpush