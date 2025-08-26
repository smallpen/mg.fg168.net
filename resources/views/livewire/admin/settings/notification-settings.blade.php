<div class="space-y-8">
    {{-- 頂部控制區域 --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-4">
            @if($this->hasChanges)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ count($this->changedSettings) }} 個設定已變更
                </span>
            @endif
        </div>
        
        <div class="flex items-center space-x-3">
            <button type="button" 
                    wire:click="openTemplateManager"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                管理範本
            </button>
        </div>
    </div>

    {{-- 郵件通知總開關 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">郵件通知</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        啟用或停用系統郵件通知功能
                    </p>
                </div>
                
                <div class="ml-6">
                    <button type="button" 
                            wire:click="$toggle('settings.email_enabled')"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $this->isEmailEnabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                            role="switch" 
                            aria-checked="{{ $this->isEmailEnabled ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $this->isEmailEnabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SMTP 伺服器設定 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 {{ !$this->isEmailEnabled ? 'opacity-50' : '' }}">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SMTP 伺服器設定</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">配置郵件伺服器連線參數</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    {{-- SMTP 狀態指示器 --}}
                    <div class="flex items-center">
                        <div class="w-2 h-2 rounded-full mr-2 {{ $this->smtpConfigStatus['status'] === 'configured' ? 'bg-green-400' : ($this->smtpConfigStatus['status'] === 'incomplete' ? 'bg-yellow-400' : 'bg-gray-400') }}"></div>
                        <span class="text-sm {{ $this->smtpConfigStatus['color'] }}">{{ $this->smtpConfigStatus['message'] }}</span>
                    </div>
                    
                    {{-- 測試連線按鈕 --}}
                    @if($this->isEmailEnabled)
                        <button type="button" 
                                wire:click="testSmtpConnection"
                                wire:loading.attr="disabled"
                                wire:target="testSmtpConnection"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                            <span wire:loading.remove wire:target="testSmtpConnection">測試連線</span>
                            <span wire:loading wire:target="testSmtpConnection">測試中...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="px-6 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- SMTP 主機 --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        SMTP 主機 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model.live="settings.smtp_host"
                           {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                           placeholder="smtp.gmail.com">
                    @if($this->getValidationError('smtp_host'))
                        <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('smtp_host') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">SMTP 伺服器的主機名稱或 IP 位址</p>
                </div>

                {{-- SMTP 埠號 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        SMTP 埠號 <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           wire:model.live="settings.smtp_port"
                           {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                           min="1" max="65535"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                           placeholder="587">
                    @if($this->getValidationError('smtp_port'))
                        <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('smtp_port') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">通常為 25、465 或 587</p>
                </div>

                {{-- SMTP 加密 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        加密方式
                    </label>
                    <select wire:model.live="settings.smtp_encryption"
                            {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        @foreach($this->encryptionOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">建議使用 TLS 加密</p>
                </div>

                {{-- SMTP 使用者名稱 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        使用者名稱
                    </label>
                    <input type="text" 
                           wire:model.live="settings.smtp_username"
                           {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                           placeholder="your-email@gmail.com">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">SMTP 認證使用者名稱</p>
                </div>

                {{-- SMTP 密碼 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        密碼
                    </label>
                    <input type="password" 
                           wire:model.live="settings.smtp_password"
                           {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                           placeholder="••••••••">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">SMTP 認證密碼或應用程式密碼</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 寄件者資訊 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 {{ !$this->isEmailEnabled ? 'opacity-50' : '' }}">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">寄件者資訊</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">設定系統發送郵件時的寄件者資訊</p>
        </div>
        
        <div class="px-6 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 寄件者名稱 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        寄件者名稱 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model.live="settings.from_name"
                           {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                           placeholder="Laravel Admin System">
                    @if($this->getValidationError('from_name'))
                        <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('from_name') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">顯示在郵件中的寄件者名稱</p>
                </div>

                {{-- 寄件者信箱 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        寄件者信箱 <span class="text-red-500">*</span>
                    </label>
                    <input type="email" 
                           wire:model.live="settings.from_email"
                           {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                           placeholder="noreply@example.com">
                    @if($this->getValidationError('from_email'))
                        <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('from_email') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">系統發送郵件使用的信箱地址</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 通知頻率限制 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 {{ !$this->isEmailEnabled ? 'opacity-50' : '' }}">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">通知頻率限制</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">防止垃圾通知和伺服器過載</p>
        </div>
        
        <div class="px-6 py-6">
            <div class="max-w-md">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    每分鐘通知限制 <span class="text-red-500">*</span>
                </label>
                <select wire:model.live="settings.rate_limit_per_minute"
                        {{ !$this->isEmailEnabled ? 'disabled' : '' }}
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                    @foreach($this->rateLimitOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">限制系統每分鐘發送的通知數量，防止垃圾通知</p>
            </div>
        </div>
    </div>

    {{-- 測試郵件 --}}
    @if($this->isEmailEnabled && $this->smtpConfigStatus['status'] === 'configured')
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">測試郵件</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">發送測試郵件驗證 SMTP 設定</p>
            </div>
            
            <div class="px-6 py-6">
                <div class="max-w-lg">
                    <div class="flex space-x-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                測試郵件地址
                            </label>
                            <input type="email" 
                                   wire:model.live="testEmailAddress"
                                   class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="test@example.com">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="button" 
                                    wire:click="sendTestEmail"
                                    wire:loading.attr="disabled"
                                    wire:target="sendTestEmail"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                                <span wire:loading.remove wire:target="sendTestEmail">發送測試</span>
                                <span wire:loading wire:target="sendTestEmail">發送中...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 測試結果 --}}
    @if($showTestResult && !empty($testResult))
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-5">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        @if($testResult['success'])
                            <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium {{ $testResult['success'] ? 'text-green-800 dark:text-green-400' : 'text-red-800 dark:text-red-400' }}">
                            {{ $testResult['message'] }}
                        </h3>
                        @if(isset($testResult['details']) && is_array($testResult['details']) && !empty($testResult['details']))
                            <div class="mt-2 text-sm {{ $testResult['success'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($testResult['details'] as $key => $value)
                                        <li>{{ $key }}：{{ $value }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="ml-3 flex-shrink-0">
                        <button type="button" 
                                wire:click="$set('showTestResult', false)"
                                class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    {{-- 底部操作按鈕 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <button type="button" 
                            wire:click="resetAll"
                            wire:confirm="確定要重設所有通知設定為預設值嗎？"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        重設全部
                    </button>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button type="button" 
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            {{ !$this->hasChanges ? 'disabled' : '' }}
                            class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="save" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg wire:loading wire:target="save" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="save">儲存設定</span>
                        <span wire:loading wire:target="save">儲存中...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 通知範本管理對話框 --}}
    @if($showTemplateManager)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeTemplateManager"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">通知範本管理</h3>
                            <button type="button" 
                                    wire:click="closeTemplateManager"
                                    class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {{-- 範本列表 --}}
                            <div class="lg:col-span-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">範本類型</h4>
                                <div class="space-y-2">
                                    <button type="button"
                                            wire:click="selectTemplate('welcome')"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm {{ $selectedTemplateType === 'welcome' ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-900 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                        歡迎郵件
                                    </button>
                                    <button type="button"
                                            wire:click="selectTemplate('password_reset')"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm {{ $selectedTemplateType === 'password_reset' ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-900 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                        密碼重設
                                    </button>
                                    <button type="button"
                                            wire:click="selectTemplate('account_locked')"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm {{ $selectedTemplateType === 'account_locked' ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-900 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                        帳號鎖定通知
                                    </button>
                                    <button type="button"
                                            wire:click="selectTemplate('maintenance')"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm {{ $selectedTemplateType === 'maintenance' ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-900 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                        系統維護通知
                                    </button>
                                </div>
                            </div>
                            
                            {{-- 範本編輯 --}}
                            <div class="lg:col-span-2">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">編輯範本</h4>
                                        <button type="button"
                                                wire:click="resetTemplate"
                                                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                            重設為預設值
                                        </button>
                                    </div>
                                    
                                    @if(!empty($currentTemplate))
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">範本名稱</label>
                                            <input type="text" 
                                                   wire:model.live="currentTemplate.name"
                                                   class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">郵件主旨</label>
                                            <input type="text" 
                                                   wire:model.live="currentTemplate.subject"
                                                   class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">郵件內容</label>
                                            <textarea rows="8" 
                                                      wire:model.live="currentTemplate.content"
                                                      class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"></textarea>
                                        </div>
                                        
                                        @if(isset($currentTemplate['variables']) && !empty($currentTemplate['variables']))
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">可用變數</label>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($currentTemplate['variables'] as $variable)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                            {{{ $variable }}}
                                                        </span>
                                                    @endforeach
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">在範本中使用 {變數名稱} 格式來插入動態內容</p>
                                            </div>
                                        @endif
                                        
                                        <div class="flex justify-end space-x-3">
                                            <button type="button"
                                                    wire:click="previewTemplate"
                                                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                預覽
                                            </button>
                                            <button type="button"
                                                    wire:click="saveTemplate"
                                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                儲存範本
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center py-8">
                                            <p class="text-gray-500 dark:text-gray-400">請選擇一個範本類型開始編輯</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
