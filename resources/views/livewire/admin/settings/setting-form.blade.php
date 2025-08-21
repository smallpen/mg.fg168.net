<div>
    {{-- 設定編輯表單模態對話框 --}}
    <div x-data="{ show: @entangle('showForm') }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        {{-- 背景遮罩 --}}
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                 @click="$wire.closeForm()"></div>

            {{-- 對話框內容 --}}
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                
                @if($this->setting)
                    {{-- 表單標題 --}}
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                編輯設定
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $this->getSettingDisplayName() }}
                            </p>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            {{-- 預覽按鈕 --}}
                            @if($this->supportsPreview)
                                <button type="button"
                                        wire:click="openPreview"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    開啟預覽
                                </button>
                            @endif

                            {{-- 連線測試按鈕 --}}
                            @if($this->supportsConnectionTest)
                                <button type="button"
                                        wire:click="testConnection"
                                        :disabled="$wire.testingConnection"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    @if($testingConnection)
                                        測試中...
                                    @else
                                        測試連線
                                    @endif
                                </button>
                            @endif

                            {{-- 關閉按鈕 --}}
                            <button type="button"
                                    @click="$wire.closeForm()"
                                    class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- 連線測試結果 --}}
                    @if($connectionTestResult !== null)
                        <div class="mb-4 p-3 rounded-md {{ $connectionTestResult ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                            <div class="flex items-center">
                                @if($connectionTestResult)
                                    <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-green-800 text-sm font-medium">{{ $connectionTestMessage }}</span>
                                @else
                                    <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-red-800 text-sm font-medium">{{ $connectionTestMessage }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 依賴關係警告 --}}
                    @if(!empty($dependencyWarnings))
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h4 class="text-yellow-800 text-sm font-medium">依賴關係警告</h4>
                                    <ul class="mt-1 text-yellow-700 text-sm list-disc list-inside">
                                        @foreach($dependencyWarnings as $warning)
                                            <li>{{ $warning['message'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 設定表單 --}}
                    <form wire:submit.prevent="save" class="space-y-6">
                        {{-- 設定值輸入 --}}
                        <div>
                            <label for="setting-value" class="block text-sm font-medium text-gray-700 mb-2">
                                設定值
                                @if($this->isRequired())
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            {{-- 根據設定類型顯示不同的輸入元件 --}}
                            @switch($this->inputType)
                                @case('text')
                                    <input type="text"
                                           id="setting-value"
                                           wire:model.live="value"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror"
                                           placeholder="{{ $this->options['placeholder'] ?? '' }}"
                                           @if(isset($this->options['max_length'])) maxlength="{{ $this->options['max_length'] }}" @endif>
                                    @break

                                @case('textarea')
                                    <textarea id="setting-value"
                                              wire:model.live="value"
                                              rows="{{ $this->options['rows'] ?? 3 }}"
                                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror"
                                              placeholder="{{ $this->options['placeholder'] ?? '' }}"
                                              @if(isset($this->options['max_length'])) maxlength="{{ $this->options['max_length'] }}" @endif></textarea>
                                    @break

                                @case('number')
                                    <input type="number"
                                           id="setting-value"
                                           wire:model.live="value"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror"
                                           @if(isset($this->options['min'])) min="{{ $this->options['min'] }}" @endif
                                           @if(isset($this->options['max'])) max="{{ $this->options['max'] }}" @endif
                                           @if(isset($this->options['step'])) step="{{ $this->options['step'] }}" @endif>
                                    @break

                                @case('email')
                                    <input type="email"
                                           id="setting-value"
                                           wire:model.live="value"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror"
                                           placeholder="{{ $this->options['placeholder'] ?? '' }}">
                                    @break

                                @case('password')
                                    <input type="password"
                                           id="setting-value"
                                           wire:model.live="value"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror"
                                           placeholder="{{ $this->isSensitive() ? '輸入新密碼以變更' : '' }}">
                                    @break

                                @case('boolean')
                                    <div class="flex items-center">
                                        <input type="checkbox"
                                               id="setting-value"
                                               wire:model.live="value"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="setting-value" class="ml-2 block text-sm text-gray-900">
                                            啟用此設定
                                        </label>
                                    </div>
                                    @break

                                @case('select')
                                    <select id="setting-value"
                                            wire:model.live="value"
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror">
                                        @if(!$this->isRequired())
                                            <option value="">請選擇...</option>
                                        @endif
                                        @if(isset($this->settingConfig['options']))
                                            @foreach($this->settingConfig['options'] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @break

                                @case('color')
                                    <div class="flex items-center space-x-3">
                                        <input type="color"
                                               wire:model.live="value"
                                               class="h-10 w-20 border border-gray-300 rounded cursor-pointer">
                                        <input type="text"
                                               id="setting-value"
                                               wire:model.live="value"
                                               class="block flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror"
                                               placeholder="#000000"
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                    @break

                                @case('file')
                                @case('image')
                                    <div class="space-y-3">
                                        @if($value)
                                            <div class="flex items-center space-x-3">
                                                @if($this->inputType === 'image')
                                                    <img src="{{ $value }}" alt="目前圖片" class="h-16 w-16 object-cover rounded-lg border">
                                                @endif
                                                <div>
                                                    <p class="text-sm text-gray-600">目前檔案：</p>
                                                    <a href="{{ $value }}" target="_blank" class="text-blue-600 hover:text-blue-500 text-sm">
                                                        {{ basename($value) }}
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <input type="file"
                                               wire:model="uploadedFile"
                                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                               @if($this->inputType === 'image') accept="image/*" @endif>
                                        
                                        @if($uploadedFile)
                                            <div class="text-sm text-green-600">
                                                已選擇新檔案：{{ $uploadedFile->getClientOriginalName() }}
                                            </div>
                                        @endif
                                    </div>
                                    @break

                                @default
                                    <input type="text"
                                           id="setting-value"
                                           wire:model.live="value"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('value') border-red-300 @enderror">
                            @endswitch

                            {{-- 設定說明 --}}
                            @if($this->getSettingHelp())
                                <p class="mt-2 text-sm text-gray-500">
                                    {{ $this->getSettingHelp() }}
                                </p>
                            @endif

                            {{-- 驗證錯誤訊息 --}}
                            @if(!empty($validationErrors['value']))
                                <div class="mt-2">
                                    @foreach($validationErrors['value'] as $error)
                                        <p class="text-sm text-red-600">{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            {{-- 數值範圍提示 --}}
                            @if($this->inputType === 'number' && (isset($this->options['min']) || isset($this->options['max'])))
                                <p class="mt-1 text-xs text-gray-500">
                                    範圍：
                                    @if(isset($this->options['min']) && isset($this->options['max']))
                                        {{ $this->options['min'] }} - {{ $this->options['max'] }}
                                    @elseif(isset($this->options['min']))
                                        最小值 {{ $this->options['min'] }}
                                    @elseif(isset($this->options['max']))
                                        最大值 {{ $this->options['max'] }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        {{-- 表單按鈕 --}}
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="flex items-center space-x-3">
                                {{-- 重設按鈕 --}}
                                @if($this->canReset)
                                    <button type="button"
                                            wire:click="resetToDefault"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        重設為預設值
                                    </button>
                                @endif

                                {{-- 變更指示器 --}}
                                @if($this->hasChanges)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        已修改
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center space-x-3">
                                {{-- 取消按鈕 --}}
                                <button type="button"
                                        wire:click="cancel"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    取消
                                </button>

                                {{-- 儲存按鈕 --}}
                                <button type="submit"
                                        :disabled="$wire.saving"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    @if($saving)
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        儲存中...
                                    @else
                                        儲存設定
                                    @endif
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>