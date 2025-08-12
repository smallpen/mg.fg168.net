{{-- PageLoadingIndicator 頁面載入進度指示器視圖 --}}
<div class="page-loading-wrapper"
     x-data="{ 
         show: @entangle('isLoading'),
         progress: @entangle('progress')
     }">
    {{-- 頂部進度條 --}}
    <div class="page-loading-indicator"
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        {{-- 進度條容器 --}}
        <div class="progress-container">
            <div class="{{ $this->progressBarClasses }}"
                 :style="`width: ${progress}%`"
                 style="width: {{ $progress }}%">
                <div class="progress-shimmer"></div>
            </div>
        </div>
        
        {{-- 載入資訊面板 (可選顯示) --}}
        @if($currentStep)
            <div class="loading-info-panel"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300 delay-100"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0">
                
                <div class="loading-step">
                    <div class="step-indicator">
                        <div class="step-number">{{ $currentStepIndex + 1 }}</div>
                        <div class="step-total">/ {{ count($loadingSteps) }}</div>
                    </div>
                    
                    <div class="step-content">
                        <div class="step-text">{{ $currentStep }}</div>
                        
                        @if($progress > 0 && $progress < 100)
                            <div class="step-progress">
                                {{ $progress }}%
                                @if($this->estimatedRemainingTime > 0)
                                    <span class="remaining-time">
                                        (剩餘約 {{ $this->estimatedRemainingTime }} 秒)
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- 載入步驟列表 --}}
                <div class="steps-list">
                    @foreach($loadingSteps as $index => $stepText)
                        <div class="step-item {{ $index <= $currentStepIndex ? 'completed' : '' }} {{ $index === $currentStepIndex ? 'active' : '' }}">
                            <div class="step-dot">
                                @if($index < $currentStepIndex)
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @elseif($index === $currentStepIndex)
                                    <div class="loading-dot"></div>
                                @else
                                    <div class="pending-dot"></div>
                                @endif
                            </div>
                            <div class="step-label">{{ $stepText }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    
    {{-- 頁面載入骨架屏 (當載入時間較長時顯示) --}}
    @if($isLoading && $this->elapsedTime > 2)
        <div class="page-skeleton"
             x-show="show"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            
            <div class="skeleton-header">
                <div class="skeleton-title"></div>
                <div class="skeleton-subtitle"></div>
            </div>
            
            <div class="skeleton-content">
                <div class="skeleton-card">
                    <div class="skeleton-card-header"></div>
                    <div class="skeleton-card-body">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line short"></div>
                        <div class="skeleton-line"></div>
                    </div>
                </div>
                
                <div class="skeleton-card">
                    <div class="skeleton-card-header"></div>
                    <div class="skeleton-card-body">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line short"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
/* 主要進度指示器 */
.page-loading-indicator {
    @apply fixed top-0 left-0 right-0 z-50;
}

/* 進度條容器 */
.progress-container {
    @apply relative h-1 bg-gray-200 dark:bg-gray-700 overflow-hidden;
}

/* 進度條 */
.progress-bar {
    @apply h-full transition-all duration-300 ease-out relative overflow-hidden;
    background: linear-gradient(90deg, #3B82F6, #1D4ED8, #3B82F6);
    background-size: 200% 100%;
    animation: progress-gradient 2s ease-in-out infinite;
}

.progress-bar.low {
    @apply bg-red-500;
}

.progress-bar.medium {
    @apply bg-yellow-500;
}

.progress-bar.high {
    @apply bg-blue-500;
}

.progress-bar.complete {
    @apply bg-green-500;
}

/* 進度條光澤效果 */
.progress-shimmer {
    @apply absolute inset-0 opacity-30;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes progress-gradient {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* 載入資訊面板 */
.loading-info-panel {
    @apply absolute top-1 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 min-w-80;
}

/* 載入步驟 */
.loading-step {
    @apply flex items-center space-x-3 mb-4;
}

.step-indicator {
    @apply flex items-center space-x-1 text-sm font-medium text-blue-600 dark:text-blue-400;
}

.step-number {
    @apply bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full w-6 h-6 flex items-center justify-center text-xs;
}

.step-total {
    @apply text-gray-500 dark:text-gray-400;
}

.step-content {
    @apply flex-1;
}

.step-text {
    @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.step-progress {
    @apply text-xs text-gray-500 dark:text-gray-400 mt-1;
}

.remaining-time {
    @apply text-gray-400 dark:text-gray-500;
}

/* 步驟列表 */
.steps-list {
    @apply space-y-2;
}

.step-item {
    @apply flex items-center space-x-2 text-sm;
}

.step-dot {
    @apply w-4 h-4 flex items-center justify-center;
}

.loading-dot {
    @apply w-2 h-2 bg-blue-500 rounded-full;
    animation: pulse 1.5s ease-in-out infinite;
}

.pending-dot {
    @apply w-2 h-2 bg-gray-300 dark:bg-gray-600 rounded-full;
}

.step-item.completed .step-label {
    @apply text-green-600 dark:text-green-400;
}

.step-item.active .step-label {
    @apply text-blue-600 dark:text-blue-400 font-medium;
}

.step-item .step-label {
    @apply text-gray-500 dark:text-gray-400;
}

/* 骨架屏 */
.page-skeleton {
    @apply fixed inset-0 bg-white dark:bg-gray-900 z-40 p-6;
}

.skeleton-header {
    @apply mb-8;
}

.skeleton-title {
    @apply h-8 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4;
    width: 300px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-subtitle {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded;
    width: 200px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
    animation-delay: 0.1s;
}

.skeleton-content {
    @apply grid grid-cols-1 md:grid-cols-2 gap-6;
}

.skeleton-card {
    @apply bg-gray-50 dark:bg-gray-800 rounded-lg p-6;
}

.skeleton-card-header {
    @apply h-6 bg-gray-200 dark:bg-gray-700 rounded mb-4;
    width: 150px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-card-body {
    @apply space-y-3;
}

.skeleton-line {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-line.short {
    width: 60%;
}

@keyframes skeleton-loading {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* 響應式設計 */
@media (max-width: 768px) {
    .loading-info-panel {
        @apply left-4 right-4 min-w-0;
    }
    
    .skeleton-content {
        @apply grid-cols-1;
    }
}

/* 暗色主題適配 */
@media (prefers-color-scheme: dark) {
    .progress-container {
        @apply bg-gray-700;
    }
    
    .loading-info-panel {
        @apply bg-gray-800 border-gray-700;
    }
    
    .page-skeleton {
        @apply bg-gray-900;
    }
}

/* 動畫效能優化 */
@media (prefers-reduced-motion: reduce) {
    .progress-bar,
    .progress-shimmer,
    .loading-dot,
    .skeleton-title,
    .skeleton-subtitle,
    .skeleton-line {
        animation: none;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 監聽頁面載入事件
    window.addEventListener('beforeunload', function() {
        Livewire.dispatch('start-page-loading');
    });
    
    // 監聽 Livewire 請求
    document.addEventListener('livewire:init', function() {
        Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
            // 只對非背景請求顯示載入指示器
            if (!options.background) {
                Livewire.dispatch('start-page-loading', {
                    steps: {
                        'request': '發送請求...',
                        'processing': '處理中...',
                        'response': '接收回應...'
                    },
                    estimatedDuration: 2000
                });
            }
            
            succeed(({ status, response }) => {
                if (!options.background) {
                    Livewire.dispatch('finish-page-loading');
                }
            });
            
            fail(({ status, content, preventDefault }) => {
                if (!options.background) {
                    Livewire.dispatch('finish-page-loading');
                }
            });
        });
    });
    
    // 網路狀態監聽
    window.addEventListener('online', function() {
        Livewire.dispatch('network-status-changed', { isOnline: true });
    });
    
    window.addEventListener('offline', function() {
        Livewire.dispatch('network-status-changed', { isOnline: false });
    });
});
</script>
@endpush