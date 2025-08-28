<div class="space-y-6">
    <!-- 訊息顯示區域 -->
    @if($successMessage)
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md dark:bg-green-900/20 dark:border-green-800 dark:text-green-200" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ $successMessage }}</span>
                </div>
                <button wire:click="$set('successMessage', null)" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md dark:bg-red-900/20 dark:border-red-800 dark:text-red-200" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ $errorMessage }}</span>
                </div>
                <button wire:click="$set('errorMessage', null)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- 安全設定表單 -->
    <form wire:submit.prevent="save" class="space-y-6">
        
        <!-- 密碼政策設定 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">密碼政策</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">設定使用者密碼的安全要求</p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 最小長度 -->
                    <div>
                        <label for="password_min_length" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            最小密碼長度
                        </label>
                        <input type="number" 
                               id="password_min_length"
                               wire:model.lazy="settings.security.password_min_length"
                               min="6" 
                               max="32"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <!-- 密碼強度指示器 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            密碼強度等級
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                強
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">密碼政策嚴格，提供高度安全保護</span>
                        </div>
                    </div>
                </div>

                <!-- 密碼要求選項 -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="require_uppercase"
                               wire:model.lazy="settings.security.password_require_uppercase"
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="require_uppercase" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            需要大寫字母
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="require_lowercase"
                               wire:model.lazy="settings.security.password_require_lowercase"
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="require_lowercase" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            需要小寫字母
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="require_numbers"
                               wire:model.lazy="settings.security.password_require_numbers"
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="require_numbers" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            需要數字
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="require_symbols"
                               wire:model.lazy="settings.security.password_require_symbols"
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="require_symbols" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            需要特殊符號
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- 登入安全設定 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">登入安全</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">設定登入失敗鎖定和會話管理</p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- 最大登入嘗試次數 -->
                    <div>
                        <label for="max_attempts" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            最大登入嘗試次數
                        </label>
                        <input type="number" 
                               id="max_attempts"
                               wire:model.lazy="settings.security.login_max_attempts"
                               min="3" 
                               max="10"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <!-- 鎖定持續時間 -->
                    <div>
                        <label for="lockout_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            鎖定持續時間（分鐘）
                        </label>
                        <input type="number" 
                               id="lockout_duration"
                               wire:model.lazy="settings.security.lockout_duration"
                               min="5" 
                               max="1440"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <!-- Session 過期時間 -->
                    <div>
                        <label for="session_lifetime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Session 過期時間（分鐘）
                        </label>
                        <input type="number" 
                               id="session_lifetime"
                               wire:model.lazy="settings.security.session_lifetime"
                               min="30" 
                               max="10080"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作按鈕 -->
        <div class="flex items-center justify-end space-x-3">
            <button type="button" 
                    wire:click="resetAll"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                重設全部
            </button>
            
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                <span wire:loading.remove>儲存設定</span>
                <span wire:loading>儲存中...</span>
            </button>
        </div>
    </form>
</div>