<div class="px-4 py-5 sm:p-6 space-y-6">
    {{-- 頁面標題 --}}
    {{-- 移除頁面級標題，遵循 UI 設計標準 --}}

    {{-- 成功訊息 --}}
    @if($successMessage)
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ $successMessage }}
            </div>
        </div>
    @endif

    {{-- 錯誤訊息 --}}
    @if(!empty($errors))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    @foreach($errors as $field => $fieldErrors)
                        @foreach($fieldErrors as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- 分頁導航 --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('analytics')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'analytics' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    分析工具
                </div>
            </button>
            <button wire:click="switchTab('social')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'social' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    社群登入
                </div>
            </button>       
     <button wire:click="switchTab('storage')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'storage' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                    雲端儲存
                </div>
            </button>
            <button wire:click="switchTab('payment')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'payment' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    支付閘道
                </div>
            </button>
            <button wire:click="switchTab('api')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'api' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    API 金鑰
                </div>
            </button>
        </nav>
    </div>

    {{-- 分頁內容 --}}
    <div class="mt-6">
        {{-- 分析工具設定 --}}
        @if($activeTab === 'analytics')
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">分析工具設定</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        設定 Google Analytics 和 Google Tag Manager 等分析工具
                    </p>
                </div>
                <div class="px-6 py-4 space-y-6">
                    {{-- Google Analytics --}}
                    <div>
                        <label for="google_analytics_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Google Analytics 追蹤 ID
                        </label>
                        <div class="mt-1">
                            <input type="text" 
                                   id="google_analytics_id"
                                   wire:model="analyticsSettings.google_analytics_id"
                                   placeholder="G-XXXXXXXXXX"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Google Analytics 4 的測量 ID，格式為 G-XXXXXXXXXX
                        </p>
                    </div>

                    {{-- Google Tag Manager --}}
                    <div>
                        <label for="google_tag_manager_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Google Tag Manager ID
                        </label>
                        <div class="mt-1">
                            <input type="text" 
                                   id="google_tag_manager_id"
                                   wire:model="analyticsSettings.google_tag_manager_id"
                                   placeholder="GTM-XXXXXXX"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Google Tag Manager 容器 ID，格式為 GTM-XXXXXXX
                        </p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-end">
                    <button wire:click="saveAnalyticsSettings" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        儲存設定
                    </button>
                </div>
            </div>
        @endif     
   {{-- 社群媒體登入設定 --}}
        @if($activeTab === 'social')
            <div class="space-y-6">
                {{-- Google OAuth --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Google OAuth 登入</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    設定 Google 社群媒體登入功能
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if(isset($testResults['google_oauth']))
                                    <div class="flex items-center text-sm {{ $testResults['google_oauth']['success'] ? 'text-green-600' : 'text-red-600' }}">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($testResults['google_oauth']['success'])
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $testResults['google_oauth']['message'] }}
                                    </div>
                                @endif
                                <button wire:click="testIntegration('google_oauth')" 
                                        @if(isset($testingServices['google_oauth']) && $testingServices['google_oauth']) disabled @endif
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium disabled:opacity-50">
                                    @if(isset($testingServices['google_oauth']) && $testingServices['google_oauth'])
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="google_oauth_enabled"
                                   wire:model="socialLoginSettings.google_oauth_enabled"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="google_oauth_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用 Google OAuth 登入
                            </label>
                        </div>
                        
                        @if($socialLoginSettings['google_oauth_enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="google_client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Google Client ID
                                    </label>
                                    <input type="text" 
                                           id="google_client_id"
                                           wire:model="socialLoginSettings.google_client_id"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="google_client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Google Client Secret
                                    </label>
                                    <input type="password" 
                                           id="google_client_secret"
                                           wire:model="socialLoginSettings.google_client_secret"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Facebook OAuth --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Facebook OAuth 登入</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    設定 Facebook 社群媒體登入功能
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if(isset($testResults['facebook_oauth']))
                                    <div class="flex items-center text-sm {{ $testResults['facebook_oauth']['success'] ? 'text-green-600' : 'text-red-600' }}">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($testResults['facebook_oauth']['success'])
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $testResults['facebook_oauth']['message'] }}
                                    </div>
                                @endif
                                <button wire:click="testIntegration('facebook_oauth')" 
                                        @if(isset($testingServices['facebook_oauth']) && $testingServices['facebook_oauth']) disabled @endif
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium disabled:opacity-50">
                                    @if(isset($testingServices['facebook_oauth']) && $testingServices['facebook_oauth'])
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="facebook_oauth_enabled"
                                   wire:model="socialLoginSettings.facebook_oauth_enabled"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="facebook_oauth_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用 Facebook OAuth 登入
                            </label>
                        </div>
                        
                        @if($socialLoginSettings['facebook_oauth_enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="facebook_app_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Facebook App ID
                                    </label>
                                    <input type="text" 
                                           id="facebook_app_id"
                                           wire:model="socialLoginSettings.facebook_app_id"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="facebook_app_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Facebook App Secret
                                    </label>
                                    <input type="password" 
                                           id="facebook_app_secret"
                                           wire:model="socialLoginSettings.facebook_app_secret"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>       
         {{-- GitHub OAuth --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">GitHub OAuth 登入</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    設定 GitHub 社群媒體登入功能
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if(isset($testResults['github_oauth']))
                                    <div class="flex items-center text-sm {{ $testResults['github_oauth']['success'] ? 'text-green-600' : 'text-red-600' }}">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($testResults['github_oauth']['success'])
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $testResults['github_oauth']['message'] }}
                                    </div>
                                @endif
                                <button wire:click="testIntegration('github_oauth')" 
                                        @if(isset($testingServices['github_oauth']) && $testingServices['github_oauth']) disabled @endif
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium disabled:opacity-50">
                                    @if(isset($testingServices['github_oauth']) && $testingServices['github_oauth'])
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="github_oauth_enabled"
                                   wire:model="socialLoginSettings.github_oauth_enabled"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="github_oauth_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用 GitHub OAuth 登入
                            </label>
                        </div>
                        
                        @if($socialLoginSettings['github_oauth_enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="github_client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        GitHub Client ID
                                    </label>
                                    <input type="text" 
                                           id="github_client_id"
                                           wire:model="socialLoginSettings.github_client_id"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="github_client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        GitHub Client Secret
                                    </label>
                                    <input type="password" 
                                           id="github_client_secret"
                                           wire:model="socialLoginSettings.github_client_secret"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end">
                    <button wire:click="saveSocialLoginSettings" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        儲存社群登入設定
                    </button>
                </div>
            </div>
        @endif       
 {{-- 雲端儲存設定 --}}
        @if($activeTab === 'storage')
            <div class="space-y-6">
                {{-- AWS S3 --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Amazon S3 儲存</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    設定 AWS S3 雲端儲存服務
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if(isset($testResults['aws_s3']))
                                    <div class="flex items-center text-sm {{ $testResults['aws_s3']['success'] ? 'text-green-600' : 'text-red-600' }}">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($testResults['aws_s3']['success'])
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $testResults['aws_s3']['message'] }}
                                    </div>
                                @endif
                                <button wire:click="testIntegration('aws_s3')" 
                                        @if(isset($testingServices['aws_s3']) && $testingServices['aws_s3']) disabled @endif
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium disabled:opacity-50">
                                    @if(isset($testingServices['aws_s3']) && $testingServices['aws_s3'])
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="aws_s3_enabled"
                                   wire:model="cloudStorageSettings.aws_s3_enabled"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="aws_s3_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用 AWS S3 儲存
                            </label>
                        </div>
                        
                        @if($cloudStorageSettings['aws_s3_enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="aws_access_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        AWS Access Key
                                    </label>
                                    <input type="text" 
                                           id="aws_access_key"
                                           wire:model="cloudStorageSettings.aws_access_key"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="aws_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        AWS Secret Key
                                    </label>
                                    <input type="password" 
                                           id="aws_secret_key"
                                           wire:model="cloudStorageSettings.aws_secret_key"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="aws_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        AWS 區域
                                    </label>
                                    <select id="aws_region"
                                            wire:model="cloudStorageSettings.aws_region"
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="us-east-1">美國東部（維吉尼亞北部）</option>
                                        <option value="us-west-2">美國西部（奧勒岡）</option>
                                        <option value="ap-northeast-1">亞太地區（東京）</option>
                                        <option value="ap-southeast-1">亞太地區（新加坡）</option>
                                        <option value="eu-west-1">歐洲（愛爾蘭）</option>
                                        <option value="eu-central-1">歐洲（法蘭克福）</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="aws_bucket" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        S3 儲存桶名稱
                                    </label>
                                    <input type="text" 
                                           id="aws_bucket"
                                           wire:model="cloudStorageSettings.aws_bucket"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Google Drive --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Google Drive 儲存</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    設定 Google Drive 雲端儲存服務
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if(isset($testResults['google_drive']))
                                    <div class="flex items-center text-sm {{ $testResults['google_drive']['success'] ? 'text-green-600' : 'text-red-600' }}">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($testResults['google_drive']['success'])
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $testResults['google_drive']['message'] }}
                                    </div>
                                @endif
                                <button wire:click="testIntegration('google_drive')" 
                                        @if(isset($testingServices['google_drive']) && $testingServices['google_drive']) disabled @endif
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium disabled:opacity-50">
                                    @if(isset($testingServices['google_drive']) && $testingServices['google_drive'])
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="google_drive_enabled"
                                   wire:model="cloudStorageSettings.google_drive_enabled"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="google_drive_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用 Google Drive 儲存
                            </label>
                        </div>
                        
                        @if($cloudStorageSettings['google_drive_enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="google_drive_client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Google Drive Client ID
                                    </label>
                                    <input type="text" 
                                           id="google_drive_client_id"
                                           wire:model="cloudStorageSettings.google_drive_client_id"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="google_drive_client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Google Drive Client Secret
                                    </label>
                                    <input type="password" 
                                           id="google_drive_client_secret"
                                           wire:model="cloudStorageSettings.google_drive_client_secret"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end">
                    <button wire:click="saveCloudStorageSettings" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        儲存雲端儲存設定
                    </button>
                </div>
            </div>
        @endif      
  {{-- 支付閘道設定 --}}
        @if($activeTab === 'payment')
            <div class="space-y-6">
                {{-- Stripe --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Stripe 支付</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    設定 Stripe 支付閘道服務
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if(isset($testResults['stripe']))
                                    <div class="flex items-center text-sm {{ $testResults['stripe']['success'] ? 'text-green-600' : 'text-red-600' }}">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($testResults['stripe']['success'])
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $testResults['stripe']['message'] }}
                                    </div>
                                @endif
                                <button wire:click="testIntegration('stripe')" 
                                        @if(isset($testingServices['stripe']) && $testingServices['stripe']) disabled @endif
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium disabled:opacity-50">
                                    @if(isset($testingServices['stripe']) && $testingServices['stripe'])
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="stripe_enabled"
                                   wire:model="paymentGatewaySettings.stripe_enabled"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="stripe_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用 Stripe 支付
                            </label>
                        </div>
                        
                        @if($paymentGatewaySettings['stripe_enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="stripe_publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Stripe 可公開金鑰
                                    </label>
                                    <input type="text" 
                                           id="stripe_publishable_key"
                                           wire:model="paymentGatewaySettings.stripe_publishable_key"
                                           placeholder="pk_..."
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="stripe_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Stripe 秘密金鑰
                                    </label>
                                    <input type="password" 
                                           id="stripe_secret_key"
                                           wire:model="paymentGatewaySettings.stripe_secret_key"
                                           placeholder="sk_..."
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="stripe_webhook_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Stripe Webhook 密鑰（選填）
                                    </label>
                                    <input type="password" 
                                           id="stripe_webhook_secret"
                                           wire:model="paymentGatewaySettings.stripe_webhook_secret"
                                           placeholder="whsec_..."
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        用於驗證 Stripe Webhook 請求的簽名密鑰
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- PayPal --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">PayPal 支付</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    設定 PayPal 支付閘道服務
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if(isset($testResults['paypal']))
                                    <div class="flex items-center text-sm {{ $testResults['paypal']['success'] ? 'text-green-600' : 'text-red-600' }}">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($testResults['paypal']['success'])
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $testResults['paypal']['message'] }}
                                    </div>
                                @endif
                                <button wire:click="testIntegration('paypal')" 
                                        @if(isset($testingServices['paypal']) && $testingServices['paypal']) disabled @endif
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium disabled:opacity-50">
                                    @if(isset($testingServices['paypal']) && $testingServices['paypal'])
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="paypal_enabled"
                                   wire:model="paymentGatewaySettings.paypal_enabled"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="paypal_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用 PayPal 支付
                            </label>
                        </div>
                        
                        @if($paymentGatewaySettings['paypal_enabled'])
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="paypal_client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        PayPal Client ID
                                    </label>
                                    <input type="text" 
                                           id="paypal_client_id"
                                           wire:model="paymentGatewaySettings.paypal_client_id"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="paypal_client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        PayPal Client Secret
                                    </label>
                                    <input type="password" 
                                           id="paypal_client_secret"
                                           wire:model="paymentGatewaySettings.paypal_client_secret"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="paypal_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        PayPal 模式
                                    </label>
                                    <select id="paypal_mode"
                                            wire:model="paymentGatewaySettings.paypal_mode"
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="sandbox">沙盒模式（測試）</option>
                                        <option value="live">正式環境</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end">
                    <button wire:click="savePaymentGatewaySettings" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        儲存支付閘道設定
                    </button>
                </div>
            </div>
        @endif        
{{-- API 金鑰管理 --}}
        @if($activeTab === 'api')
            <div class="space-y-6">
                {{-- 自訂 API 金鑰列表 --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">自訂 API 金鑰</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    管理自訂的第三方服務 API 金鑰，所有金鑰都會加密儲存
                                </p>
                            </div>
                            <button wire:click="$set('showApiKeyModal', true)" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                新增 API 金鑰
                            </button>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        @if(empty($customApiKeys))
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">尚未設定 API 金鑰</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">開始新增您的第一個自訂 API 金鑰</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($customApiKeys as $index => $apiKey)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $apiKey['name'] }}</h4>
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        已加密
                                                    </span>
                                                </div>
                                                @if(!empty($apiKey['description']))
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $apiKey['description'] }}</p>
                                                @endif
                                                <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    建立於 {{ \Carbon\Carbon::parse($apiKey['created_at'])->format('Y-m-d H:i') }}
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <div class="font-mono text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                    {{ substr($apiKey['key'], 0, 8) }}...{{ substr($apiKey['key'], -4) }}
                                                </div>
                                                <button wire:click="removeApiKey({{ $index }})" 
                                                        onclick="return confirm('確定要刪除此 API 金鑰嗎？')"
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- API 金鑰新增模態框 --}}
    @if($showApiKeyModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="$set('showApiKeyModal', false)">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">新增 API 金鑰</h3>
                        <button wire:click="$set('showApiKeyModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="api_key_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                金鑰名稱 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="api_key_name"
                                   wire:model="newApiKey.name"
                                   placeholder="例如：OpenAI API Key"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="api_key_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                API 金鑰 <span class="text-red-500">*</span>
                            </label>
                            <input type="password" 
                                   id="api_key_value"
                                   wire:model="newApiKey.key"
                                   placeholder="輸入 API 金鑰"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="api_key_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                描述（選填）
                            </label>
                            <textarea id="api_key_description"
                                      wire:model="newApiKey.description"
                                      rows="3"
                                      placeholder="描述此 API 金鑰的用途"
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button wire:click="$set('showApiKeyModal', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            取消
                        </button>
                        <button wire:click="addApiKey" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            新增金鑰
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>