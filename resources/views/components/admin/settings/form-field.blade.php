@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'required' => false,
    'error' => null,
    'help' => '',
    'value' => null,
    'options' => [],
    'dependencyWarnings' => [],
    'showDependencies' => true,
    'autoSave' => false,
    'autoSaveDelay' => 1000
])

<div class="space-y-2" 
     x-data="{
         autoSaveTimer: null,
         hasChanges: false,
         originalValue: @js($value),
         
         handleInput() {
             this.hasChanges = true;
             
             @if($autoSave)
                 if (this.autoSaveTimer) {
                     clearTimeout(this.autoSaveTimer);
                 }
                 
                 this.autoSaveTimer = setTimeout(() => {
                     $wire.call('autoSave', '{{ $name }}');
                     this.hasChanges = false;
                 }, {{ $autoSaveDelay }});
             @endif
         }
     }">
    
    {{-- 標籤和必填指示器 --}}
    @if($label)
        <div class="flex items-center justify-between">
            <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
                {{ $label }}
                @if($required)
                    <span class="text-red-500 ml-1">*</span>
                @endif
            </label>
            
            {{-- 自動儲存指示器 --}}
            @if($autoSave)
                <div class="flex items-center space-x-2">
                    <div x-show="hasChanges" class="flex items-center text-xs text-yellow-600">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        未儲存
                    </div>
                    
                    <div x-show="!hasChanges && originalValue !== null" class="flex items-center text-xs text-green-600">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        已儲存
                    </div>
                </div>
            @endif
        </div>
    @endif
    
    {{-- 依賴關係警告 --}}
    @if($showDependencies && !empty($dependencyWarnings))
        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-yellow-800 text-sm font-medium">依賴關係提醒</h4>
                    <ul class="mt-1 text-yellow-700 text-sm space-y-1">
                        @foreach($dependencyWarnings as $warning)
                            <li class="flex items-start">
                                <span class="inline-block w-1.5 h-1.5 bg-yellow-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                <span>{{ $warning['message'] ?? $warning }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
    
    {{-- 輸入元件 --}}
    <div @input="handleInput()">
        @switch($type)
            @case('text')
                <x-admin.settings.inputs.text-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :placeholder="$options['placeholder'] ?? ''"
                    :maxlength="$options['maxlength'] ?? null" />
                @break
                
            @case('textarea')
                <x-admin.settings.inputs.textarea-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :placeholder="$options['placeholder'] ?? ''"
                    :rows="$options['rows'] ?? 3"
                    :maxlength="$options['maxlength'] ?? null" />
                @break
                
            @case('number')
                <x-admin.settings.inputs.number-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :min="$options['min'] ?? null"
                    :max="$options['max'] ?? null"
                    :step="$options['step'] ?? null" />
                @break
                
            @case('email')
                <x-admin.settings.inputs.email-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :placeholder="$options['placeholder'] ?? ''" />
                @break
                
            @case('url')
                <x-admin.settings.inputs.url-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :placeholder="$options['placeholder'] ?? ''" />
                @break
                
            @case('password')
                <x-admin.settings.inputs.password-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :placeholder="$options['placeholder'] ?? ''"
                    :showToggle="$options['showToggle'] ?? true" />
                @break
                
            @case('boolean')
                <x-admin.settings.inputs.toggle-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :label="$options['label'] ?? '啟用此設定'" />
                @break
                
            @case('select')
                <x-admin.settings.inputs.select-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :options="$options['options'] ?? []"
                    :placeholder="$options['placeholder'] ?? '請選擇...'" />
                @break
                
            @case('multiselect')
                <x-admin.settings.inputs.multiselect-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :options="$options['options'] ?? []"
                    :placeholder="$options['placeholder'] ?? '請選擇...'"
                    :searchable="$options['searchable'] ?? true" />
                @break
                
            @case('color')
                <x-admin.settings.inputs.color-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)" />
                @break
                
            @case('file')
                <x-admin.settings.inputs.file-input
                    :id="$name"
                    :wire:model="'uploadedFile'"
                    :required="$required"
                    :error="!empty($error)"
                    :accept="$options['accept'] ?? null"
                    :currentFile="$value" />
                @break
                
            @case('image')
                <x-admin.settings.inputs.image-input
                    :id="$name"
                    :wire:model="'uploadedFile'"
                    :required="$required"
                    :error="!empty($error)"
                    :currentImage="$value"
                    :maxSize="$options['maxSize'] ?? '2MB'" />
                @break
                
            @case('json')
                <x-admin.settings.inputs.json-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :height="$options['height'] ?? '200px'" />
                @break
                
            @case('code')
                <x-admin.settings.inputs.code-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :language="$options['language'] ?? 'css'"
                    :height="$options['height'] ?? '200px'"
                    :placeholder="$options['placeholder'] ?? ''" />
                @break
                
            @default
                <x-admin.settings.inputs.text-input
                    :id="$name"
                    :wire:model="'value'"
                    :required="$required"
                    :error="!empty($error)"
                    :placeholder="$options['placeholder'] ?? ''" />
        @endswitch
    </div>
    
    {{-- 說明文字 --}}
    @if($help)
        <p class="text-sm text-gray-500">
            {{ $help }}
        </p>
    @endif
    
    {{-- 數值範圍提示 --}}
    @if($type === 'number' && (isset($options['min']) || isset($options['max'])))
        <p class="text-xs text-gray-500">
            範圍：
            @if(isset($options['min']) && isset($options['max']))
                {{ $options['min'] }} - {{ $options['max'] }}
            @elseif(isset($options['min']))
                最小值 {{ $options['min'] }}
            @elseif(isset($options['max']))
                最大值 {{ $options['max'] }}
            @endif
        </p>
    @endif
    
    {{-- 錯誤訊息 --}}
    @if($error)
        <div class="text-sm text-red-600 space-y-1">
            @if(is_array($error))
                @foreach($error as $errorMessage)
                    <p class="flex items-start">
                        <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ $errorMessage }}</span>
                    </p>
                @endforeach
            @else
                <p class="flex items-start">
                    <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ $error }}</span>
                </p>
            @endif
        </div>
    @endif
</div>