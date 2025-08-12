{{-- NetworkStatus 網路狀態檢測和離線提示視圖 --}}
<div class="network-status-container"
     x-data="{ 
         isOnline: @entangle('isOnline'),
         showOfflineMessage: @entangle('showOfflineMessage'),
         offlineQueue: @entangle('offlineQueue'),
         connectionQuality: @entangle('connectionQuality'),
         offlineDuration: 0,
         updateInterval: null
     }"
     x-init="
         // 監聽瀏覽器網路事件
         window.addEventListener('online', () => {
             $wire.setNetworkStatus(true);
         });
         
         window.addEventListener('offline', () => {
             $wire.setNetworkStatus(false);
         });
         
         // 定期檢測網路狀態
         updateInterval = setInterval(() => {
             if (!isOnline) {
                 offlineDuration++;
             }
             
             // 每 30 秒進行一次連線測試
             if (Date.now() % 30000 < 1000) {
                 checkConnection();
             }
         }, 1000);
         
         // 初始化網路狀態
         $wire.setNetworkStatus(navigator.onLine);
     "
     x-destroy="clearInterval(updateInterval)">
    
    {{-- 離線狀態橫幅 --}}
    <div class="offline-banner"
         x-show="showOfflineMessage"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="-translate-y-full"
         style="display: none;">
        
        <div class="offline-content">
            {{-- 離線圖示 --}}
            <div class="offline-icon">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/>
                </svg>
            </div>
            
            {{-- 離線訊息 --}}
            <div class="offline-message">
                <div class="offline-title">網路連線中斷</div>
                <div class="offline-subtitle">
                    您目前處於離線狀態
                    <span x-show="offlineDuration > 0" x-text="`，已離線 ${Math.floor(offlineDuration / 60)} 分 ${offlineDuration % 60} 秒`"></span>
                </div>
            </div>
            
            {{-- 離線佇列資訊 --}}
            @if($offlineModeEnabled)
                <div class="offline-queue-info" x-show="offlineQueue.length > 0">
                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="`${offlineQueue.length} 個操作待同步`">{{ count($offlineQueue) }} 個操作待同步</span>
                </div>
            @endif
            
            {{-- 操作按鈕 --}}
            <div class="offline-actions">
                <button type="button"
                        class="offline-retry-btn"
                        wire:click="reconnect"
                        :disabled="$wire.reconnectAttempts >= $wire.maxReconnectAttempts">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    重新連線
                </button>
                
                @if(!$offlineModeEnabled)
                    <button type="button"
                            class="offline-mode-btn"
                            wire:click="toggleOfflineMode">
                        啟用離線模式
                    </button>
                @endif
            </div>
        </div>
    </div>
    
    {{-- 網路狀態指示器 (右下角) --}}
    <div class="network-indicator"
         x-show="!isOnline || connectionQuality !== 'good'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="indicator-content" :class="{
            'indicator-offline': !isOnline,
            'indicator-poor': isOnline && connectionQuality === 'poor',
            'indicator-fair': isOnline && connectionQuality === 'fair'
        }">
            {{-- 連線狀態圖示 --}}
            <div class="indicator-icon">
                @switch($this->connectionIcon)
                    @case('offline')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/>
                        </svg>
                        @break
                    @case('wifi-weak')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.778 8.222c-4.296-4.296-11.26-4.296-15.556 0A1 1 0 01.808 6.808c5.076-5.077 13.308-5.077 18.384 0a1 1 0 01-1.414 1.414zM14.95 11.05a7 7 0 00-9.9 0 1 1 0 01-1.414-1.414 9 9 0 0112.728 0 1 1 0 01-1.414 1.414zM12.12 13.88a3 3 0 00-4.24 0 1 1 0 01-1.415-1.414 5 5 0 017.07 0 1 1 0 01-1.415 1.414zM9 16a1 1 0 112 0 1 1 0 01-2 0z"/>
                        </svg>
                        @break
                    @case('wifi-medium')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.778 8.222c-4.296-4.296-11.26-4.296-15.556 0A1 1 0 01.808 6.808c5.076-5.077 13.308-5.077 18.384 0a1 1 0 01-1.414 1.414zM14.95 11.05a7 7 0 00-9.9 0 1 1 0 01-1.414-1.414 9 9 0 0112.728 0 1 1 0 01-1.414 1.414zM12.12 13.88a3 3 0 00-4.24 0 1 1 0 01-1.415-1.414 5 5 0 017.07 0 1 1 0 01-1.415 1.414zM9 16a1 1 0 112 0 1 1 0 01-2 0z"/>
                        </svg>
                        @break
                    @default
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.778 8.222c-4.296-4.296-11.26-4.296-15.556 0A1 1 0 01.808 6.808c5.076-5.077 13.308-5.077 18.384 0a1 1 0 01-1.414 1.414zM14.95 11.05a7 7 0 00-9.9 0 1 1 0 01-1.414-1.414 9 9 0 0112.728 0 1 1 0 01-1.414 1.414zM12.12 13.88a3 3 0 00-4.24 0 1 1 0 01-1.415-1.414 5 5 0 017.07 0 1 1 0 01-1.415 1.414zM9 16a1 1 0 112 0 1 1 0 01-2 0z"/>
                        </svg>
                @endswitch
            </div>
            
            {{-- 連線狀態文字 --}}
            <div class="indicator-text">
                {{ $this->connectionStatus }}
                @if($latency > 0)
                    <span class="latency">({{ $latency }}ms)</span>
                @endif
            </div>
        </div>
    </div>
    
    {{-- 離線佇列管理面板 --}}
    @if($offlineModeEnabled && count($offlineQueue) > 0)
        <div class="offline-queue-panel"
             x-data="{ showQueue: false }"
             x-show="!isOnline">
            
            <button type="button"
                    class="queue-toggle-btn"
                    @click="showQueue = !showQueue">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>
                離線佇列 ({{ count($offlineQueue) }})
                <svg class="w-4 h-4 ml-1 transform transition-transform" :class="{ 'rotate-180': showQueue }" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            
            <div class="queue-content"
                 x-show="showQueue"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 style="display: none;">
                
                <div class="queue-header">
                    <h3 class="queue-title">待同步操作</h3>
                    <button type="button"
                            class="queue-clear-btn"
                            wire:click="clearOfflineQueue"
                            onclick="return confirm('確定要清空離線佇列嗎？')">
                        清空佇列
                    </button>
                </div>
                
                <div class="queue-list">
                    @foreach($offlineQueue as $action)
                        <div class="queue-item" wire:key="queue-{{ $action['id'] }}">
                            <div class="queue-item-content">
                                <div class="queue-item-type">
                                    {{ $action['type'] ?? '未知操作' }}
                                </div>
                                <div class="queue-item-time">
                                    {{ \Carbon\Carbon::createFromTimestamp($action['timestamp'])->diffForHumans() }}
                                </div>
                            </div>
                            
                            <button type="button"
                                    class="queue-item-remove"
                                    wire:click="removeOfflineAction('{{ $action['id'] }}')"
                                    title="移除此操作">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

