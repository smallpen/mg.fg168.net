<div class="p-6 space-y-6">
    {{-- 控制項區域 --}}
    <div class="flex justify-end">
        <div class="flex items-center space-x-3">
            {{-- 變更狀態指示 --}}
            @if($this->hasChanges)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    有未儲存的變更
                </span>
            @endif
            
            {{-- 重設按鈕 --}}
            <button 
                wire:click="resetAll"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-orange-600 dark:text-orange-400 bg-white dark:bg-gray-700 hover:bg-orange-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                title="重設所有設定"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                重設
            </button>
        </div>
    </div>

    {{-- 設定表單 --}}
    <form wire:submit="save" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
        
        {{-- 應用程式資訊設定 --}}
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                <svg class="w-5 h-5 inline mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                應用程式資訊
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 應用程式名稱 --}}
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        應用程式名稱
                        <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="app_name"
                        wire:model.live="settings.app.name"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                        placeholder="請輸入應用程式名稱"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        顯示在瀏覽器標題和系統標頭的應用程式名稱
                    </p>
                </div>

                {{-- 應用程式描述 --}}
                <div class="lg:col-span-2">
                    <label for="app_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        應用程式描述
                    </label>
                    <textarea 
                        id="app_description"
                        wire:model.live="settings.app.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                        placeholder="請輸入應用程式描述"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        應用程式的簡短描述，用於 SEO 和系統說明
                    </p>
                </div>
            </div>
        </div>

        {{-- 地區和語言設定 --}}
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                <svg class="w-5 h-5 inline mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                地區和語言
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 系統時區 --}}
                <div>
                    <label for="app_timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        系統時區
                        <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="app_timezone"
                        wire:model.live="settings.app.timezone"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">請選擇時區</option>
                        @foreach($this->timezoneOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        影響所有時間顯示和記錄，變更後會立即生效
                    </p>
                </div>

                {{-- 預設語言 --}}
                <div>
                    <label for="app_locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        預設語言
                        <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="app_locale"
                        wire:model.live="settings.app.locale"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">請選擇語言</option>
                        @foreach($this->localeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        新使用者的預設語言，現有使用者需手動切換
                    </p>
                </div>
            </div>
        </div>

        {{-- 日期時間格式設定 --}}
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                <svg class="w-5 h-5 inline mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                日期時間格式
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 日期格式 --}}
                <div>
                    <label for="app_date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        日期格式
                        <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="app_date_format"
                        wire:model.live="settings.app.date_format"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">請選擇日期格式</option>
                        @foreach($this->dateFormatOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        影響系統中所有日期的顯示格式
                    </p>
                </div>

                {{-- 時間格式 --}}
                <div>
                    <label for="app_time_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        時間格式
                        <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="app_time_format"
                        wire:model.live="settings.app.time_format"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">請選擇時間格式</option>
                        @foreach($this->timeFormatOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        影響系統中所有時間的顯示格式
                    </p>
                </div>
            </div>
        </div>

        {{-- 變更摘要 --}}
        @if($this->hasChanges)
            <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-3">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    待儲存的變更
                </h3>
                <div class="space-y-2">
                    @foreach($this->changedSettings as $key => $change)
                        <div class="text-xs text-yellow-700 dark:text-yellow-300">
                            <span class="font-medium">
                                @switch($key)
                                    @case('app.name')
                                        應用程式名稱
                                        @break
                                    @case('app.description')
                                        應用程式描述
                                        @break
                                    @case('app.timezone')
                                        系統時區
                                        @break
                                    @case('app.locale')
                                        預設語言
                                        @break
                                    @case('app.date_format')
                                        日期格式
                                        @break
                                    @case('app.time_format')
                                        時間格式
                                        @break
                                    @default
                                        {{ $key }}
                                @endswitch
                                ：
                            </span>
                            <span class="line-through">{{ $change['old'] ?: '(空)' }}</span>
                            <span class="mx-1">→</span>
                            <span class="font-medium">{{ $change['new'] ?: '(空)' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 操作按鈕 --}}
        <div class="p-6 bg-gray-50 dark:bg-gray-700/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                @if($this->hasChanges)
                    <span class="text-yellow-600 dark:text-yellow-400">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        有 {{ count($this->changedSettings) }} 項設定待儲存
                    </span>
                @else
                    <span class="text-green-600 dark:text-green-400">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        所有設定已儲存
                    </span>
                @endif
            </div>
            
            <div class="flex gap-3">
                <button 
                    type="button"
                    wire:click="loadSettings"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    @if(!$this->hasChanges) disabled @endif
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    重新載入
                </button>
                
                <button 
                    type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    @if(!$this->hasChanges) disabled @endif
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        儲存設定
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        儲存中...
                    </span>
                </button>
            </div>
        </div>
    </form>

    {{-- 設定說明 --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            設定說明
        </h3>
        <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <p>• <strong>應用程式名稱</strong>：顯示在瀏覽器標題和系統標頭的名稱</p>
            <p>• <strong>系統時區</strong>：影響所有時間顯示和記錄，變更後會立即生效</p>
            <p>• <strong>預設語言</strong>：新使用者的預設語言，現有使用者需手動切換</p>
            <p>• <strong>日期時間格式</strong>：影響系統中所有日期時間的顯示格式</p>
            <p>• 設定變更會立即生效，無需重新啟動系統</p>
        </div>
    </div>
</div>