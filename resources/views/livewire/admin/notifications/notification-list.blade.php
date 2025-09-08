<div class="space-y-6">
    <!-- 篩選和搜尋區域 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 搜尋框 -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        搜尋
                    </label>
                    <input type="text" 
                           id="search"
                           wire:model.live="search"
                           placeholder="搜尋標題、內容或類型..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- 類型篩選 -->
                <div>
                    <label for="typeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        類型
                    </label>
                    <select id="typeFilter" 
                            wire:model.live="typeFilter"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="all">全部類型</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 優先級篩選 -->
                <div>
                    <label for="priorityFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        優先級
                    </label>
                    <select id="priorityFilter" 
                            wire:model.live="priorityFilter"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="all">全部優先級</option>
                        <option value="low">低</option>
                        <option value="normal">一般</option>
                        <option value="high">高</option>
                        <option value="urgent">緊急</option>
                    </select>
                </div>

                <!-- 狀態篩選 -->
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        狀態
                    </label>
                    <select id="statusFilter" 
                            wire:model.live="statusFilter"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="all">全部狀態</option>
                        <option value="unread">未讀</option>
                        <option value="read">已讀</option>
                    </select>
                </div>
            </div>

            <!-- 操作按鈕區域 -->
            <div class="mt-4 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <!-- 重置按鈕 -->
                    @if($search || $typeFilter !== 'all' || $priorityFilter !== 'all' || $statusFilter !== 'all')
                        <button wire:click="resetFilters"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            重置
                        </button>
                    @endif

                    <!-- 批次操作 -->
                    @if(!empty($selectedItems))
                        <div class="flex items-center space-x-2">
                            <button wire:click="bulkAction('mark_read')"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800">
                                標記已讀
                            </button>
                            @can('notifications.delete')
                                <button wire:click="bulkAction('delete')"
                                        onclick="return confirm('確定要刪除選中的通知嗎？')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 hover:text-red-800">
                                    刪除
                                </button>
                            @endcan
                        </div>
                    @endif
                </div>

                <!-- 每頁顯示筆數 -->
                <div class="flex items-center space-x-3">
                    <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        每頁顯示：
                    </label>
                    <select id="perPage"
                            wire:model.live="perPage"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent min-w-[80px]">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }} 筆</option>
                        @endforeach
                    </select>
                    <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        共 {{ $notifications->total() }} 筆
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 通知列表 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        @if($notifications->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" 
                                       wire:model.live="selectAll"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                通知資訊
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                收件人
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                類型/優先級
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                狀態
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                建立時間
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($notifications as $notification)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ !$notification->isRead() ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" 
                                           wire:model.live="selectedItems" 
                                           value="{{ $notification->id }}"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-start space-x-3">
                                        @if($notification->icon)
                                            <div class="flex-shrink-0">
                                                <i class="{{ $notification->icon }} text-lg {{ $notification->color ?: 'text-gray-500' }}"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $notification->title }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                                {{ $notification->message }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $notification->user->name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $notification->user->username }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ ucfirst($notification->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $notification->priority_color }}">
                                            {{ $notification->priority_label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($notification->isRead())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            已讀
                                        </span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $notification->read_at->format('m/d H:i') }}
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                            未讀
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $notification->created_at->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        @if(!$notification->isRead())
                                            <button wire:click="markAsRead({{ $notification->id }})"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                標記已讀
                                            </button>
                                        @endif
                                        
                                        @can('notifications.edit')
                                            <a href="{{ route('admin.notifications.edit', $notification) }}"
                                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                編輯
                                            </a>
                                        @endcan
                                        
                                        @can('notifications.delete')
                                            <button wire:click="deleteNotification({{ $notification->id }})"
                                                    onclick="return confirm('確定要刪除這個通知嗎？')"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                刪除
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 分頁 -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-12h5v12z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有通知</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($search || $typeFilter !== 'all' || $priorityFilter !== 'all' || $statusFilter !== 'all')
                        沒有符合篩選條件的通知
                    @else
                        還沒有任何通知記錄
                    @endif
                </p>
                @can('notifications.create')
                    <div class="mt-6">
                        <a href="{{ route('admin.notifications.create') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            建立第一個通知
                        </a>
                    </div>
                @endcan
            </div>
        @endif
    </div>
</div>