{{-- 網路狀態樣式 --}}
<style>
/* 離線狀態橫幅 */
.offline-banner {
    @apply fixed top-0 left-0 right-0 z-50 bg-red-500 text-white shadow-lg;
}

.offline-content {
    @apply flex items-center justify-between px-4 py-3 max-w-7xl mx-auto;
}

.offline-icon {
    @apply flex-shrink-0;
}

.offline-message {
    @apply flex-1 mx-4;
}

.offline-title {
    @apply font-semibold text-sm;
}

.offline-subtitle {
    @apply text-xs opacity-90 mt-1;
}

.offline-queue-info {
    @apply flex items-center space-x-2 text-sm bg-red-600 px-3 py-1 rounded;
}

.offline-actions {
    @apply flex items-center space-x-3;
}

.offline-retry-btn {
    @apply inline-flex items-center px-3 py-1 text-sm font-medium bg-red-600 hover:bg-red-700;
    @apply rounded transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed;
}

.offline-mode-btn {
    @apply px-3 py-1 text-sm font-medium bg-red-600 hover:bg-red-700 rounded transition-colors duration-200;
}

/* 網路狀態指示器 */
.network-indicator {
    @apply fixed bottom-4 right-4 z-40;
}

.indicator-content {
    @apply flex items-center space-x-2 px-3 py-2 rounded-lg shadow-lg text-sm font-medium;
    @apply bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700;
}

