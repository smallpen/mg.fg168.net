<div>
    <!-- 對話框遮罩 -->
    <div 
        x-show="$wire.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 z-50"
        @click="$wire.close()"
        style="display: none;"
    ></div>

    <!-- 對話框內容 -->
    <div 
        x-show="$wire.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <!-- 對話框標題 -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">鍵盤快捷鍵說明</h3>
                            <p class="text-sm text-gray-500">查看所有可用的快捷鍵組合</p>
                        </div>
                    </div>
                    <button 
                        @click="$wire.close()"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- 搜尋和篩選 -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <!-- 搜尋框 -->
                    <div class="flex-1">
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live="searchQuery"
                                placeholder="搜尋快捷鍵或說明..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            @if($searchQuery)
                                <button 
                                    wire:click="clearSearch"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- 分類篩選 -->
                    <div class="sm:w-48">
                        <select 
                            wire:model.live="selectedCategory"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">所有分類 ({{ $this->categoryStats['all'] }})</option>
                            @foreach($this->categories as $key => $name)
                                <option value="{{ $key }}">{{ $name }} ({{ $this->categoryStats[$key] ?? 0 }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 快捷鍵列表 -->
                <div class="max-h-96 overflow-y-auto">
                    @if(empty($this->filteredShortcuts))
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.47-.881-6.08-2.33"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">沒有找到快捷鍵</h3>
                            <p class="mt-1 text-sm text-gray-500">請嘗試調整搜尋條件或分類篩選</p>
                        </div>
                    @else
                        @foreach($this->filteredShortcuts as $category => $shortcuts)
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                    <span class="bg-gray-100 px-2 py-1 rounded text-xs mr-2">
                                        {{ $this->categories[$category] ?? '自訂' }}
                                    </span>
                                    <span class="text-gray-500">({{ count($shortcuts) }} 個)</span>
                                </h4>
                                
                                <div class="space-y-2">
                                    @foreach($shortcuts as $key => $shortcut)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-1">
                                                    <!-- 快捷鍵組合 -->
                                                    <div class="flex items-center space-x-1">
                                                        @foreach(explode('+', $this->formatShortcutKey($key)) as $keyPart)
                                                            <kbd class="px-2 py-1 text-xs font-mono bg-white border border-gray-300 rounded shadow-sm">
                                                                {{ trim($keyPart) }}
                                                            </kbd>
                                                            @if(!$loop->last)
                                                                <span class="text-gray-400">+</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <p class="text-sm text-gray-600">{{ $shortcut['description'] ?? '無說明' }}</p>
                                            </div>
                                            
                                            <div class="flex items-center space-x-2 ml-4">
                                                <!-- 複製按鈕 -->
                                                <button 
                                                    wire:click="copyShortcut('{{ $key }}')"
                                                    class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                                                    title="複製快捷鍵"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                                
                                                <!-- 測試按鈕 -->
                                                <button 
                                                    wire:click="testShortcut('{{ $key }}')"
                                                    class="p-1 text-gray-400 hover:text-blue-600 transition-colors"
                                                    title="測試快捷鍵"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a1.5 1.5 0 011.5 1.5v1a1.5 1.5 0 01-1.5 1.5H9m0-5V9a1.5 1.5 0 011.5-1.5H12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- 對話框底部 -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        <p>提示：按 <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">Ctrl + Shift + H</kbd> 可隨時開啟此說明</p>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button 
                            @click="$wire.dispatch('open-shortcut-settings')"
                            class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors"
                        >
                            自訂快捷鍵
                        </button>
                        <button 
                            @click="$wire.close()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>