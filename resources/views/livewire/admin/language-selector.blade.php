{{-- 語言選擇器元件 --}}
<div class="language-selector-wrapper">
    <div class="relative inline-block text-left" 
         x-data="{ 
             open: false, 
             switching: @entangle('isChanging').live,
             showConfirm: @entangle('showConfirmation').live,
             success: @entangle('switchSuccess').live,
             pendingLocale: @entangle('pendingLocale').live,
             init() {
                 // 監聽語言切換事件
                 this.$wire.on('language-switched', (event) => {
                     this.showSuccessAnimation();
                     setTimeout(() => {
                         window.location.reload();
                     }, 1200);
                 });
                 
                 this.$wire.on('language-error', (event) => {
                     this.showErrorMessage(event.message);
                 });
                 
                 this.$wire.on('language-switch-confirmation', (event) => {
                     this.open = false;
                 });
             },
             showSuccessAnimation() {
                 this.success = true;
                 setTimeout(() => {
                     this.success = false;
                 }, 1000);
             },
             showErrorMessage(message) {
                 // 可以整合現有的通知系統
                 console.error('Language switch error:', message);
             }
         }"
         @click.away="open = false">
         
        {{-- 語言選擇按鈕 --}}
        <button 
            @click="open = !open"
            type="button" 
            :disabled="switching"
            data-language-selector
            class="relative inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{
                'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20': success,
                'animate-pulse': switching
            }"
            :title="switching ? '{{ __('admin.common.processing') }}' : '{{ __('admin.language.select') }} ({{ __('admin.language.keyboard_shortcut') }})'"
        >
            {{-- 語言圖示 --}}
            <svg class="w-4 h-4 mr-2 transition-transform duration-200" 
                 :class="{ 'animate-spin': switching }"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
            
            {{-- 目前語言顯示 --}}
            <span class="transition-all duration-200" 
                  :class="{ 'text-green-600 dark:text-green-400': success }">
                {{ $supportedLocales[$currentLocale] ?? $currentLocale }}
            </span>
            
            {{-- 下拉箭頭 --}}
            <svg class="w-4 h-4 ml-2 transition-transform duration-200" 
                 :class="{ 'rotate-180': open }"
                 fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
            
            {{-- 成功指示器 --}}
            <div x-show="success" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-0"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-0"
                 class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </button>

        {{-- 下拉選單 --}}
        <div 
            x-show="open && !switching"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="transform opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="transform opacity-0 scale-95 translate-y-1"
            class="absolute right-0 z-50 w-56 mt-2 origin-top-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl ring-1 ring-black ring-opacity-5"
            style="display: none;"
        >
            <div class="py-2">
                {{-- 語言選項標題 --}}
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-center">
                        <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                        {{ __('admin.language.select') }}
                    </div>
                </div>
                
                {{-- 語言選項列表 --}}
                @foreach($supportedLocales as $locale => $name)
                    <button 
                        wire:click="initiateLanguageSwitch('{{ $locale }}')"
                        @click="open = false"
                        :disabled="switching"
                        class="group flex items-center w-full px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed {{ $currentLocale === $locale ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : '' }}"
                    >
                        {{-- 選中狀態指示器 --}}
                        @if($currentLocale === $locale)
                            <div class="w-5 h-5 mr-3 flex items-center justify-center">
                                <div class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full animate-pulse"></div>
                            </div>
                        @else
                            <div class="w-5 h-5 mr-3 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        @endif
                        
                        {{-- 語言資訊 --}}
                        <div class="flex-1 text-left">
                            <div class="font-medium">{{ $name }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ strtoupper($locale) }}
                                @if($currentLocale === $locale)
                                    <span class="ml-1 text-blue-500">• {{ __('admin.language.current') }}</span>
                                @endif
                            </div>
                        </div>
                        
                        {{-- 切換指示器 --}}
                        @if($currentLocale !== $locale)
                            <div class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        
        {{-- 確認對話框 --}}
        <div x-show="showConfirm" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                
                {{-- 對話框 --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    {{ __('admin.language.confirm_switch_title', ['default' => '確認語言切換']) }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('admin.language.confirm_switch_message', ['default' => '您確定要切換語言嗎？頁面將會重新載入以套用新的語言設定。']) }}
                                    </p>
                                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-300">{{ __('admin.language.from', ['default' => '從']) }}:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $supportedLocales[$currentLocale] ?? $currentLocale }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm mt-1">
                                            <span class="text-gray-600 dark:text-gray-300">{{ __('admin.language.to', ['default' => '到']) }}:</span>
                                            <span class="font-medium text-blue-600 dark:text-blue-400">{{ $supportedLocales[$pendingLocale] ?? $pendingLocale }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="confirmLanguageSwitch" 
                                type="button" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                            {{ __('admin.common.confirm') }}
                        </button>
                        <button wire:click="cancelLanguageSwitch" 
                                type="button" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                            {{ __('admin.common.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 載入狀態覆蓋層 --}}
        <div x-show="switching" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-gray-900 bg-opacity-90"
             style="display: none;">
            <div class="text-center">
                {{-- 載入動畫 --}}
                <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                
                {{-- 載入文字 --}}
                <div class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('admin.language.switching', ['default' => '正在切換語言...']) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.language.please_wait', ['default' => '請稍候，頁面即將重新載入']) }}
                </div>
                
                {{-- 進度條 --}}
                <div class="mt-4 w-64 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%; animation: progress 1.2s ease-in-out infinite;"></div>
                </div>
            </div>
        </div>
    </div>
</div>