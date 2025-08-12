<!DOCTYPE html>
<html lang="@locale" dir="@dir">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('layout.language.title', [], 'zh_TW') }} - {{ __('admin.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('css/rtl-support.css') }}">
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- 頂部導航 -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ __('admin.title') }}
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- 語言選擇器 -->
                        <livewire:admin.layout.language-selector />
                        
                        <!-- 當前時間顯示 -->
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            @datetime(now())
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- 主要內容 -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                <!-- 標題區域 -->
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ __('layout.language.title', [], app()->getLocale()) }}
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        {{ __('layout.language.current') }}: {{ app(App\Services\LanguageService::class)->getCurrentLocaleInfo()['name'] }}
                    </p>
                </div>

                <!-- 功能展示區域 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- 日期時間格式化 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ __('layout.topnav.notifications') }}
                        </h3>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div>{{ __('layout.notifications.time_ago', ['time' => '5 分鐘']) }}</div>
                            <div>@datetime(now())</div>
                            <div>@date(now())</div>
                            <div>@time(now())</div>
                            <div>@timeago(now()->subHours(2))</div>
                        </div>
                    </div>

                    <!-- 數字格式化 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ __('admin.dashboard.stats.total_users') }}
                        </h3>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div>@number(12345.67, 2)</div>
                            <div>@currency(999.99)</div>
                            <div>@percentage(85.5, 1)</div>
                            <div>@filesize(1048576)</div>
                        </div>
                    </div>

                    <!-- 導航選單 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ __('layout.sidebar.dashboard') }}
                        </h3>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div>{{ __('layout.sidebar.user_management') }}</div>
                            <div>{{ __('layout.sidebar.role_management') }}</div>
                            <div>{{ __('layout.sidebar.permission_management') }}</div>
                            <div>{{ __('layout.sidebar.system_settings') }}</div>
                            <div>{{ __('layout.sidebar.activity_logs') }}</div>
                        </div>
                    </div>

                    <!-- 表單元素 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ __('admin.users.search') }}
                        </h3>
                        <div class="space-y-3">
                            <input 
                                type="text" 
                                placeholder="{{ __('admin.users.search_placeholder') }}"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                            <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <option>{{ __('admin.users.filter_by_role') }}</option>
                                <option>{{ __('admin.users.all_roles') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- 按鈕和操作 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ __('admin.actions.create') }}
                        </h3>
                        <div class="space-y-2">
                            <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                {{ __('admin.users.add_user') }}
                            </button>
                            <button class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                {{ __('admin.actions.save') }}
                            </button>
                            <button class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                {{ __('admin.actions.delete') }}
                            </button>
                        </div>
                    </div>

                    <!-- 狀態訊息 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ __('layout.loading.default') }}
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="text-green-600">{{ __('layout.success.saved') }}</div>
                            <div class="text-red-600">{{ __('layout.errors.network_error') }}</div>
                            <div class="text-yellow-600">{{ __('layout.loading.processing') }}</div>
                            <div class="text-blue-600">{{ __('layout.loading.switching_language') }}</div>
                        </div>
                    </div>
                </div>

                <!-- 語言資訊 -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ __('layout.language.current') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <strong>{{ __('layout.language.current') }}:</strong> 
                            {{ app(App\Services\LanguageService::class)->getCurrentLocaleInfo()['name'] }}
                        </div>
                        <div>
                            <strong>Locale Code:</strong> @locale
                        </div>
                        <div>
                            <strong>Direction:</strong> @dir
                        </div>
                        <div>
                            <strong>RTL:</strong> @isRtl
                        </div>
                    </div>
                </div>

                <!-- RTL 支援展示 -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" dir="rtl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        RTL Support Demo (عربي)
                    </h3>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center justify-between">
                            <span>مرحبا بك في النظام</span>
                            <button class="px-3 py-1 bg-blue-600 text-white rounded">حفظ</button>
                        </div>
                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                            <span>🏠</span>
                            <span>الصفحة الرئيسية</span>
                            <span>></span>
                            <span>إدارة المستخدمين</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    @livewireScripts
    <script src="{{ asset('js/rtl-support.js') }}"></script>
    
    <!-- Toast 通知系統 -->
    <div x-data="{ 
        show: false, 
        message: '', 
        type: 'success',
        showToast(data) {
            this.message = data.message;
            this.type = data.type;
            this.show = true;
            setTimeout(() => this.show = false, 3000);
        }
    }"
    @toast.window="showToast($event.detail)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="fixed top-4 right-4 z-50 max-w-sm"
    style="display: none;">
        <div class="rounded-lg shadow-lg p-4"
             :class="{
                'bg-green-500 text-white': type === 'success',
                'bg-red-500 text-white': type === 'error',
                'bg-yellow-500 text-white': type === 'warning',
                'bg-blue-500 text-white': type === 'info'
             }">
            <div class="flex items-center">
                <span x-text="message"></span>
                <button @click="show = false" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</body>
</html>