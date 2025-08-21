<div>
    {{-- 預覽面板 --}}
    @if($showPreview)
        <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                {{-- 背景遮罩 --}}
                <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="stopPreview"></div>
                
                {{-- 預覽面板 --}}
                <section class="absolute right-0 top-0 flex h-full w-screen max-w-2xl flex-col overflow-y-scroll bg-white shadow-xl">
                    {{-- 標題列 --}}
                    <div class="flex items-center justify-between bg-gray-50 px-4 py-6 sm:px-6">
                        <div class="flex items-center">
                            <div class="flex h-7 items-center">
                                <button type="button" class="relative -m-2 p-2 text-gray-400 hover:text-gray-500" wire:click="stopPreview">
                                    <span class="absolute -inset-0.5"></span>
                                    <span class="sr-only">關閉面板</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="ml-3">
                                <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title">
                                    設定預覽與測試
                                </h2>
                                <p class="text-sm text-gray-500">
                                    即時預覽設定變更效果並測試連線
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- 預覽模式切換 --}}
                    <div class="border-b border-gray-200 bg-white px-4 py-4 sm:px-6">
                        <nav class="-mb-px flex space-x-8">
                            @foreach($this->availableModes as $mode => $label)
                                <button 
                                    wire:click="switchPreviewMode('{{ $mode }}')"
                                    class="@if($previewMode === $mode) border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 @endif whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    {{-- 預覽內容 --}}
                    <div class="flex-1 px-4 py-6 sm:px-6">
                        {{-- 主題預覽 --}}
                        @if($previewMode === 'theme')
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">主題預覽</h3>
                                    <p class="mt-1 text-sm text-gray-500">預覽主題和顏色變更效果</p>
                                </div>

                                {{-- 預覽區域 --}}
                                <div class="rounded-lg border border-gray-200 p-4" 
                                     style="{{ $this->previewCssVariables }}"
                                     x-data="{ theme: '{{ $themePreview['theme'] }}' }"
                                     :class="theme === 'dark' ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'">
                                    
                                    {{-- 模擬標頭 --}}
                                    <div class="mb-4 flex items-center justify-between border-b pb-4" 
                                         :class="theme === 'dark' ? 'border-gray-700' : 'border-gray-200'">
                                        <div class="flex items-center space-x-3">
                                            @if($themePreview['logo_url'])
                                                <img src="{{ $themePreview['logo_url'] }}" alt="Logo" class="h-8 w-8 rounded">
                                            @else
                                                <div class="h-8 w-8 rounded bg-gradient-to-r from-blue-500 to-purple-600"></div>
                                            @endif
                                            <span class="text-lg font-semibold">{{ $this->getSettingValue('app.name') ?? 'Laravel Admin System' }}</span>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button class="rounded px-3 py-1 text-sm font-medium text-white" 
                                                    style="background-color: {{ $themePreview['primary_color'] }}">
                                                主要按鈕
                                            </button>
                                            <button class="rounded border px-3 py-1 text-sm font-medium" 
                                                    style="border-color: {{ $themePreview['secondary_color'] }}; color: {{ $themePreview['secondary_color'] }}">
                                                次要按鈕
                                            </button>
                                        </div>
                                    </div>

                                    {{-- 模擬內容 --}}
                                    <div class="space-y-4">
                                        <div>
                                            <h4 class="text-base font-medium">範例內容</h4>
                                            <p class="mt-1 text-sm" :class="theme === 'dark' ? 'text-gray-300' : 'text-gray-600'">
                                                這是預覽內容，展示主題變更後的效果。
                                            </p>
                                        </div>
                                        
                                        <div class="flex space-x-4">
                                            <a href="#" class="text-sm font-medium hover:underline" 
                                               style="color: {{ $themePreview['primary_color'] }}">
                                                主要連結
                                            </a>
                                            <a href="#" class="text-sm font-medium hover:underline" 
                                               style="color: {{ $themePreview['secondary_color'] }}">
                                                次要連結
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- 主題設定摘要 --}}
                                <div class="rounded-lg bg-gray-50 p-4">
                                    <h4 class="text-sm font-medium text-gray-900">目前預覽設定</h4>
                                    <dl class="mt-2 space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">主題模式：</dt>
                                            <dd class="text-gray-900">{{ $themePreview['theme'] === 'auto' ? '自動' : ($themePreview['theme'] === 'dark' ? '暗色' : '亮色') }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">主要顏色：</dt>
                                            <dd class="flex items-center space-x-2">
                                                <span class="inline-block h-4 w-4 rounded" style="background-color: {{ $themePreview['primary_color'] }}"></span>
                                                <span class="text-gray-900">{{ $themePreview['primary_color'] }}</span>
                                            </dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">次要顏色：</dt>
                                            <dd class="flex items-center space-x-2">
                                                <span class="inline-block h-4 w-4 rounded" style="background-color: {{ $themePreview['secondary_color'] }}"></span>
                                                <span class="text-gray-900">{{ $themePreview['secondary_color'] }}</span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        @endif

                        {{-- 郵件預覽 --}}
                        @if($previewMode === 'email')
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">郵件設定測試</h3>
                                    <p class="mt-1 text-sm text-gray-500">測試 SMTP 連線和郵件發送功能</p>
                                </div>

                                {{-- SMTP 連線測試 --}}
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-base font-medium text-gray-900">SMTP 連線測試</h4>
                                            <p class="text-sm text-gray-500">測試郵件伺服器連線設定</p>
                                        </div>
                                        <button 
                                            wire:click="testSmtpConnection"
                                            :disabled="@js($this->isTestingConnection('smtp'))"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                        >
                                            @if($this->isTestingConnection('smtp'))
                                                <svg class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                測試中...
                                            @else
                                                測試連線
                                            @endif
                                        </button>
                                    </div>

                                    {{-- 測試結果 --}}
                                    @if($this->getConnectionTestResult('smtp'))
                                        @php $result = $this->getConnectionTestResult('smtp') @endphp
                                        <div class="mt-4 rounded-md p-4 @if($result['success']) bg-green-50 @else bg-red-50 @endif">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    @if($result['success'])
                                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium @if($result['success']) text-green-800 @else text-red-800 @endif">
                                                        {{ $result['message'] }}
                                                    </h3>
                                                    <div class="mt-2 text-sm @if($result['success']) text-green-700 @else text-red-700 @endif">
                                                        <p>測試時間：{{ $result['tested_at'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- 郵件設定摘要 --}}
                                <div class="rounded-lg bg-gray-50 p-4">
                                    <h4 class="text-sm font-medium text-gray-900">目前郵件設定</h4>
                                    <dl class="mt-2 space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">SMTP 主機：</dt>
                                            <dd class="text-gray-900">{{ $this->getSettingValue('notification.smtp_host') ?: '未設定' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">SMTP 埠號：</dt>
                                            <dd class="text-gray-900">{{ $this->getSettingValue('notification.smtp_port') ?: '未設定' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">加密方式：</dt>
                                            <dd class="text-gray-900">{{ $this->getSettingValue('notification.smtp_encryption') ?: '未設定' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">寄件者：</dt>
                                            <dd class="text-gray-900">{{ $this->getSettingValue('notification.from_name') ?: '未設定' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        @endif

                        {{-- 整合預覽 --}}
                        @if($previewMode === 'integration')
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">整合服務測試</h3>
                                    <p class="mt-1 text-sm text-gray-500">測試第三方服務連線和 API 整合</p>
                                </div>

                                {{-- AWS S3 測試 --}}
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-base font-medium text-gray-900">AWS S3 連線測試</h4>
                                            <p class="text-sm text-gray-500">測試 Amazon S3 儲存服務連線</p>
                                        </div>
                                        <button 
                                            wire:click="testAwsS3Connection"
                                            :disabled="@js($this->isTestingConnection('aws_s3'))"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                        >
                                            @if($this->isTestingConnection('aws_s3'))
                                                <svg class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                測試中...
                                            @else
                                                測試連線
                                            @endif
                                        </button>
                                    </div>

                                    {{-- S3 測試結果 --}}
                                    @if($this->getConnectionTestResult('aws_s3'))
                                        @php $result = $this->getConnectionTestResult('aws_s3') @endphp
                                        <div class="mt-4 rounded-md p-4 @if($result['success']) bg-green-50 @else bg-red-50 @endif">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    @if($result['success'])
                                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium @if($result['success']) text-green-800 @else text-red-800 @endif">
                                                        {{ $result['message'] }}
                                                    </h3>
                                                    <div class="mt-2 text-sm @if($result['success']) text-green-700 @else text-red-700 @endif">
                                                        <p>測試時間：{{ $result['tested_at'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Google OAuth 測試 --}}
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-base font-medium text-gray-900">Google OAuth 連線測試</h4>
                                            <p class="text-sm text-gray-500">測試 Google OAuth 認證設定</p>
                                        </div>
                                        <button 
                                            wire:click="testGoogleOAuthConnection"
                                            :disabled="@js($this->isTestingConnection('google_oauth'))"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                        >
                                            @if($this->isTestingConnection('google_oauth'))
                                                <svg class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                測試中...
                                            @else
                                                測試連線
                                            @endif
                                        </button>
                                    </div>

                                    {{-- OAuth 測試結果 --}}
                                    @if($this->getConnectionTestResult('google_oauth'))
                                        @php $result = $this->getConnectionTestResult('google_oauth') @endphp
                                        <div class="mt-4 rounded-md p-4 @if($result['success']) bg-green-50 @else bg-red-50 @endif">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    @if($result['success'])
                                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium @if($result['success']) text-green-800 @else text-red-800 @endif">
                                                        {{ $result['message'] }}
                                                    </h3>
                                                    <div class="mt-2 text-sm @if($result['success']) text-green-700 @else text-red-700 @endif">
                                                        <p>測試時間：{{ $result['tested_at'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- 批量測試按鈕 --}}
                                <div class="flex justify-center">
                                    <button 
                                        wire:click="testAllConnections"
                                        class="inline-flex items-center rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600"
                                    >
                                        測試所有連線
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- 佈局預覽 --}}
                        @if($previewMode === 'layout')
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">佈局預覽</h3>
                                    <p class="mt-1 text-sm text-gray-500">預覽基本設定對系統佈局的影響</p>
                                </div>

                                {{-- 應用程式資訊預覽 --}}
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <h4 class="text-base font-medium text-gray-900">應用程式資訊</h4>
                                    <div class="mt-2 space-y-2">
                                        <div>
                                            <span class="text-sm text-gray-500">應用程式名稱：</span>
                                            <span class="text-sm font-medium text-gray-900">{{ $this->getSettingValue('app.name') ?: 'Laravel Admin System' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-500">描述：</span>
                                            <span class="text-sm text-gray-900">{{ $this->getSettingValue('app.description') ?: '功能完整的管理系統' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-500">時區：</span>
                                            <span class="text-sm text-gray-900">{{ $this->getSettingValue('app.timezone') ?: 'Asia/Taipei' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-500">語言：</span>
                                            <span class="text-sm text-gray-900">{{ $this->getSettingValue('app.locale') ?: 'zh_TW' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- 影響分析 --}}
                        @if(!empty($impactAnalysis))
                            <div class="mt-8 space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">設定變更影響分析</h3>
                                    <p class="mt-1 text-sm text-gray-500">分析設定變更對系統的潛在影響</p>
                                </div>

                                @foreach($impactAnalysis as $settingKey => $impacts)
                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <h4 class="text-base font-medium text-gray-900">{{ $settingKey }}</h4>
                                        <div class="mt-3 space-y-3">
                                            @foreach($impacts as $impact)
                                                <div class="flex items-start space-x-3">
                                                    <div class="flex-shrink-0">
                                                        @if($impact['severity'] === 'high')
                                                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100">
                                                                <svg class="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        @elseif($impact['severity'] === 'medium')
                                                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-100">
                                                                <svg class="h-4 w-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        @else
                                                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                                                                <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1">
                                                        <h5 class="text-sm font-medium text-gray-900">{{ $impact['title'] }}</h5>
                                                        <p class="text-sm text-gray-600">{{ $impact['description'] }}</p>
                                                        @if(isset($impact['details']) && !empty($impact['details']))
                                                            <ul class="mt-1 text-xs text-gray-500">
                                                                @foreach($impact['details'] as $detail)
                                                                    <li>• {{ is_array($detail) ? $detail['name'] : $detail }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                {{-- 高影響警告 --}}
                                @if($this->hasHighImpactChanges())
                                    <div class="rounded-md bg-red-50 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-red-800">
                                                    高影響設定變更警告
                                                </h3>
                                                <div class="mt-2 text-sm text-red-700">
                                                    <p>您的設定變更包含高影響項目，可能會顯著影響系統運行。請確認變更內容後再儲存。</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>