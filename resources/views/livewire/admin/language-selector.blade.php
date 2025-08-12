{{-- 語言選擇器元件 --}}
<div class="relative inline-block text-left" x-data="{ open: false }">
    {{-- 語言選擇按鈕 --}}
    <button 
        @click="open = !open"
        @click.away="open = false"
        type="button" 
        class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200"
        title="切換語言"
    >
        {{-- 語言圖示 --}}
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
        </svg>
        
        {{-- 目前語言顯示 --}}
        <span>{{ $supportedLocales[$currentLocale] ?? $currentLocale }}</span>
        
        {{-- 下拉箭頭 --}}
        <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </button>

    {{-- 下拉選單 --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 w-48 mt-2 origin-top-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg"
        style="display: none;"
    >
        <div class="py-1">
            {{-- 語言選項標題 --}}
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                選擇語言
            </div>
            
            {{-- 語言選項列表 --}}
            @foreach($supportedLocales as $locale => $name)
                <button 
                    wire:click="switchLanguage('{{ $locale }}')"
                    @click="open = false"
                    class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $currentLocale === $locale ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : '' }}"
                >
                    {{-- 選中狀態指示器 --}}
                    @if($currentLocale === $locale)
                        <svg class="w-4 h-4 mr-3 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <div class="w-4 h-4 mr-3"></div>
                    @endif
                    
                    {{-- 語言名稱 --}}
                    <span class="flex-1 text-left">{{ $name }}</span>
                    
                    {{-- 語言代碼 --}}
                    <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">{{ strtoupper($locale) }}</span>
                </button>
            @endforeach
        </div>
    </div>
    
    {{-- 載入狀態指示器 --}}
    <div wire:loading wire:target="switchLanguage" class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-800 bg-opacity-75 rounded-md">
        <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('language-changed', (event) => {
        // 短暫延遲後重新載入頁面，讓使用者看到載入動畫
        setTimeout(() => {
            window.location.reload();
        }, 500);
    });
});
</script>
