@props([
    'wire:model' => null,
    'id' => null,
    'required' => false,
    'error' => false,
    'language' => 'css',
    'height' => '200px',
    'placeholder' => ''
])

<div x-data="{
    code: @entangle($attributes->whereStartsWith('wire:')->first()).defer,
    lineNumbers: true,
    
    init() {
        this.$nextTick(() => {
            this.updateLineNumbers();
        });
    },
    
    updateLineNumbers() {
        if (this.lineNumbers) {
            const textarea = this.$refs.codeTextarea;
            const lineNumbersDiv = this.$refs.lineNumbers;
            const lines = (textarea.value || '').split('\n').length;
            
            lineNumbersDiv.innerHTML = Array.from({length: lines}, (_, i) => i + 1).join('\n');
        }
    },
    
    handleScroll() {
        if (this.lineNumbers) {
            this.$refs.lineNumbers.scrollTop = this.$refs.codeTextarea.scrollTop;
        }
    },
    
    insertTab(event) {
        if (event.key === 'Tab') {
            event.preventDefault();
            const textarea = event.target;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            
            textarea.value = textarea.value.substring(0, start) + '  ' + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + 2;
            
            this.code = textarea.value;
            this.updateLineNumbers();
        }
    }
}" class="relative">
    
    {{-- 工具列 --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center space-x-2">
            <span class="text-xs font-medium text-gray-700 uppercase">{{ $language }}</span>
            
            <button type="button"
                    @click="lineNumbers = !lineNumbers; $nextTick(() => updateLineNumbers())"
                    class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <span x-text="lineNumbers ? '隱藏' : '顯示'"></span>
                行號
            </button>
        </div>
        
        <div class="text-xs text-gray-500">
            使用 Tab 鍵縮排
        </div>
    </div>
    
    {{-- 程式碼編輯器 --}}
    <div class="relative border border-gray-300 rounded-md overflow-hidden {{ $error ? 'border-red-300' : '' }}">
        <div class="flex">
            {{-- 行號 --}}
            <div x-show="lineNumbers"
                 x-ref="lineNumbers"
                 class="bg-gray-50 text-gray-400 text-xs font-mono p-2 select-none overflow-hidden"
                 style="width: 3rem; line-height: 1.5; white-space: pre;"
                 @scroll="handleScroll()">1</div>
            
            {{-- 程式碼輸入區 --}}
            <textarea x-ref="codeTextarea"
                      x-model="code"
                      @input="updateLineNumbers()"
                      @scroll="handleScroll()"
                      @keydown="insertTab($event)"
                      {{ $attributes->merge([
                          'class' => 'block w-full font-mono text-sm bg-white resize-none focus:ring-blue-500 focus:border-blue-500 border-0 focus:ring-0'
                      ]) }}
                      @if($id) id="{{ $id }}" @endif
                      @if($required) required @endif
                      @if($placeholder) placeholder="{{ $placeholder }}" @endif
                      :style="{ height: '{{ $height }}' }"
                      style="line-height: 1.5; tab-size: 2;"
                      spellcheck="false"></textarea>
        </div>
    </div>
    
    {{-- 說明文字 --}}
    <p class="mt-2 text-xs text-gray-500">
        @switch($language)
            @case('css')
                輸入自訂 CSS 樣式。這些樣式將套用到所有頁面。
                @break
            @case('javascript')
                輸入自訂 JavaScript 程式碼。請確保程式碼的安全性。
                @break
            @case('html')
                輸入 HTML 程式碼。請注意 XSS 安全性問題。
                @break
            @default
                輸入 {{ $language }} 程式碼。
        @endswitch
    </p>
</div>