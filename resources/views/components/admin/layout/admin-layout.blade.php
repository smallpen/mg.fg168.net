@props(['sidebarOpen' => false])

<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: false, isMobile: window.innerWidth < 1024 }" 
     x-init="
        // 檢測螢幕大小
        checkMobile = () => {
            wasMobile = isMobile;
            isMobile = window.innerWidth < 1024;
            
            // 如果切換到手機版，關閉側邊欄
            if (isMobile) {
                sidebarOpen = false;
            }
            // 如果切換到桌面版，恢復側邊欄狀態
            else if (wasMobile && !isMobile) {
                savedState = localStorage.getItem('sidebarOpen');
                sidebarOpen = savedState !== null ? savedState === 'true' : true;
            }
        };
        
        // 初始化
        checkMobile();
        window.addEventListener('resize', checkMobile);
        
        // 初始化側邊欄狀態
        if (!isMobile) {
            savedState = localStorage.getItem('sidebarOpen');
            sidebarOpen = savedState !== null ? savedState === 'true' : true;
        }
        
        // 監聽側邊欄狀態變化並儲存（僅桌面版）
        $watch('sidebarOpen', value => {
            if (!isMobile) {
                localStorage.setItem('sidebarOpen', value);
            }
        });
     ">

    <!-- 側邊欄 -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out"
         :class="{
             'translate-x-0': sidebarOpen,
             '-translate-x-full': !sidebarOpen && isMobile,
             'lg:translate-x-0': !isMobile
         }"
         x-show="sidebarOpen || !isMobile"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full">
        
        <!-- 側邊欄內容 -->
        <div class="flex flex-col h-full">
            
            <!-- Logo 區域 -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.title') }}</span>
                </div>
                
                <!-- 行動裝置關閉按鈕 -->
                <button @click="sidebarOpen = false" 
                        x-show="isMobile"
                        class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- 導航選單 -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                
                <!-- 儀表板 -->
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                    </svg>
                    {{ __('admin.navigation.dashboard') }}
                </a>
                
                <!-- 使用者管理 -->
                <div x-data="{ open: {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            {{ __('admin.navigation.users') }}
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div x-show="open" x-transition class="ml-8 mt-2 space-y-1">
                        <a href="{{ route('admin.users.index') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.users.index') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.users.list') }}
                        </a>
                        <a href="{{ route('admin.users.create') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.users.create') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.users.create') }}
                        </a>
                        <a href="{{ route('admin.roles.index') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.roles.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.roles.title') }}
                        </a>
                        @can('permissions.view')
                            <a href="{{ route('admin.permissions.index') }}" 
                               class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.permissions.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                {{ __('permissions.titles.permission_management', ['default' => '權限管理']) }}
                            </a>
                        @endcan
                    </div>
                </div>
                
                <!-- 活動記錄 -->
                @can('activity_logs.view')
                <div x-data="{ open: {{ request()->routeIs('admin.activities.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('admin.navigation.activities') }}
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div x-show="open" x-transition class="ml-8 mt-2 space-y-1">
                        <a href="{{ route('admin.activities.index') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.activities.index') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.activities.list') }}
                        </a>
                        @can('system.logs')
                            <a href="{{ route('admin.activities.security') }}" 
                               class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.activities.security') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                {{ __('admin.activities.security') }}
                            </a>
                            <a href="{{ route('admin.activities.stats') }}" 
                               class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.activities.stats') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                {{ __('admin.activities.stats') }}
                            </a>
                            <a href="{{ route('admin.activities.monitor') }}" 
                               class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.activities.monitor') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                {{ __('admin.activities.monitor') }}
                            </a>
                        @endcan
                        @can('activity_logs.export')
                            <a href="{{ route('admin.activities.export') }}" 
                               class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.activities.export') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                {{ __('admin.activities.export') }}
                            </a>
                        @endcan
                    </div>
                </div>
                @endcan
                
                <!-- 系統設定 -->
                <div x-data="{ open: {{ request()->routeIs('admin.settings.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ __('admin.navigation.settings') }}
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div x-show="open" x-transition class="ml-8 mt-2 space-y-1">
                        <a href="{{ route('admin.settings.index') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.settings.index') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.settings.general') ?? '基本設定' }}
                        </a>
                        <a href="{{ route('admin.settings.security') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.settings.security') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.settings.security') ?? '安全設定' }}
                        </a>
                        <a href="{{ route('admin.settings.appearance') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.settings.appearance') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.settings.appearance') ?? '外觀設定' }}
                        </a>
                        <a href="{{ route('admin.settings.notifications') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.settings.notifications') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.settings.notifications') ?? '通知設定' }}
                        </a>
                        <a href="{{ route('admin.settings.integration') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.settings.integration') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.settings.integration') ?? '整合設定' }}
                        </a>
                        <a href="{{ route('admin.settings.maintenance') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.settings.maintenance') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.settings.maintenance') ?? '維護設定' }}
                        </a>
                        <a href="{{ route('admin.settings.backups') }}" 
                           class="block px-4 py-2 text-sm rounded-lg transition-colors duration-150 {{ request()->routeIs('admin.settings.backups') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                            {{ __('admin.settings.backups') ?? '備份管理' }}
                        </a>
                    </div>
                </div>
                
            </nav>
            
            <!-- 使用者資訊 -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ auth()->user()->display_name ?? auth()->user()->name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ auth()->user()->email }}
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- 行動裝置遮罩 -->
    <div x-show="sidebarOpen && isMobile" 
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"></div>
    
    <!-- 主內容區域 -->
    <div class="flex-1 flex flex-col transition-all duration-300 ease-in-out lg:ml-64"
         :class="sidebarOpen && !isMobile ? 'lg:ml-64' : 'lg:ml-64'">
        
        <!-- 頂部導航欄 -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                
                <!-- 左側：選單按鈕和頁面標題 -->
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" 
                            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    @hasSection('page-title')
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            @yield('page-title')
                        </h1>
                    @endif
                </div>
                
                <!-- 右側：語言選擇器、主題切換和使用者選單 -->
                <div class="flex items-center space-x-4">
                    
                    <!-- 語言選擇器 -->
                    <livewire:admin.language-selector />
                    
                    <!-- 主題切換按鈕 -->
                    <button @click="
                        theme = theme === 'dark' ? 'light' : 'dark';
                        localStorage.setItem('theme', theme);
                        if (theme === 'dark') {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    " class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <svg x-show="theme === 'light'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg x-show="theme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>
                    
                    <!-- 使用者下拉選單 -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex items-center space-x-2 p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <div class="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <svg class="w-3 h-3 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                            <div class="py-1">
                                <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">{{ __('admin.user_menu.profile') ?? '個人資料' }}</a>
                                <a href="{{ route('admin.account.settings') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">{{ __('admin.user_menu.settings') ?? '帳號設定' }}</a>
                                <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        {{ __('admin.user_menu.logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </header>
        
        <!-- 主要內容 -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            
            <!-- 麵包屑導航 -->
            @if(isset($breadcrumbs) && !empty($breadcrumbs))
                <x-admin.breadcrumb :breadcrumbs="$breadcrumbs" />
            @endif
            
            <!-- Flash 訊息 -->
            @if(session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info'))
                <div class="mb-6">
                    @if(session()->has('success'))
                        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md dark:bg-green-900/20 dark:border-green-800 dark:text-green-200" x-data="{ show: true }" x-show="show" x-transition>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ session('success') }}</span>
                                </div>
                                <button @click="show = false" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                    
                    @if(session()->has('error'))
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md dark:bg-red-900/20 dark:border-red-800 dark:text-red-200" x-data="{ show: true }" x-show="show" x-transition>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ session('error') }}</span>
                                </div>
                                <button @click="show = false" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                    
                    @if(session()->has('warning'))
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-md dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200" x-data="{ show: true }" x-show="show" x-transition>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <span>{{ session('warning') }}</span>
                                </div>
                                <button @click="show = false" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                    
                    @if(session()->has('info'))
                        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200" x-data="{ show: true }" x-show="show" x-transition>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ session('info') }}</span>
                                </div>
                                <button @click="show = false" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            
            <!-- 頁面內容插槽 -->
            {{ $slot }}
            
        </main>
        
    </div>
    
    <!-- Toast 通知系統 -->
    <div x-data="{ 
        show: false, 
        message: '', 
        type: 'success',
        showToast(data) {
            this.message = data.message;
            this.type = data.type;
            this.show = true;
            setTimeout(() => this.show = false, 4000);
        }
    }"
    x-on:show-toast.window="showToast(Array.isArray($event.detail) ? $event.detail[0] : $event.detail)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="fixed top-4 right-4 z-50 max-w-sm"
    style="display: none;">
        <div class="rounded-lg shadow-lg p-4 border"
             :class="{
                'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-200 dark:border-green-800': type === 'success',
                'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-200 dark:border-red-800': type === 'error',
                'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-200 dark:border-yellow-800': type === 'warning',
                'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-200 dark:border-blue-800': type === 'info'
             }">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-3">
                    <svg x-show="type === 'success'" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg x-show="type === 'error'" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg x-show="type === 'warning'" class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <svg x-show="type === 'info'" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span x-text="message" class="flex-1"></span>
                <button @click="show = false" class="ml-4 flex-shrink-0 hover:opacity-75">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Toast 事件監聽器修復 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 確保 toast 事件監聽器正確工作
        const toastElement = document.querySelector('[x-data*="showToast"]');
        if (toastElement) {
            // 添加全域事件監聽器
            window.addEventListener('show-toast', function(event) {
                console.log('Toast event received:', event.detail);
                
                if (toastElement._x_dataStack && toastElement._x_dataStack[0]) {
                    const alpineData = toastElement._x_dataStack[0];
                    if (alpineData.showToast) {
                        const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                        alpineData.showToast(data);
                        console.log('Toast shown with data:', data);
                    }
                }
            });
            
            // 也監聽 Livewire 事件
            if (window.Livewire) {
                window.Livewire.on('show-toast', function(data) {
                    console.log('Livewire toast event received:', data);
                    
                    if (toastElement._x_dataStack && toastElement._x_dataStack[0]) {
                        const alpineData = toastElement._x_dataStack[0];
                        if (alpineData.showToast) {
                            const toastData = Array.isArray(data) ? data[0] : data;
                            alpineData.showToast(toastData);
                            console.log('Toast shown from Livewire with data:', toastData);
                        }
                    }
                });
            }
        }
    });
    </script>
    
</div>