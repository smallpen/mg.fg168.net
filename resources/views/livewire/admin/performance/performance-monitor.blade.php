{{-- 效能監控面板 --}}
<div>
    {{-- 觸發按鈕 --}}
    <button 
        wire:click="togglePanel"
        class="fixed bottom-4 right-4 bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-full shadow-lg z-50 transition-colors"
        title="效能監控"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
    </button>

    {{-- 監控面板 --}}
    @if($showPanel)
    <div class="fixed bottom-20 right-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 w-96 max-h-96 overflow-y-auto">
        {{-- 標題列 --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">效能監控</h3>
            <div class="flex items-center space-x-2">
                {{-- 時間週期選擇 --}}
                <select 
                    wire:model.defer="selectedPeriod" 
                    wire:key="performance-period-select"
                    wire:change="updatedSelectedPeriod($event.target.value)"
                    class="text-sm border-gray-300 dark:border-gray-600 rounded-md"
                >
                    <option value="1h">1小時</option>
                    <option value="6h">6小時</option>
                    <option value="24h">24小時</option>
                    <option value="7d">7天</option>
                </select>
                
                <button wire:click="togglePanel" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- 效能分數 --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <div class="text-3xl font-bold {{ $this->scoreColor }}">
                    {{ $metrics['performance_score'] ?? 0 }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">效能分數</div>
            </div>
        </div>

        {{-- Core Web Vitals --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Core Web Vitals</h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">LCP</span>
                    <span class="text-sm font-medium">{{ $this->formatMetric('lcp', $metrics['average_lcp'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">FID</span>
                    <span class="text-sm font-medium">{{ $this->formatMetric('fid', $metrics['average_fid'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">CLS</span>
                    <span class="text-sm font-medium">{{ $this->formatMetric('cls', $metrics['average_cls'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        {{-- 即時指標 --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">即時指標</h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">活躍使用者</span>
                    <span class="text-sm font-medium">{{ $this->realTimeMetrics['active_users'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">快取命中率</span>
                    <span class="text-sm font-medium">{{ number_format(($this->realTimeMetrics['cache_hit_rate'] ?? 0) * 100, 1) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">平均回應時間</span>
                    <span class="text-sm font-medium">{{ number_format($this->realTimeMetrics['average_response_time'] ?? 0, 0) }}ms</span>
                </div>
            </div>
        </div>

        {{-- 懶載入統計 --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">懶載入統計</h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">總元件數</span>
                    <span class="text-sm font-medium">{{ $this->lazyLoadingStats['total_components'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">延遲載入</span>
                    <span class="text-sm font-medium">{{ $this->lazyLoadingStats['deferred_components'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">高優先級</span>
                    <span class="text-sm font-medium">{{ $this->lazyLoadingStats['high_priority'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- 建議 --}}
        @if(count($recommendations) > 0)
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">效能建議</h4>
            <div class="space-y-2">
                @foreach($recommendations as $recommendation)
                <div class="p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                    <div class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        {{ $recommendation['title'] }}
                    </div>
                    <div class="text-xs text-yellow-600 dark:text-yellow-300 mt-1">
                        {{ $recommendation['description'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- 操作按鈕 --}}
        <div class="p-4">
            <div class="flex space-x-2">
                <button 
                    wire:click="loadMetrics"
                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-sm py-2 px-3 rounded-md transition-colors"
                >
                    重新整理
                </button>
                <button 
                    wire:click="clearData"
                    wire:confirm="確定要清除效能資料嗎？"
                    class="flex-1 bg-red-500 hover:bg-red-600 text-white text-sm py-2 px-3 rounded-md transition-colors"
                >
                    清除資料
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- 載入中狀態 --}}
<div wire:loading class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
        <div class="loading-spinner"></div>
        <span class="text-gray-900 dark:text-white">載入效能資料中...</span>
    </div>
</div>

{{-- 錯誤狀態顯示 --}}
@if(session()->has('performance_error'))
<div class="fixed bottom-4 left-4 bg-red-500 text-white p-4 rounded-lg shadow-lg z-50">
    <div class="flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ session('performance_error') }}</span>
    </div>
</div>
@endif

<script>
    document.addEventListener('livewire:init', () => {
        // 監聽效能週期變更事件
        Livewire.on('performance-period-changed', (event) => {
            console.log('🔄 收到 performance-period-changed 事件，週期:', event.period);
            
            // 更新圖表或其他視覺元素
            if (window.performanceChart) {
                window.performanceChart.updatePeriod(event.period);
            }
            
            // 顯示載入狀態
            const loadingIndicator = document.querySelector('[wire\\:loading]');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'flex';
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 1000);
            }
        });

        // 監聽效能資料清除事件
        Livewire.on('performance-data-cleared', () => {
            console.log('🗑️ 效能資料已清除');
            
            // 重置圖表
            if (window.performanceChart) {
                window.performanceChart.reset();
            }
            
            // 顯示成功訊息
            const successMessage = document.createElement('div');
            successMessage.className = 'fixed bottom-4 left-4 bg-green-500 text-white p-4 rounded-lg shadow-lg z-50';
            successMessage.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>效能資料已清除</span>
                </div>
            `;
            document.body.appendChild(successMessage);
            
            setTimeout(() => {
                successMessage.remove();
            }, 3000);
        });

        // 處理選擇框變更事件
        const periodSelect = document.querySelector('[wire\\:key="performance-period-select"]');
        if (periodSelect) {
            periodSelect.addEventListener('change', function(e) {
                console.log('📊 效能週期選擇變更:', e.target.value);
                
                // 觸發 blur 事件確保 Livewire 同步
                e.target.blur();
                
                // 延遲執行以確保資料同步
                setTimeout(() => {
                    // 可以在這裡添加額外的前端處理邏輯
                }, 500);
            });
        }
    });
</script>