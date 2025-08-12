{{-- 頂部導航列主容器 --}}
<header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40 shadow-sm"
        role="banner">
    <div class="px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center justify-between h-16" 
             role="navigation" 
             aria-label="頂部導航列">
            
            {{-- 左側區域：選單切換按鈕和麵包屑導航 --}}
            <div class="flex items-center flex-1 min-w-0">
                
                {{-- 手機版選單切換按鈕 --}}
                <button wire:click="toggleSidebar" 
                        class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all duration-200 lg:hidden touch-feedback"
                        aria-label="開啟導航選單"
                        aria-expanded="false"
                        aria-controls="navigation"
                        title="開啟選單 (Alt + M)">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                {{-- 桌面版選單切換按鈕 --}}
                <button wire:click="toggleSidebar" 
                        class="hidden lg:flex p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all duration-200 touch-feedback"
                        aria-label="切換導航選單"
                        aria-expanded="false"
                        aria-controls="navigation"
                        title="切換選單 (Alt + M)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                {{-- 麵包屑導航區域 --}}
                <div class="ml-4 flex-1 min-w-0">
                    {{-- 頁面標題（手機版顯示） --}}
                    <div class="block sm:hidden">
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                            {{ $pageTitle }}
                        </h1>
                    </div>
                    
                    {{-- 麵包屑導航（桌面版顯示） --}}
                    <div class="hidden sm:block">
                        <livewire:admin.layout.breadcrumb />
                    </div>
                </div>
                
            </div>
            
            {{-- 右側工具列區域 --}}
            <div class="flex items-center space-x-1 sm:space-x-2 lg:space-x-3" 
                 role="toolbar" 
                 aria-label="工具列">
                
                {{-- 全域搜尋元件 --}}
                <div class="hidden sm:block">
                    <livewire:admin.layout.global-search />
                </div>
                
                {{-- 手機版搜尋按鈕 --}}
                <button onclick="Livewire.find('{{ app(\App\Livewire\Admin\Layout\GlobalSearch::class)->getId() }}').call('open')"
                        class="sm:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all duration-200"
                        title="開啟搜尋 (Ctrl + K)"
                        aria-label="開啟搜尋">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
                
                {{-- 手機版語言選擇器 --}}
                <div class="sm:hidden">
                    <livewire:admin.language-selector />
                </div>
                
                {{-- 主題切換元件 --}}
                <livewire:admin.layout.theme-toggle />
                
                {{-- 語言選擇器 --}}
                <div class="hidden sm:block">
                    <livewire:admin.language-selector />
                </div>
                
                {{-- 無障礙設定按鈕 --}}
                <button onclick="Livewire.find('{{ app(\App\Livewire\Admin\Layout\AccessibilitySettings::class)->getId() }}').call('toggle')"
                        class="hidden lg:flex p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all duration-200"
                        title="開啟無障礙設定 (Alt + A)"
                        aria-label="開啟無障礙設定">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </button>
                
                {{-- 通知中心下拉選單 --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open; $wire.toggleNotifications()" 
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all duration-200 relative touch-feedback"
                            :aria-expanded="open"
                            aria-haspopup="true"
                            :aria-label="'通知中心' + (@js($unreadNotifications) > 0 ? ' (' + @js($unreadNotifications) + ' 個未讀通知)' : '')"
                            title="通知中心 @if($unreadNotifications > 0)({{ $unreadNotifications }} 個未讀)@endif">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        
                        {{-- 通知數量徽章 --}}
                        @if($unreadNotifications > 0)
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-medium animate-pulse"
                                  aria-label="{{ $unreadNotifications }} 個未讀通知">
                                {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                            </span>
                        @endif
                    </button>
                    
                    {{-- 通知下拉面板 --}}
                    <div x-show="open && $wire.showNotifications"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-hidden"
                         role="menu"
                         aria-labelledby="notifications-button"
                         tabindex="-1"
                         style="display: none;">
                        
                        {{-- 通知面板標題列 --}}
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">通知中心</h3>
                                    @if($unreadNotifications > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            {{ $unreadNotifications }} 個未讀
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($unreadNotifications > 0)
                                        <button wire:click="markAllAsRead" 
                                                class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-medium transition-colors duration-200"
                                                title="標記所有通知為已讀">
                                            全部已讀
                                        </button>
                                    @endif
                                    <button @click="open = false; $wire.closeAllMenus()"
                                            class="p-1 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                                            title="關閉通知面板">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        {{-- 通知列表容器 --}}
                        <div class="max-h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                            @forelse($recentNotifications as $index => $notification)
                                <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition-colors duration-200 {{ !$notification['read'] ? 'bg-blue-50/50 dark:bg-blue-900/10 border-l-4 border-l-blue-500' : '' }}"
                                     role="menuitem"
                                     tabindex="0">
                                    <div class="flex items-start space-x-3">
                                        
                                        {{-- 通知類型圖示 --}}
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center 
                                                    {{ $notification['type'] === 'success' ? 'bg-green-100 dark:bg-green-900/50' : '' }}
                                                    {{ $notification['type'] === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/50' : '' }}
                                                    {{ $notification['type'] === 'error' ? 'bg-red-100 dark:bg-red-900/50' : '' }}
                                                    {{ $notification['type'] === 'info' ? 'bg-blue-100 dark:bg-blue-900/50' : '' }}
                                                    {{ $notification['type'] === 'security' ? 'bg-purple-100 dark:bg-purple-900/50' : '' }}">
                                            
                                            @switch($notification['type'])
                                                @case('success')
                                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    @break
                                                @case('warning')
                                                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                    @break
                                                @case('error')
                                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    @break
                                                @case('security')
                                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                    </svg>
                                                    @break
                                                @default
                                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                            @endswitch
                                        </div>
                                        
                                        {{-- 通知內容 --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                        {{ $notification['title'] }}
                                                        @if(!$notification['read'])
                                                            <span class="inline-block w-2 h-2 bg-blue-500 rounded-full ml-2"></span>
                                                        @endif
                                                    </p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                                        {{ $notification['message'] }}
                                                    </p>
                                                    <div class="flex items-center justify-between mt-2">
                                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                                            {{ $notification['created_at']->diffForHumans() }}
                                                        </p>
                                                        @if(isset($notification['priority']) && $notification['priority'] === 'high')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                高優先級
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                {{-- 操作按鈕 --}}
                                                <div class="flex items-center space-x-1 ml-2">
                                                    @if(!$notification['read'])
                                                        <button wire:click="markAsRead({{ $notification['id'] }})"
                                                                class="p-1 rounded-full text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200"
                                                                title="標記為已讀">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                    <button class="p-1 rounded-full text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-200"
                                                            title="刪除通知">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            @empty
                                {{-- 空狀態顯示 --}}
                                <div class="px-4 py-12 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">沒有新通知</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">當有新的系統通知時，會在這裡顯示</p>
                                </div>
                            @endforelse
                        </div>
                        
                        {{-- 通知面板底部操作區 --}}
                        @if(!empty($recentNotifications))
                            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <div class="flex items-center justify-between">
                                    <button class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors duration-200">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        通知設定
                                    </button>
                                    <a href="#" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-medium transition-colors duration-200">
                                        查看全部通知
                                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endif
                        
                    </div>
                </div>
                
                {{-- 使用者選單元件 --}}
                <livewire:admin.layout.user-menu />
                
            </div>
            
        </nav>
    </div>
    
    {{-- 載入狀態指示器 --}}
    <div wire:loading.flex wire:target="toggleNotifications,markAsRead,markAllAsRead" 
         class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 items-center justify-center z-50">
        <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 px-3 py-2 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
            <svg class="w-4 h-4 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600 dark:text-gray-400">處理中...</span>
        </div>
    </div>
</header>

{{-- JavaScript 增強功能 --}}
@script
<script>
document.addEventListener('livewire:init', () => {
    // 瀏覽器通知功能
    Livewire.on('show-browser-notification', (event) => {
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                showNotification(event);
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        showNotification(event);
                    }
                });
            }
        }
    });
    
    // 顯示通知的函數
    function showNotification(event) {
        const notification = new Notification(event.title, {
            body: event.body,
            icon: event.icon || '/favicon.ico',
            badge: '/favicon.ico',
            tag: 'admin-notification',
            requireInteraction: event.priority === 'high',
            silent: false,
            timestamp: Date.now(),
            data: {
                url: event.url || null,
                id: event.id || null
            }
        });
        
        // 點擊通知時的處理
        notification.onclick = function(e) {
            e.preventDefault();
            window.focus();
            if (event.url) {
                window.location.href = event.url;
            }
            notification.close();
        };
        
        // 自動關閉通知
        setTimeout(() => {
            notification.close();
        }, event.duration || 5000);
    }
    
    // 鍵盤快捷鍵支援
    document.addEventListener('keydown', (e) => {
        // Alt + M: 切換選單
        if (e.altKey && e.key === 'm') {
            e.preventDefault();
            @this.toggleSidebar();
        }
        
        // Alt + N: 開啟通知面板
        if (e.altKey && e.key === 'n') {
            e.preventDefault();
            @this.toggleNotifications();
        }
        
        // Escape: 關閉所有下拉選單
        if (e.key === 'Escape') {
            @this.closeAllMenus();
        }
    });
    
    // 通知權限請求
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('通知權限已授予');
                } else {
                    console.log('通知權限被拒絕');
                }
            });
        }
    }
    
    // 頁面載入時請求通知權限
    setTimeout(requestNotificationPermission, 2000);
    
    // 監聽頁面可見性變化
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            // 頁面變為可見時，重新載入通知
            @this.call('loadNotifications');
        }
    });
    
    // 定期檢查新通知（每30秒）
    setInterval(() => {
        if (!document.hidden) {
            @this.call('checkNewNotifications');
        }
    }, 30000);
    
    // 觸控設備優化
    if ('ontouchstart' in window) {
        // 為觸控設備添加觸控回饋
        document.querySelectorAll('.touch-feedback').forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            element.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }
    
    // 無障礙功能增強
    function enhanceAccessibility() {
        // 為動態內容添加 aria-live 區域
        const notificationContainer = document.querySelector('[role="menu"]');
        if (notificationContainer) {
            notificationContainer.setAttribute('aria-live', 'polite');
            notificationContainer.setAttribute('aria-atomic', 'false');
        }
        
        // 為按鈕添加鍵盤導航支援
        document.querySelectorAll('button[role="menuitem"]').forEach(button => {
            button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        });
    }
    
    // 初始化無障礙功能
    enhanceAccessibility();
    
    // 監聽 Livewire 更新後重新初始化
    Livewire.hook('morph.updated', () => {
        enhanceAccessibility();
    });
    
    // 網路狀態監控
    window.addEventListener('online', () => {
        console.log('網路連線已恢復');
        @this.call('syncNotifications');
    });
    
    window.addEventListener('offline', () => {
        console.log('網路連線中斷');
    });
    
    // 效能監控
    if ('PerformanceObserver' in window) {
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.entryType === 'navigation') {
                    console.log('頁面載入時間:', entry.loadEventEnd - entry.loadEventStart, 'ms');
                }
            }
        });
        observer.observe({ entryTypes: ['navigation'] });
    }
});

// 全域函數：手動觸發通知檢查
window.checkNotifications = function() {
    Livewire.find('{{ $this->getId() }}').call('checkNewNotifications');
};

// 全域函數：清除所有通知
window.clearAllNotifications = function() {
    Livewire.find('{{ $this->getId() }}').call('markAllAsRead');
};
</script>
@endscript