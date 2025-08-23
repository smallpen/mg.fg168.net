@props([
    'wire:model' => null,
    'id' => null,
    'required' => false,
    'error' => false,
    'height' => '200px'
])

<div x-data="{
    jsonValue: @entangle($attributes->whereStartsWith('wire:')->first()).defer,
    jsonString: '',
    isValid: true,
    errorMessage: '',
    
    init() {
        this.updateJsonString();
        this.$watch('jsonValue', () => this.updateJsonString());
    },
    
    updateJsonString() {
        try {
            this.jsonString = typeof this.jsonValue === 'string' 
                ? this.jsonValue 
                : JSON.stringify(this.jsonValue, null, 2);
            this.isValid = true;
            this.errorMessage = '';
        } catch (e) {
            this.isValid = false;
            this.errorMessage = e.message;
        }
    },
    
    updateJsonValue() {
        try {
            const parsed = JSON.parse(this.jsonString);
            this.jsonValue = parsed;
            this.isValid = true;
            this.errorMessage = '';
        } catch (e) {
            this.isValid = false;
            this.errorMessage = 'JSON 格式錯誤: ' + e.message;
        }
    },
    
    formatJson() {
        try {
            const parsed = JSON.parse(this.jsonString);
            this.jsonString = JSON.stringify(parsed, null, 2);
            this.jsonValue = parsed;
            this.isValid = true;
            this.errorMessage = '';
        } catch (e) {
            this.isValid = false;
            this.errorMessage = 'JSON 格式錯誤: ' + e.message;
        }
    }
}" class="space-y-2">
    
    {{-- 工具列 --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <button type="button"
                    @click="formatJson()"
                    class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                格式化
            </button>
            
            <span class="text-xs text-gray-500">JSON 編輯器</span>
        </div>
        
        {{-- 驗證狀態 --}}
        <div class="flex items-center space-x-2">
            <div x-show="isValid" class="flex items-center text-green-600">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs">有效</span>
            </div>
            
            <div x-show="!isValid" class="flex items-center text-red-600">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs">無效</span>
            </div>
        </div>
    </div>
    
    {{-- JSON 編輯器 --}}
    <textarea x-model="jsonString"
              @input="updateJsonValue()"
              {{ $attributes->merge([
                  'class' => 'block w-full font-mono text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500' . ($error || !$isValid ? ' border-red-300' : '')
              ]) }}
              @if($id) id="{{ $id }}" @endif
              @if($required) required @endif
              :style="{ height: '{{ $height }}' }"
              placeholder='{"key": "value"}'
              spellcheck="false"></textarea>
    
    {{-- 錯誤訊息 --}}
    <div x-show="!isValid && errorMessage" class="text-sm text-red-600" x-text="errorMessage"></div>
    
    {{-- 說明文字 --}}
    <p class="text-xs text-gray-500">
        輸入有效的 JSON 格式資料。使用「格式化」按鈕可以自動整理格式。
    </p>
</div>