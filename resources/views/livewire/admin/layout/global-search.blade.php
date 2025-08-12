<div class="relative" x-data="globalSearch" x-init="init()">
    <!-- 搜尋觸發按鈕 -->
    <button 
        type="button"
        @click="$wire.open()"
        class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <span class="hidden sm:inline">{{ $searchPlaceholder }}</span>
        <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-xs font-mono text-gray-400 bg-gray-200 rounded dark:bg-gray-600 dark:text-gray-300">
            Ctrl+K
        </kbd>
    </button>

    <!-- 搜尋模態框 -->
    <div 
        x-show="$wire.isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <!-- 背景遮罩 -->
        <div 
            class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.close()"
        ></div>

        <!-- 搜尋面板 -->
        <div class="flex items-start justify-center min-h-screen pt-16 px-4">
            <div 
                class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl dark:bg-gray-800"
                @click.stop
            >
                <!-- 搜尋輸入框 -->
                <div class="flex items-center px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="query"
                        @keydown.arrow-down.prevent="$wire.handleKeydown('ArrowDown')"
                        @keydown.arrow-up.prevent="$wire.handleKeydown('ArrowUp')"
                        @keydown.enter.prevent="$wire.handleKeydown('Enter')"
                        @keydown.escape.prevent="$wire.handleKeydown('Escape')"
                        placeholder="{{ $searchPlaceholder }}"
                        class="flex-1 bg-transparent border-none outline-none text-gray-900 placeholder-gray-500 dark:text-white dark:placeholder-gray-400"
                        x-ref="searchInput"
                    >
                    
                    <button
                        type="button"
                        @click="$wire.close()"
                        class="ml-3 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- 分類篩選 -->
                @if(!empty($categories))
                <div class="flex items-center px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-sm text-gray-500 mr-3">篩選：</span>
                    <div class="flex space-x-2">
                        @foreach($categories as $key => $label)
                        <button
                            type="button"
                            wire:click="setCategory('{{ $key }}')"
                            class="px-3 py-1 text-xs rounded-full transition-colors {{ $selectedCategory === $key ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600' }}"
                        >
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- 搜尋結果 -->
                <div class="max-h-96 overflow-y-auto">
                    @if($hasResults)
                        @foreach($searchResults as $categoryKey => $category)
                        <div class="px-4 py-3">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                {{ $category['title'] }}
                                @if($category['total'] > count($category['items']))
                                <span class="text-gray-500">(顯示 {{ count($category['items']) }} / {{ $category['total'] }})</span>
                                @endif
                            </h3>
                            
                            <div class="space-y-1">
                                @foreach($category['items'] as $index => $item)
                                <button
                                    type="button"
                                    wire:click="selectResult('{{ $item['type'] }}', {{ $item['id'] }}, '{{ $item['url'] ?? '' }}')"
                                    class="w-full flex items-center px-3 py-2 text-left rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $selectedIndex === $loop->parent->index * 10 + $loop->index ? 'bg-blue-50 dark:bg-blue-900' : '' }}"
                                >
                                    <!-- 圖示 -->
                                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 mr-3">
                                        @switch($item['icon'])
                                            @case('user')
                                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                @break
                                            @case('shield-check')
                                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                </svg>
                                                @break
                                            @case('key')
                                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                                </svg>
                                                @break
                                            @case('chart-bar')
                                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                                @break
                                            @default
                                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                        @endswitch
                                    </div>
                                    
                                    <!-- 內容 -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $item['title'] }}
                                        </p>
                                        @if(!empty($item['subtitle']))
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $item['subtitle'] }}
                                        </p>
                                        @endif
                                    </div>
                                    
                                    <!-- 類型標籤 -->
                                    <span class="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded dark:bg-gray-700 dark:text-gray-400">
                                        {{ $categories[$item['type']] ?? $item['type'] }}
                                    </span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    @elseif($showEmptyState)
                        <!-- 空狀態 -->
                        <div class="px-4 py-8 text-center">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                {{ $emptyStateMessage }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                請嘗試使用不同的關鍵字或檢查拼寫
                            </p>
                        </div>
                    @else
                        <!-- 預設狀態：顯示建議和歷史 -->
                        <div class="px-4 py-3">
                            @if(!empty($suggestions))
                            <div class="mb-4">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                    {{ empty($query) ? '熱門搜尋' : '搜尋建議' }}
                                </h3>
                                <div class="space-y-1">
                                    @foreach($suggestions as $suggestion)
                                    <button
                                        type="button"
                                        wire:click="selectSuggestion('{{ $suggestion['text'] }}')"
                                        class="w-full flex items-center px-3 py-2 text-left rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center mr-3">
                                            @if($suggestion['type'] === 'popular')
                                                <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $suggestion['text'] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if(!empty($searchHistory))
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        最近搜尋
                                    </h3>
                                    <button
                                        type="button"
                                        wire:click="clearSearchHistory"
                                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                    >
                                        清除
                                    </button>
                                </div>
                                <div class="space-y-1">
                                    @foreach($searchHistory as $historyItem)
                                    <button
                                        type="button"
                                        wire:click="selectSuggestion('{{ $historyItem }}')"
                                        class="w-full flex items-center px-3 py-2 text-left rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center mr-3">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $historyItem }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- 底部提示 -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex items-center space-x-4">
                            <span class="flex items-center">
                                <kbd class="px-1.5 py-0.5 bg-gray-100 rounded dark:bg-gray-700">↑↓</kbd>
                                <span class="ml-1">導航</span>
                            </span>
                            <span class="flex items-center">
                                <kbd class="px-1.5 py-0.5 bg-gray-100 rounded dark:bg-gray-700">Enter</kbd>
                                <span class="ml-1">選擇</span>
                            </span>
                            <span class="flex items-center">
                                <kbd class="px-1.5 py-0.5 bg-gray-100 rounded dark:bg-gray-700">Esc</kbd>
                                <span class="ml-1">關閉</span>
                            </span>
                        </div>
                        @if($totalResults > 0)
                        <span>共 {{ $totalResults }} 個結果</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('globalSearch', () => ({
    init() {
        // 監聽全域鍵盤快捷鍵
        document.addEventListener('keydown', (e) => {
            // Ctrl+K 或 Cmd+K 開啟搜尋
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.$wire.toggle();
            }
            
            // Ctrl+Shift+F 或 Cmd+Shift+F 開啟搜尋
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'F') {
                e.preventDefault();
                this.$wire.open();
            }
        });
        
        // 監聽搜尋開啟事件
        this.$wire.on('search-opened', () => {
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        });
    }
}));
</script>
@endscript