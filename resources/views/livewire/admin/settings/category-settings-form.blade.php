<div>
    <x-admin.settings.settings-form-layout
        :title="$this->categoryInfo['name'] ?? '設定表單'"
        :description="$this->categoryInfo['description'] ?? ''"
        :category="$category"
        :showValidationSummary="true"
        :showFormActions="!$autoSave"
        :autoSave="$autoSave"
        :hasChanges="$this->hasChanges"
        :errors="$validationErrors"
        :saving="$saving">
        
        <form wire:submit.prevent="save" class="space-y-6">
            @if(!empty($this->categorySettings))
                @foreach($this->categorySettings as $settingKey => $config)
                    @php
                        $fieldName = str_replace('.', '_', $settingKey);
                        $fieldErrors = $validationErrors["values.{$settingKey}"] ?? [];
                        $fieldDependencies = $dependencyWarnings[$settingKey] ?? [];
                        $inputType = $config['type'] ?? 'text';
                    @endphp
                    
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        {{-- 設定標題和描述 --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 flex items-center">
                                    {{ $config['description'] ?? $settingKey }}
                                    @if(str_contains($config['validation'] ?? '', 'required'))
                                        <span class="text-red-500 ml-1">*</span>
                                    @endif
                                    
                                    {{-- 敏感資料指示器 --}}
                                    @if($config['encrypted'] ?? false)
                                        <svg class="w-4 h-4 ml-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </h4>
                                
                                @if(isset($config['help']))
                                    <p class="mt-1 text-sm text-gray-600">{{ $config['help'] }}</p>
                                @endif
                            </div>
                            
                            {{-- 重設按鈕 --}}
                            <button type="button"
                                    wire:click="resetSetting('{{ $settingKey }}')"
                                    class="ml-4 inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    title="重設為預設值">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>
                        
                        {{-- 依賴關係警告 --}}
                        @if(!empty($fieldDependencies))
                            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h5 class="text-yellow-800 text-sm font-medium">依賴關係提醒</h5>
                                        <ul class="mt-1 text-yellow-700 text-sm space-y-1">
                                            @foreach($fieldDependencies as $warning)
                                                <li>{{ $warning['message'] ?? $warning }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        {{-- 輸入元件 --}}
                        <div class="space-y-2">
                            @switch($inputType)
                                @case('text')
                                    <x-admin.settings.inputs.text-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :placeholder="$config['placeholder'] ?? ''"
                                        :maxlength="$config['maxlength'] ?? null" />
                                    @break
                                    
                                @case('textarea')
                                    <x-admin.settings.inputs.textarea-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :placeholder="$config['placeholder'] ?? ''"
                                        :rows="$config['rows'] ?? 3"
                                        :maxlength="$config['maxlength'] ?? null" />
                                    @break
                                    
                                @case('number')
                                    <x-admin.settings.inputs.number-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :min="$config['min'] ?? null"
                                        :max="$config['max'] ?? null"
                                        :step="$config['step'] ?? null" />
                                    @break
                                    
                                @case('email')
                                    <x-admin.settings.inputs.email-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :placeholder="$config['placeholder'] ?? ''" />
                                    @break
                                    
                                @case('url')
                                    <x-admin.settings.inputs.url-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :placeholder="$config['placeholder'] ?? ''" />
                                    @break
                                    
                                @case('password')
                                    <x-admin.settings.inputs.password-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :placeholder="$config['encrypted'] ?? false ? '輸入新密碼以變更' : ''"
                                        :showToggle="true" />
                                    @break
                                    
                                @case('boolean')
                                    <x-admin.settings.inputs.toggle-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :label="$config['label'] ?? '啟用此設定'" />
                                    @break
                                    
                                @case('select')
                                    <x-admin.settings.inputs.select-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :options="$config['options'] ?? []"
                                        :placeholder="$config['placeholder'] ?? '請選擇...'" />
                                    @break
                                    
                                @case('multiselect')
                                    <x-admin.settings.inputs.multiselect-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :options="$config['options'] ?? []"
                                        :placeholder="$config['placeholder'] ?? '請選擇...'"
                                        :searchable="$config['searchable'] ?? true" />
                                    @break
                                    
                                @case('color')
                                    <x-admin.settings.inputs.color-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)" />
                                    @break
                                    
                                @case('file')
                                    <x-admin.settings.inputs.file-input
                                        :id="$fieldName"
                                        wire:model="uploadedFiles.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :accept="$config['accept'] ?? null"
                                        :currentFile="$values[$settingKey] ?? null" />
                                    @break
                                    
                                @case('image')
                                    <x-admin.settings.inputs.image-input
                                        :id="$fieldName"
                                        wire:model="uploadedFiles.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :currentImage="$values[$settingKey] ?? null"
                                        :maxSize="$config['maxSize'] ?? '2MB'" />
                                    @break
                                    
                                @case('json')
                                    <x-admin.settings.inputs.json-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :height="$config['height'] ?? '200px'" />
                                    @break
                                    
                                @case('code')
                                    <x-admin.settings.inputs.code-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :language="$config['language'] ?? 'css'"
                                        :height="$config['height'] ?? '200px'"
                                        :placeholder="$config['placeholder'] ?? ''" />
                                    @break
                                    
                                @default
                                    <x-admin.settings.inputs.text-input
                                        :id="$fieldName"
                                        wire:model.live="values.{{ $settingKey }}"
                                        :required="str_contains($config['validation'] ?? '', 'required')"
                                        :error="!empty($fieldErrors)"
                                        :placeholder="$config['placeholder'] ?? ''" />
                            @endswitch
                            
                            {{-- 數值範圍提示 --}}
                            @if($inputType === 'number' && (isset($config['min']) || isset($config['max'])))
                                <p class="text-xs text-gray-500">
                                    範圍：
                                    @if(isset($config['min']) && isset($config['max']))
                                        {{ $config['min'] }} - {{ $config['max'] }}
                                    @elseif(isset($config['min']))
                                        最小值 {{ $config['min'] }}
                                    @elseif(isset($config['max']))
                                        最大值 {{ $config['max'] }}
                                    @endif
                                </p>
                            @endif
                            
                            {{-- 錯誤訊息 --}}
                            @if(!empty($fieldErrors))
                                <div class="text-sm text-red-600 space-y-1">
                                    @foreach($fieldErrors as $error)
                                        <p class="flex items-start">
                                            <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>{{ $error }}</span>
                                        </p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                
                {{-- 連線測試按鈕（針對特定分類） --}}
                @if($category === 'notification')
                    <div class="flex justify-center pt-4">
                        <button type="button"
                                wire:click="testConnection('smtp')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            測試 SMTP 連線
                        </button>
                    </div>
                @endif
                
            @else
                {{-- 沒有設定項目 --}}
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">此分類暫無設定項目</h3>
                    <p class="mt-1 text-sm text-gray-500">設定項目將在系統更新後顯示</p>
                </div>
            @endif
        </form>
    </x-admin.settings.settings-form-layout>
</div>