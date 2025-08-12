<div class="relative" x-data="{ open: @entangle('isOpen') }">
    {{-- 通知按鈕 --}}
    <button 
        @click="open = !open"
        class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors duration-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
        :class="{ 'bg-gray-100 dark:bg-gray-700': open }"
    >
        {{-- 通知圖示 --}}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        
        {{-- 未讀數量徽章 --}}
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 rounded-full
                {{ $hasHighPriorityUnread ? 'bg-red-500 animate-pulse' : 'bg-blue-500' }}">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- 通知下拉面板 --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="open = false"
        class="absolute right-0 z-50 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700"
        style="display: none;"
    >
        {{-- 面板標題 --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">通知中心</h3>
            <div class="flex items-center space-x-2">
                {{-- 重新整理按鈕 --}}
                <button 
                    wire:click="refresh"
                    class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded transition-colors"
                    title="重新整理"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                
                {{-- 全部標記已讀按鈕 --}}
                @if($unreadCount > 0)
                    <button 
                        wire:click="markAllAsRead"
                        class="px-3 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-900/20 rounded-full transition-colors"
                    >
                        全部標記已讀
                    </button>
                @endif
            </div>
        </div>

        {{-- 篩選標籤 --}}
        <div class="flex items-center space-x-1 p-3 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
            @foreach(['all' => '全部', 'unread' => '未讀', 'security' => '安全', 'system' => '系統', 'user_action' => '操作', 'report' => '報告'] as $filterKey => $filterLabel)
                <button 
                    wire:click="setFilter('{{ $filterKey }}')"
                    class="px-3 py-1 text-xs font-medium rounded-full whitespace-nowrap transition-colors
                        {{ $filter === $filterKey 
                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                            : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    {{ $filterLabel }}
                </button>
            @endforeach
        </div>

        {{-- 通知列表 --}}
        <div class="max-h-96 overflow-y-auto">
            @if($notifications->count() > 0)
                @foreach($notifications as $notification)
                    <div 
                        wire:click="clickNotification({{ $notification->id }})"
                        class="flex items-start p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors
                            {{ $notification->isUnread() ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}"
                    >
                        {{-- 通知圖示 --}}
                        <div class="flex-shrink-0 mr-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center
                                {{ $notification->type_config['color'] === 'red' ? 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400' : '' }}
                                {{ $notification->type_config['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400' : '' }}
                                {{ $notification->type_config['color'] === 'green' ? 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400' : '' }}
                                {{ $notification->type_config['color'] === 'blue' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : '' }}
                                {{ !in_array($notification->type_config['color'], ['red', 'yellow', 'green', 'blue']) ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}">
                                @switch($notification->type_config['icon'])
                                    @case('shield-exclamation')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        @break
                                    @case('cog')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        @break
                                    @case('user')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        @break
                                    @case('chart-bar')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        @break
                                    @default
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                @endswitch
                            </div>
                        </div>

                        {{-- 通知內容 --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $notification->title }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">
                                        {{ $notification->message }}
                                    </p>
                                </div>
                                
                                {{-- 操作按鈕 --}}
                                <div class="flex items-center space-x-1 ml-2">
                                    {{-- 優先級標籤 --}}
                                    @if($notification->priority !== 'normal')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            {{ $notification->priority_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : '' }}
                                            {{ $notification->priority_color === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' : '' }}
                                            {{ $notification->priority_color === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}">
                                            {{ $notification->priority_label }}
                                        </span>
                                    @endif
                                    
                                    {{-- 刪除按鈕 --}}
                                    <button 
                                        wire:click.stop="deleteNotification({{ $notification->id }})"
                                        class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded transition-colors"
                                        title="刪除通知"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            {{-- 時間和狀態 --}}
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $notification->relative_time }}
                                </span>
                                
                                {{-- 未讀指示器 --}}
                                @if($notification->isUnread())
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- 分頁 --}}
                @if($notifications->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $notifications->links() }}
                    </div>
                @endif

                {{-- 載入更多按鈕 --}}
                @if($notifications->hasMorePages())
                    <div class="p-4 text-center border-t border-gray-200 dark:border-gray-700">
                        <button 
                            wire:click="loadMore"
                            class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-900/20 rounded-lg transition-colors"
                        >
                            載入更多
                        </button>
                    </div>
                @endif
            @else
                {{-- 空狀態 --}}
                <div class="p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        @if($filter === 'unread')
                            沒有未讀通知
                        @elseif($filter === 'all')
                            暫無通知
                        @else
                            沒有 {{ $this->getFilterLabel($filter) }} 類型的通知
                        @endif
                    </p>
                </div>
            @endif
        </div>

        {{-- 查看全部通知連結 --}}
        @if($notifications->count() > 0)
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
                <a 
                    href="#" 
                    class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                >
                    查看全部通知
                </a>
            </div>
        @endif
    </div>
</div>

{{-- JavaScript 處理瀏覽器通知 --}}
@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    // 請求通知權限
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // 監聽顯示瀏覽器通知事件
    Livewire.on('show-browser-notification', (data) => {
        if ('Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification(data.title, {
                body: data.body,
                icon: data.icon,
                tag: data.tag,
                requireInteraction: data.requireInteraction || false
            });
            
            // 點擊通知時的處理
            notification.onclick = function() {
                window.focus();
                this.close();
            };
            
            // 自動關閉通知
            if (!data.requireInteraction) {
                setTimeout(() => {
                    notification.close();
                }, 5000);
            }
        }
    });
});
</script>
@endpush