.indicator-content.indicator-offline {
    @apply bg-red-50 border-red-200 text-red-800 dark:bg-red-900 dark:border-red-800 dark:text-red-200;
}

.indicator-content.indicator-poor {
    @apply bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-800 dark:text-yellow-200;
}

.indicator-content.indicator-fair {
    @apply bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900 dark:border-blue-800 dark:text-blue-200;
}

.indicator-icon {
    @apply flex-shrink-0;
}

.indicator-text .latency {
    @apply text-xs opacity-75;
}

/* 離線佇列面板 */
.offline-queue-panel {
    @apply fixed bottom-4 left-4 z-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700;
    @apply max-w-sm;
}

.queue-toggle-btn {
    @apply w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300;
    @apply hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200;
}

.queue-content {
    @apply border-t border-gray-200 dark:border-gray-700;
}

.queue-header {
    @apply flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-700;
}

.queue-title {
    @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.queue-clear-btn {
    @apply text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300;
    @apply transition-colors duration-200;
}

.queue-list {
    @apply max-h-64 overflow-y-auto;
}

.queue-item {
    @apply flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-gray-600 last:border-b-0;
}

.queue-item-content {
    @apply flex-1;
}

.queue-item-type {
    @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.queue-item-time {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.queue-item-remove {
    @apply text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors duration-200;
}

/* 響應式設計 */
@media (max-width: 640px) {
    .offline-content {
        @apply flex-col space-y-2 py-4;
    }
    
    .offline-message {
        @apply mx-0 text-center;
    }
    
    .offline-actions {
        @apply justify-center;
    }
    
    .network-indicator {
        @apply bottom-2 right-2;
    }
    
    .offline-queue-panel {
        @apply left-2 right-2 bottom-2 max-w-none;
    }
}

/* 動畫效能優化 */
@media (prefers-reduced-motion: reduce) {
    .offline-banner,
    .network-indicator,
    .queue-content {
        @apply transition-none;
    }
}

/* 高對比模式 */
@media (prefers-contrast: high) {
    .offline-banner {
        @apply border-b-4 border-red-700;
    }
    
    .indicator-content {
        @apply border-2;
    }
}
</style>

{{-- JavaScript 網路檢測功能 --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    let networkMonitor = {
        isOnline: navigator.onLine,
        lastCheck: Date.now(),
        checkInterval: 30000, // 30 秒
        
        // 檢測網路連線
        async checkConnection() {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);
                
                const startTime = Date.now();
                const response = await fetch('/api/ping', {
                    method: 'HEAD',
                    cache: 'no-cache',
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                const latency = Date.now() - startTime;
                
                const isOnline = response.ok;
                
                Livewire.dispatch('connection-test-result', {
                    isOnline,
                    latency
                });
                
                return { isOnline, latency };
            } catch (error) {
                Livewire.dispatch('connection-test-result', {
                    isOnline: false,
                    latency: 0
                });
                
                return { isOnline: false, latency: 0 };
            }
        },
        
        // 監控網路品質
        async monitorQuality() {
            if (!navigator.onLine) return;
            
            const tests = [];
            for (let i = 0; i < 3; i++) {
                const result = await this.checkConnection();
                tests.push(result.latency);
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
            
            const avgLatency = tests.reduce((a, b) => a + b, 0) / tests.length;
            
            Livewire.dispatch('network-status-update', {
                isOnline: true,
                details: {
                    latency: Math.round(avgLatency),
                    type: 'quality-test'
                }
            });
        },
        
        // 初始化監控
        init() {
            // 定期檢測
            setInterval(() => {
                if (Date.now() - this.lastCheck > this.checkInterval) {
                    this.checkConnection();
                    this.lastCheck = Date.now();
                }
            }, 5000);
            
            // 頁面可見性變更時檢測
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.checkConnection();
                }
            });
            
            // 監聽 Livewire 請求失敗
            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status }) => {
                        if (status === 0 || status >= 500) {
                            this.checkConnection();
                        }
                    });
                });
            });
        }
    };
    
    // 初始化網路監控
    networkMonitor.init();
    
    // 全域函數
    window.checkConnection = () => networkMonitor.checkConnection();
    window.monitorNetworkQuality = () => networkMonitor.monitorQuality();
});
</script>