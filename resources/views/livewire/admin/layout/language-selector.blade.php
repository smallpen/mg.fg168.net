<div class="relative" x-data="{ open: @entangle('isOpen') }" @click.away="open = false">
    <!-- 語言選擇按鈕 -->
    <button 
        type="button"
        @click="open = !open"
        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors duration-200"
        :class="{ 'bg-gray-100 dark:bg-gray-700': open }"
        aria-expanded="false"
        aria-haspopup="true"
    >
        <!-- 當前語言旗幟 -->
        <span class="text-lg">{{ $this->currentLanguage['flag'] }}</span>
        
        <!-- 當前語言名稱 -->
        <span class="hidden sm:block">{{ $this->currentLanguage['name'] }}</span>
        
        <!-- 下拉箭頭 -->
        <svg 
            class="w-4 h-4 transition-transform duration-200" 
            :class="{ 'rotate-180': open }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- 下拉選單 -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        role="menu"
        aria-orientation="vertical"
        style="display: none;"
    >
        <div class="py-1" role="none">
            <!-- 當前語言（已選中狀態） -->
            <div class="flex items-center px-4 py-2 text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700">
                <span class="mr-3 text-lg">{{ $this->currentLanguage['flag'] }}</span>
                <span class="flex-1">{{ $this->currentLanguage['name'] }}</span>
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </div>
            
            <!-- 分隔線 -->
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            
            <!-- 其他語言選項 -->
            @foreach($this->languageOptions as $code => $language)
                <button
                    type="button"
                    wire:click="switchLanguage('{{ $code }}')"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors duration-150"
                    role="menuitem"
                >
                    <span class="mr-3 text-lg">{{ $language['flag'] }}</span>
                    <span>{{ $language['name'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- 載入狀態指示器 -->
    <div wire:loading wire:target="switchLanguage" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 dark:bg-gray-800 dark:bg-opacity-75 rounded-md">
        <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
</div>