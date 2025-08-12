{{-- LoadingOverlay 全域載入元件視圖 --}}
<div class="loading-overlay-wrapper">
    {{-- 主要載入覆蓋層 --}}
    @if($isLoading)
        <div class="{{ $this->loadingClasses }}" 
             x-data="{ 
                 show: @entangle('isLoading'),
                 progress: @entangle('progress'),
                 autoHideTimer: null
             }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="loading-backdrop"></div>
            
            <div class="loading-content">
                {{-- Spinner 載入動畫 --}}
                @if($loadingType === 'spinner')
                    <div class="loading-spinner">
                        <div class="spinner-ring">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                @endif
                
                {{-- 進度條載入 --}}
                @if($loadingType === 'progress' && $showProgress)
                    <div class="loading-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" 
                                 :style="`width: ${progress}%`"
                                 style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="progress-text">
                            {{ $progress }}%
                            @if($estimatedRemainingTime > 0)
                                <span class="estimated-time">
                                    (預估剩餘 {{ $this->estimatedRemainingTime }} 秒)
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
                
                {{-- 載入文字 --}}
                <div class="loading-text">
                    {{ $loadingText }}
                </div>
                
                {{-- 取消按鈕 (長時間操作時顯示) --}}
                @if($estimatedTime > 30)
                    <button type="button" 
                            class="loading-cancel-btn"
                            wire:click="$dispatch('cancel-operation')">
                        取消操作
                    </button>
                @endif
            </div>
        </div>
    @endif
    
    {{-- 操作狀態訊息 --}}
    @if($showOperationStatus)
        <div class="{{ $this->operationStatusClasses }}"
             x-data="{ 
                 show: @entangle('showOperationStatus'),
                 autoHideTimer: null
             }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @auto-hide-status.window="
                 clearTimeout(autoHideTimer);
                 autoHideTimer = setTimeout(() => {
                     $wire.hideOperationStatus();
                 }, $event.detail.duration);
             ">
            
            <div class="status-content">
                {{-- 狀態圖示 --}}
                <div class="status-icon">
                    @switch($operationType)
                        @case('success')
                            <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            @break
                        @case('error')
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            @break
                        @case('warning')
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            @break
                        @default
                            <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                    @endswitch
                </div>
                
                {{-- 狀態訊息 --}}
                <div class="status-message">
                    {{ $operationMessage }}
                    
                    @if($estimatedTime > 0)
                        <div class="status-time">
                            預估完成時間：{{ $estimatedTime }} 秒
                        </div>
                    @endif
                </div>
                
                {{-- 操作按鈕 --}}
                <div class="status-actions">
                    @if(!$isOnline)
                        <button type="button" 
                                class="status-retry-btn"
                                wire:click="retryOperation">
                            重試
                        </button>
                    @endif
                    
                    <button type="button" 
                            class="status-close-btn"
                            wire:click="hideOperationStatus">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif
    
    {{-- 離線狀態提示 --}}
    @if($showOfflineMessage)
        <div class="offline-banner"
             x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="-translate-y-2 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="offline-content">
                <div class="offline-icon">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/>
                    </svg>
                </div>
                
                <div class="offline-message">
                    您目前處於離線狀態，部分功能可能無法使用
                </div>
                
                <button type="button" 
                        class="offline-retry-btn"
                        wire:click="retryOperation">
                    重新連線
                </button>
            </div>
        </div>
    @endif
</div>

{{-- 載入狀態樣式 --}}
<style>
/* 載入覆蓋層 */
.loading-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center;
}

.loading-overlay .loading-backdrop {
    @apply absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm;
}

.loading-overlay .loading-content {
    @apply relative bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl max-w-sm mx-4;
    @apply flex flex-col items-center space-y-4;
}

/* Spinner 動畫 */
.loading-spinner .spinner-ring {
    @apply inline-block relative w-12 h-12;
}

.loading-spinner .spinner-ring div {
    @apply box-border block absolute w-10 h-10 m-1 border-4 border-blue-500 rounded-full;
    animation: spinner-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    border-color: #3B82F6 transparent transparent transparent;
}

.loading-spinner .spinner-ring div:nth-child(1) { animation-delay: -0.45s; }
.loading-spinner .spinner-ring div:nth-child(2) { animation-delay: -0.3s; }
.loading-spinner .spinner-ring div:nth-child(3) { animation-delay: -0.15s; }

@keyframes spinner-ring {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 進度條 */
.loading-progress {
    @apply w-full space-y-2;
}

.loading-progress .progress-bar {
    @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden;
}

.loading-progress .progress-fill {
    @apply h-full bg-blue-500 rounded-full transition-all duration-300 ease-out;
}

.loading-progress .progress-text {
    @apply text-sm text-gray-600 dark:text-gray-400 text-center;
}

.loading-progress .estimated-time {
    @apply text-xs text-gray-500 dark:text-gray-500;
}

/* 載入文字 */
.loading-text {
    @apply text-gray-700 dark:text-gray-300 text-center font-medium;
}

/* 取消按鈕 */
.loading-cancel-btn {
    @apply mt-4 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600;
    @apply rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200;
}

/* 操作狀態訊息 */
.operation-status {
    @apply fixed top-4 right-4 z-40 max-w-sm;
}

.operation-status .status-content {
    @apply bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4;
    @apply flex items-start space-x-3;
}

.operation-status .status-icon {
    @apply flex-shrink-0;
}

.operation-status .status-message {
    @apply flex-1 text-sm text-gray-700 dark:text-gray-300;
}

.operation-status .status-time {
    @apply text-xs text-gray-500 dark:text-gray-500 mt-1;
}

.operation-status .status-actions {
    @apply flex items-center space-x-2 ml-auto;
}

.operation-status .status-retry-btn {
    @apply text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors duration-200;
}

.operation-status .status-close-btn {
    @apply text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200;
}

/* 狀態類型樣式 */
.operation-status.status-success .status-content {
    @apply border-green-200 dark:border-green-800;
}

.operation-status.status-error .status-content {
    @apply border-red-200 dark:border-red-800;
}

.operation-status.status-warning .status-content {
    @apply border-yellow-200 dark:border-yellow-800;
}

.operation-status.status-info .status-content {
    @apply border-blue-200 dark:border-blue-800;
}

/* 離線狀態橫幅 */
.offline-banner {
    @apply fixed top-0 left-0 right-0 z-30 bg-red-500 text-white;
}

.offline-banner .offline-content {
    @apply flex items-center justify-between px-4 py-2 max-w-7xl mx-auto;
}

.offline-banner .offline-icon {
    @apply flex-shrink-0;
}

.offline-banner .offline-message {
    @apply flex-1 text-sm font-medium mx-4;
}

.offline-banner .offline-retry-btn {
    @apply text-sm px-3 py-1 bg-red-600 hover:bg-red-700 rounded transition-colors duration-200;
}

/* 響應式設計 */
@media (max-width: 640px) {
    .loading-overlay .loading-content {
        @apply mx-4 p-4;
    }
    
    .operation-status {
        @apply left-4 right-4 top-4;
    }
    
    .operation-status .status-content {
        @apply p-3;
    }
}

/* 暗色主題適配 */
@media (prefers-color-scheme: dark) {
    .loading-overlay .loading-content {
        @apply bg-gray-800 border border-gray-700;
    }
    
    .loading-progress .progress-bar {
        @apply bg-gray-700;
    }
    
    .operation-status .status-content {
        @apply bg-gray-800 border-gray-700;
    }
}
</style>