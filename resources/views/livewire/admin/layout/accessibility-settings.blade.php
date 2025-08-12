<div class="accessibility-settings" 
     x-data="{ isOpen: @entangle('isOpen') }"
     x-show="isOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95"
     @keydown.escape="$wire.close()"
     role="dialog"
     aria-labelledby="accessibility-settings-title"
     aria-describedby="accessibility-settings-description">

    <!-- 背景遮罩 -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-40" 
         @click="$wire.close()"
         aria-hidden="true"></div>

    <!-- 設定面板 -->
    <div class="fixed inset-y-0 right-0 w-96 bg-white dark:bg-gray-800 shadow-xl z-50 overflow-y-auto"
         tabindex="-1">
        
        <!-- 標題列 -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 id="accessibility-settings-title" 
                class="text-lg font-semibold text-gray-900 dark:text-white">
                無障礙設定
            </h2>
            <button type="button" 
                    @click="$wire.close()"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="關閉無障礙設定">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- 設定內容 -->
        <div class="p-4 space-y-6">
            <p id="accessibility-settings-description" class="text-sm text-gray-600 dark:text-gray-400">
                調整這些設定以改善您的使用體驗
            </p>

            <!-- 視覺設定 -->
            <div class="space-y-4">
                <h3 class="text-md font-medium text-gray-900 dark:text-white">視覺設定</h3>
                
                <!-- 高對比模式 -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="high-contrast" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            高對比模式
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            增強文字和背景的對比度
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="high-contrast"
                               wire:model.live="preferences.high_contrast"
                               wire:change="updatePreference('high_contrast', $event.target.checked)"
                               class="sr-only peer"
                               aria-describedby="high-contrast-description">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="sr-only">切換高對比模式</span>
                    </label>
                </div>

                <!-- 大字體模式 -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="large-text" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            大字體模式
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            增大文字大小以提高可讀性
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="large-text"
                               wire:model.live="preferences.large_text"
                               wire:change="updatePreference('large_text', $event.target.checked)"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="sr-only">切換大字體模式</span>
                    </label>
                </div>

                <!-- 減少動畫 -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="reduced-motion" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            減少動畫效果
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            減少或停用動畫和過渡效果
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="reduced-motion"
                               wire:model.live="preferences.reduced_motion"
                               wire:change="updatePreference('reduced_motion', $event.target.checked)"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="sr-only">切換減少動畫模式</span>
                    </label>
                </div>
            </div>

            <!-- 導航設定 -->
            <div class="space-y-4">
                <h3 class="text-md font-medium text-gray-900 dark:text-white">導航設定</h3>
                
                <!-- 鍵盤導航 -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="keyboard-navigation" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            鍵盤導航支援
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            啟用鍵盤快捷鍵和導航功能
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="keyboard-navigation"
                               wire:model.live="preferences.keyboard_navigation"
                               wire:change="updatePreference('keyboard_navigation', $event.target.checked)"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="sr-only">切換鍵盤導航支援</span>
                    </label>
                </div>

                <!-- 跳轉連結 -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="skip-links" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            跳轉連結
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            顯示快速跳轉到頁面區域的連結
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="skip-links"
                               wire:model.live="preferences.skip_links"
                               wire:change="updatePreference('skip_links', $event.target.checked)"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="sr-only">切換跳轉連結</span>
                    </label>
                </div>

                <!-- 增強焦點指示器 -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="focus-indicators" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            增強焦點指示器
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            顯示更明顯的焦點邊框
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="focus-indicators"
                               wire:model.live="preferences.focus_indicators"
                               wire:change="updatePreference('focus_indicators', $event.target.checked)"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="sr-only">切換增強焦點指示器</span>
                    </label>
                </div>
            </div>

            <!-- 螢幕閱讀器設定 -->
            <div class="space-y-4">
                <h3 class="text-md font-medium text-gray-900 dark:text-white">螢幕閱讀器</h3>
                
                <div class="flex items-center justify-between">
                    <div>
                        <label for="screen-reader-support" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            螢幕閱讀器支援
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            啟用螢幕閱讀器優化功能
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="screen-reader-support"
                               wire:model.live="preferences.screen_reader_support"
                               wire:change="updatePreference('screen_reader_support', $event.target.checked)"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="sr-only">切換螢幕閱讀器支援</span>
                    </label>
                </div>
            </div>

            <!-- 鍵盤快捷鍵說明 -->
            <div class="space-y-4">
                <h3 class="text-md font-medium text-gray-900 dark:text-white">鍵盤快捷鍵</h3>
                
                <div class="space-y-3 text-sm">
                    @foreach($this->keyboardShortcuts as $category => $shortcuts)
                        <div>
                            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ match($category) {
                                    'navigation' => '導航',
                                    'general' => '一般',
                                    'menu' => '選單',
                                    default => $category
                                } }}
                            </h4>
                            <dl class="space-y-1">
                                @foreach($shortcuts as $key => $description)
                                    <div class="flex justify-between">
                                        <dt class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                            {{ $key }}
                                        </dt>
                                        <dd class="text-gray-600 dark:text-gray-400 ml-3">
                                            {{ $description }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 底部按鈕 -->
        <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3">
            <button type="button"
                    wire:click="resetToDefaults"
                    class="w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                重設為預設值
            </button>
            
            <button type="button"
                    @click="$wire.close()"
                    class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                完成
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    // 監聽鍵盤事件
    document.addEventListener('keydown', (event) => {
        if (event.altKey && event.key.toLowerCase() === 'a') {
            event.preventDefault();
            @this.call('toggle');
        }
    });
});
</script>