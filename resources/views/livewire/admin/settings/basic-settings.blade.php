<div class="space-y-6">
    {{-- 頁面標頭 --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">基本設定</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理應用程式的基本資訊、時區、語言和日期時間格式設定
            </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            {{-- 變更狀態指示 --}}
            @if($this->hasChanges)
                <span class="badge badge-warning">
                    <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1" />
                    有未儲存的變更
                </span>
            @endif
            
            {{-- 預覽按鈕 --}}
            @if(!$showPreview && $this->hasChanges)
                <button 
                    wire:click="startPreview"
                    class="btn btn-outline btn-sm"
                    title="預覽變更"
                >
                    <x-heroicon-o-eye class="w-4 h-4" />
                    預覽
                </button>
            @endif
            
            {{-- 重設按鈕 --}}
            <button 
                wire:click="resetAll"
                class="btn btn-ghost btn-sm text-orange-600 hover:text-orange-700"
                title="重設所有設定為預設值"
                onclick="return confirm('確定要重設所有基本設定為預設值嗎？此操作無法復原。')"
            >
                <x-heroicon-o-arrow-path class="w-4 h-4" />
                重設全部
            </button>
        </div>
    </div>

    {{-- 預覽模式提示 --}}
    @if($showPreview)
        <div class="alert alert-info">
            <x-heroicon-o-information-circle class="w-5 h-5" />
            <div>
                <h3 class="font-medium">預覽模式</h3>
                <p class="text-sm mt-1">您正在預覽設定變更，變更尚未儲存。</p>
            </div>
            <div class="flex gap-2">
                <button 
                    wire:click="applyPreview"
                    class="btn btn-success btn-sm"
                >
                    套用變更
                </button>
                <button 
                    wire:click="stopPreview"
                    class="btn btn-ghost btn-sm"
                >
                    取消預覽
                </button>
            </div>
        </div>
    @endif

    {{-- 設定表單 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <form wire:submit="save" class="divide-y divide-gray-200 dark:divide-gray-700">
            
            {{-- 應用程式資訊設定 --}}
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-information-circle class="w-5 h-5 inline mr-2 text-blue-600" />
                    應用程式資訊
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- 應用程式名稱 --}}
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $this->getSettingDisplayName('app.name') }}
                            @if($this->isRequired('app.name'))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input 
                            type="text" 
                            id="app_name"
                            wire:model.live.debounce.500ms="settings.app.name"
                            class="input input-bordered w-full @error('settings.app.name') input-error @enderror"
                            placeholder="請輸入應用程式名稱"
                        />
                        @if($this->getValidationError('app.name'))
                            <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('app.name') }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->getSettingHelp('app.name') }}
                        </p>
                    </div>

                    {{-- 應用程式描述 --}}
                    <div class="lg:col-span-2">
                        <label for="app_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $this->getSettingDisplayName('app.description') }}
                        </label>
                        <textarea 
                            id="app_description"
                            wire:model.live.debounce.500ms="settings.app.description"
                            rows="3"
                            class="textarea textarea-bordered w-full @error('settings.app.description') textarea-error @enderror"
                            placeholder="請輸入應用程式描述"
                        ></textarea>
                        @if($this->getValidationError('app.description'))
                            <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('app.description') }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->getSettingHelp('app.description') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- 地區和語言設定 --}}
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-globe-alt class="w-5 h-5 inline mr-2 text-green-600" />
                    地區和語言
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- 系統時區 --}}
                    <div>
                        <label for="app_timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $this->getSettingDisplayName('app.timezone') }}
                            @if($this->isRequired('app.timezone'))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <div class="flex gap-2">
                            <select 
                                id="app_timezone"
                                wire:model.live="settings.app.timezone"
                                class="select select-bordered flex-1 @error('settings.app.timezone') select-error @enderror"
                            >
                                <option value="">請選擇時區</option>
                                @foreach($this->timezoneOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button 
                                type="button"
                                wire:click="testTimezone"
                                class="btn btn-outline btn-sm"
                                title="測試時區"
                            >
                                <x-heroicon-o-clock class="w-4 h-4" />
                            </button>
                        </div>
                        @if($this->getValidationError('app.timezone'))
                            <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('app.timezone') }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->getSettingHelp('app.timezone') }}
                        </p>
                    </div>

                    {{-- 預設語言 --}}
                    <div>
                        <label for="app_locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $this->getSettingDisplayName('app.locale') }}
                            @if($this->isRequired('app.locale'))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <select 
                            id="app_locale"
                            wire:model.live="settings.app.locale"
                            class="select select-bordered w-full @error('settings.app.locale') select-error @enderror"
                        >
                            <option value="">請選擇語言</option>
                            @foreach($this->localeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($this->getValidationError('app.locale'))
                            <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('app.locale') }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->getSettingHelp('app.locale') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- 日期時間格式設定 --}}
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-calendar-days class="w-5 h-5 inline mr-2 text-purple-600" />
                    日期時間格式
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- 日期格式 --}}
                    <div>
                        <label for="app_date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $this->getSettingDisplayName('app.date_format') }}
                            @if($this->isRequired('app.date_format'))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <select 
                            id="app_date_format"
                            wire:model.live="settings.app.date_format"
                            class="select select-bordered w-full @error('settings.app.date_format') select-error @enderror"
                        >
                            <option value="">請選擇日期格式</option>
                            @foreach($this->dateFormatOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($this->getValidationError('app.date_format'))
                            <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('app.date_format') }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->getSettingHelp('app.date_format') }}
                        </p>
                    </div>

                    {{-- 時間格式 --}}
                    <div>
                        <label for="app_time_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $this->getSettingDisplayName('app.time_format') }}
                            @if($this->isRequired('app.time_format'))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <select 
                            id="app_time_format"
                            wire:model.live="settings.app.time_format"
                            class="select select-bordered w-full @error('settings.app.time_format') select-error @enderror"
                        >
                            <option value="">請選擇時間格式</option>
                            @foreach($this->timeFormatOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($this->getValidationError('app.time_format'))
                            <p class="mt-1 text-sm text-red-600">{{ $this->getValidationError('app.time_format') }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->getSettingHelp('app.time_format') }}
                        </p>
                    </div>
                </div>

                {{-- 格式預覽 --}}
                @if(isset($settings['app.date_format']) && isset($settings['app.time_format']))
                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">格式預覽</h4>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <div>
                                <span class="font-medium">日期：</span>
                                <span class="font-mono">{{ now()->format($settings['app.date_format'] ?? 'Y-m-d') }}</span>
                            </div>
                            <div>
                                <span class="font-medium">時間：</span>
                                <span class="font-mono">{{ now()->format($settings['app.time_format'] ?? 'H:i') }}</span>
                            </div>
                            <div>
                                <span class="font-medium">完整：</span>
                                <span class="font-mono">
                                    {{ now()->format(($settings['app.date_format'] ?? 'Y-m-d') . ' ' . ($settings['app.time_format'] ?? 'H:i')) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 變更摘要 --}}
            @if($this->hasChanges)
                <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-3">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline mr-1" />
                        待儲存的變更
                    </h3>
                    <div class="space-y-2">
                        @foreach($this->changedSettings as $key => $change)
                            <div class="text-xs text-yellow-700 dark:text-yellow-300">
                                <span class="font-medium">{{ $this->getSettingDisplayName($key) }}：</span>
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
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline mr-1" />
                            有 {{ count($this->changedSettings) }} 項設定待儲存
                        </span>
                    @else
                        <span class="text-green-600 dark:text-green-400">
                            <x-heroicon-o-check-circle class="w-4 h-4 inline mr-1" />
                            所有設定已儲存
                        </span>
                    @endif
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="button"
                        wire:click="loadSettings"
                        class="btn btn-ghost"
                        :disabled="!$wire.hasChanges"
                    >
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                        重新載入
                    </button>
                    
                    <button 
                        type="submit"
                        class="btn btn-primary"
                        :disabled="!$wire.hasChanges || $wire.saving"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">
                            <x-heroicon-o-check class="w-4 h-4" />
                            儲存設定
                        </span>
                        <span wire:loading wire:target="save">
                            <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" />
                            儲存中...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- 設定說明 --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
            <x-heroicon-o-information-circle class="w-4 h-4 inline mr-1" />
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