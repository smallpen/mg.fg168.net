<div class="px-4 sm:px-6 py-4">
    <div class="flex items-center justify-between">
        
        <!-- 左側：選單切換按鈕和頁面標題 -->
        <div class="flex items-center">
            <!-- 選單切換按鈕 -->
            <button wire:click="toggleSidebar" 
                    class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <!-- 頁面標題 -->
            <div class="ml-4">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ $pageTitle }}
                </h1>
                <!-- 麵包屑導航（可選） -->
                <nav class="hidden sm:flex mt-1" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                                管理後台
                            </a>
                        </li>
                        @if(!request()->routeIs('admin.dashboard'))
                            <li class="flex items-center">
                                <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">{{ $pageTitle }}</span>
                            </li>
                        @endif
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- 右側：工具列 -->
        <div class="flex items-center space-x-2 sm:space-x-4">
            
            <!-- 搜尋按鈕（行動裝置隱藏） -->
            <button class="hidden sm:flex p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
            
            <!-- 主題切換按鈕 -->
            @livewire('admin.layout.theme-toggle')
            
            <!-- 語言選擇器 -->
            @livewire('admin.language-selector')
            
            <!-- 通知按鈕 -->
            <div class="relative">
                <button wire:click="toggleNotificationMenu" 
                        class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors duration-200 relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    
                    <!-- 通知數量徽章 -->
                    @if($unreadNotificationCount > 0)
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                        </span>
                    @endif
                </button>
                
                <!-- 通知下拉選單 -->
                @if($notificationMenuOpen)
                    <div class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                         wire:click.away="closeAllMenus">
                        
                        <!-- 通知標題 -->
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">通知</h3>
                                @if($unreadNotificationCount > 0)
                                    <button wire:click="markAllNotificationsAsRead" 
                                            class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                                        全部標記為已讀
                                    </button>
                                @endif
                            </div>
                        </div>
                        
                        <!-- 通知列表 -->
                        <div class="max-h-64 overflow-y-auto">
                            @if($unreadNotificationCount > 0)
                                <!-- 這裡可以顯示實際的通知項目 -->
                                <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">範例通知</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">剛剛</p>
                                </div>
                            @else
                                <div class="px-4 py-8 text-center">
                                    <svg class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">目前沒有新通知</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- 查看所有通知連結 -->
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                            <a href="#" class="block text-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                                查看所有通知
                            </a>
                        </div>
                        
                    </div>
                @endif
            </div>
            
            <!-- 使用者選單 -->
            <div class="relative">
                <button wire:click="toggleUserMenu" 
                        class="flex items-center p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors duration-200">
                    
                    <!-- 使用者頭像 -->
                    <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center mr-2">
                        <span class="text-sm font-medium text-white">
                            {{ $userInitials }}
                        </span>
                    </div>
                    
                    <!-- 使用者名稱（桌面版顯示） -->
                    <div class="hidden sm:block text-left mr-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $userDisplayName }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $userEmail }}</p>
                    </div>
                    
                    <!-- 下拉箭頭 -->
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                
                <!-- 使用者下拉選單 -->
                @if($userMenuOpen)
                    <div class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                         wire:click.away="closeAllMenus">
                        
                        <!-- 使用者資訊 -->
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $userDisplayName }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $userEmail }}</p>
                        </div>
                        
                        <!-- 選單項目 -->
                        <div class="py-1">
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                個人資料
                            </a>
                            
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                帳號設定
                            </a>
                            
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                說明中心
                            </a>
                        </div>
                        
                        <!-- 分隔線 -->
                        <div class="border-t border-gray-200 dark:border-gray-700"></div>
                        
                        <!-- 登出按鈕 -->
                        <div class="py-1">
                            @livewire('admin.auth.logout-button', ['showText' => true, 'classes' => 'flex items-center w-full px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'])
                        </div>
                        
                    </div>
                @endif
            </div>
            
        </div>
        
    </div>
</div>