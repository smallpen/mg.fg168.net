<div class="space-y-6">
    <!-- 檢測概覽 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">安全檢測概覽</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        @if($this->scanTimeFormatted)
                            最後檢測時間：{{ $this->scanTimeFormatted }}
                        @else
                            尚未執行安全檢測
                        @endif
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <button wire:click="refreshScan" 
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                        <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>重新檢測</span>
                        <span wire:loading>檢測中...</span>
                    </button>
                    
                    @if($scanResults)
                        <button wire:click="exportReport"
                                class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            匯出報告
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if($scanResults)
            <div class="px-4 py-5 sm:p-6">
                <!-- 安全等級指示器 -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">安全等級</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $scanResults['overall_score'] }}/100</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-{{ $this->securityLevel['color'] }}-600 h-2 rounded-full transition-all duration-300" 
                             style="width: {{ $scanResults['overall_score'] }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->securityLevel['color'] }}-100 text-{{ $this->securityLevel['color'] }}-800 dark:bg-{{ $this->securityLevel['color'] }}-900 dark:text-{{ $this->securityLevel['color'] }}-200">
                            {{ $this->securityLevel['text'] }}
                        </span>
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $this->securityLevel['description'] }}</span>
                    </div>
                </div>

                <!-- 統計資訊 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $scanResults['total_checks'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">總檢查項目</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-600">{{ $this->statusStats['pass'] }}</div>
                        <div class="text-sm text-green-600">通過</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                        <div class="text-2xl font-bold text-yellow-600">{{ $this->statusStats['warning'] }}</div>
                        <div class="text-sm text-yellow-600">警告</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                        <div class="text-2xl font-bold text-red-600">{{ $this->statusStats['fail'] }}</div>
                        <div class="text-sm text-red-600">失敗</div>
                    </div>
                </div>
            </div>
        @else
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">尚未執行安全檢測</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">點擊「重新檢測」按鈕開始安全檢測</p>
                </div>
            </div>
        @endif
    </div>

    @if($scanResults && !empty($this->recommendedFixes))
        <!-- 建議修復項目 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">建議修復項目</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">優先處理以下安全問題</p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="space-y-4">
                    @foreach($this->recommendedFixes as $fix)
                        <div class="flex items-start space-x-3 p-4 rounded-lg {{ $fix['priority'] === 'high' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20' }}">
                            <div class="flex-shrink-0">
                                @if($fix['priority'] === 'high')
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $fix['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                        {{ $fix['priority'] === 'high' ? '高優先級' : '中優先級' }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $fix['category'] }}</span>
                                </div>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $fix['check'] }}</p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $fix['message'] }}</p>
                                <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <span class="text-gray-500 dark:text-gray-400">建議動作：</span>{{ $fix['action'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($scanResults)
        <!-- 篩選和搜尋 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">詳細檢測結果</h3>
                    
                    <!-- 篩選控制項 -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-3 lg:space-y-0 lg:space-x-4">
                        <!-- 左側：搜尋和篩選 -->
                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 flex-1">
                            <!-- 搜尋框 -->
                            <div class="relative flex-1 min-w-0">
                                <input type="text" 
                                       wire:model.live="search"
                                       placeholder="搜尋檢測項目..."
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- 狀態篩選 -->
                            <div class="flex-shrink-0">
                                <select wire:model.live="filterStatus"
                                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md bg-white dark:bg-gray-700 dark:text-white min-w-[120px]">
                                    <option value="all">所有狀態</option>
                                    <option value="pass">通過</option>
                                    <option value="warning">警告</option>
                                    <option value="fail">失敗</option>
                                    <option value="error">錯誤</option>
                                </select>
                            </div>
                        </div>

                        <!-- 右側：操作按鈕 -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- 展開/收合按鈕 -->
                            <div class="flex space-x-1">
                                <button wire:click="expandAll"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    展開全部
                                </button>
                                <button wire:click="collapseAll"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    收合全部
                                </button>
                            </div>

                            <!-- 清除篩選按鈕 -->
                            @if($search || $filterStatus !== 'all')
                                <button wire:click="resetFilters"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    清除
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 檢測結果列表 -->
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->filteredCategories as $categoryKey => $category)
                    <div class="px-4 py-4">
                        <!-- 類別標題 -->
                        <button wire:click="toggleCategory('{{ $categoryKey }}')"
                                class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2 -m-2">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $category['score'] >= 80 ? 'bg-green-100 dark:bg-green-900/20' : ($category['score'] >= 60 ? 'bg-yellow-100 dark:bg-yellow-900/20' : 'bg-red-100 dark:bg-red-900/20') }}">
                                        <span class="text-sm font-medium {{ $category['score'] >= 80 ? 'text-green-600' : ($category['score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $category['score'] }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white">{{ $category['name'] }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($category['checks']) }} 個檢查項目</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <!-- 狀態指示器 -->
                                <div class="flex space-x-1">
                                    @php
                                        $categoryStats = ['pass' => 0, 'warning' => 0, 'fail' => 0, 'error' => 0];
                                        foreach ($category['checks'] as $check) {
                                            $categoryStats[$check['status']]++;
                                        }
                                    @endphp
                                    @if($categoryStats['pass'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ $categoryStats['pass'] }} 通過
                                        </span>
                                    @endif
                                    @if($categoryStats['warning'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            {{ $categoryStats['warning'] }} 警告
                                        </span>
                                    @endif
                                    @if($categoryStats['fail'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            {{ $categoryStats['fail'] }} 失敗
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- 展開/收合圖示 -->
                                <svg class="w-5 h-5 text-gray-400 transform transition-transform {{ $this->isCategoryExpanded($categoryKey) ? 'rotate-90' : '' }}" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </button>

                        <!-- 檢查項目詳細資訊 -->
                        @if($this->isCategoryExpanded($categoryKey))
                            <div class="mt-4 space-y-3">
                                @foreach($category['checks'] as $check)
                                    <div class="ml-11 p-4 rounded-lg border {{ $this->getStatusBgColor($check['status']) }} border-gray-200 dark:border-gray-600">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 {{ $this->getStatusColor($check['status']) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $this->getStatusIcon($check['status']) }}"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white">{{ $check['name'] }}</h5>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $check['status'] === 'pass' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($check['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                                        {{ $check['status'] === 'pass' ? '通過' : ($check['status'] === 'warning' ? '警告' : ($check['status'] === 'fail' ? '失敗' : '錯誤')) }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $check['message'] }}</p>
                                                
                                                @if(!empty($check['details']))
                                                    <div class="mt-2">
                                                        <details class="group">
                                                            <summary class="cursor-pointer text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                                                <span class="group-open:hidden">顯示詳細資訊</span>
                                                                <span class="hidden group-open:inline">隱藏詳細資訊</span>
                                                            </summary>
                                                            <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded text-xs">
                                                                <pre class="whitespace-pre-wrap text-gray-600 dark:text-gray-300">{{ json_encode($check['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            </div>
                                                        </details>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有找到符合條件的檢測項目</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">請嘗試調整搜尋條件或篩選設定</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>

<script>
    // 處理檔案下載
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Livewire !== 'undefined') {
            Livewire.on('download-file', (event) => {
                const { filename, content, type } = event;
                const blob = new Blob([content], { type: type });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            });
        }
    });
</script>