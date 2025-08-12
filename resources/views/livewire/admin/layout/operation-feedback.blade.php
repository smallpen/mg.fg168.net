{{-- OperationFeedback 操作狀態回饋系統視圖 --}}
<div class="operation-feedback-container">
    {{-- 回饋訊息列表 --}}
    <div class="feedback-list"
         x-data="{ 
             feedbacks: @entangle('feedbacks'),
             autoRemoveTimers: {}
         }"
         @auto-remove-feedback.window="
             if (autoRemoveTimers[$event.detail.id]) {
                 clearTimeout(autoRemoveTimers[$event.detail.id]);
             }
             autoRemoveTimers[$event.detail.id] = setTimeout(() => {
                 $wire.removeFeedback($event.detail.id);
                 delete autoRemoveTimers[$event.detail.id];
             }, $event.detail.duration);
         ">
        
        @foreach($feedbacks as $index => $feedback)
            <div class="{{ $this->getFeedbackClasses($feedback) }}"
                 x-data="{ 
                     show: true,
                     progress: {{ $feedback['progress'] ?? 'null' }},
                     startTime: {{ $feedback['timestamp'] }},
                     duration: {{ $feedback['duration'] }},
                     isPersistent: {{ $feedback['persistent'] ? 'true' : 'false' }}
                 }"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-x-full opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-x-0 opacity-100"
                 x-transition:leave-end="translate-x-full opacity-0"
                 wire:key="feedback-{{ $feedback['id'] }}">
                
                <div class="feedback-content">
                    {{-- 回饋圖示 --}}
                    <div class="feedback-icon">
                        @switch($this->getFeedbackIcon($feedback))
                            @case('check-circle')
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                @break
                            @case('x-circle')
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                @break
                            @case('exclamation-triangle')
                                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                @break
                            @case('information-circle')
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                @break
                            @case('refresh')
                                <svg class="w-5 h-5 text-gray-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                @break
                        @endswitch
                    </div>
                    
                    {{-- 回饋內容 --}}
                    <div class="feedback-body">
                        <div class="feedback-title">{{ $feedback['title'] }}</div>
                        <div class="feedback-message">{{ $feedback['message'] }}</div>
                        
                        {{-- 載入進度條 --}}
                        @if($feedback['type'] === 'loading' && isset($feedback['progress']))
                            <div class="feedback-progress" x-show="progress !== null">
                                <div class="progress-bar">
                                    <div class="progress-fill" 
                                         :style="`width: ${progress}%`"
                                         style="width: {{ $feedback['progress'] }}%"></div>
                                </div>
                                <div class="progress-text" x-text="`${progress}%`">{{ $feedback['progress'] }}%</div>
                            </div>
                        @endif
                        
                        {{-- 時間戳記 --}}
                        <div class="feedback-timestamp">
                            {{ \Carbon\Carbon::createFromTimestamp($feedback['timestamp'])->diffForHumans() }}
                        </div>
                    </div>
                    
                    {{-- 操作按鈕 --}}
                    <div class="feedback-actions">
                        {{-- 自訂動作按鈕 --}}
                        @if(!empty($feedback['actions']))
                            @foreach($feedback['actions'] as $actionKey => $action)
                                <button type="button"
                                        class="feedback-action-btn {{ $action['style'] ?? 'primary' }}"
                                        wire:click="executeAction('{{ $feedback['id'] }}', '{{ $actionKey }}')"
                                        @if(isset($action['confirm']))
                                            onclick="return confirm('{{ $action['confirm'] }}')"
                                        @endif>
                                    @if(isset($action['icon']))
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            {{-- 這裡可以根據 action['icon'] 顯示不同圖示 --}}
                                        </svg>
                                    @endif
                                    {{ $action['label'] }}
                                </button>
                            @endforeach
                        @endif
                        
                        {{-- 關閉按鈕 --}}
                        <button type="button"
                                class="feedback-close-btn"
                                wire:click="removeFeedback('{{ $feedback['id'] }}')"
                                title="關閉">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    
                    {{-- 自動消失進度條 (非持久性回饋) --}}
                    @if(!$feedback['persistent'] && $feedback['duration'] > 0)
                        <div class="feedback-timer"
                             x-data="{ 
                                 timeLeft: duration,
                                 interval: null
                             }"
                             x-init="
                                 interval = setInterval(() => {
                                     timeLeft -= 100;
                                     if (timeLeft <= 0) {
                                         clearInterval(interval);
                                     }
                                 }, 100);
                             "
                             x-destroy="clearInterval(interval)">
                            <div class="timer-bar"
                                 :style="`width: ${(timeLeft / duration) * 100}%`"></div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- 清除所有按鈕 (當有多個回饋時顯示) --}}
    @if(count($feedbacks) > 1)
        <div class="feedback-controls">
            <button type="button"
                    class="clear-all-btn"
                    wire:click="clearAllFeedbacks">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                清除所有通知
            </button>
        </div>
    @endif
</div>

{{-- 操作回饋樣式 --}}
<style>
/* 回饋容器 */
.operation-feedback-container {
    @apply fixed top-4 right-4 z-50 space-y-3 max-w-sm;
}

.feedback-list {
    @apply space-y-3;
}

/* 回饋項目 */
.feedback-item {
    @apply bg-white dark:bg-gray-800 rounded-lg shadow-lg border-l-4 p-4;
    @apply transform transition-all duration-300 ease-in-out;
}

.feedback-item:hover {
    @apply shadow-xl scale-105;
}

/* 回饋內容 */
.feedback-content {
    @apply flex items-start space-x-3 relative;
}

.feedback-icon {
    @apply flex-shrink-0 mt-0.5;
}

.feedback-body {
    @apply flex-1 min-w-0;
}

.feedback-title {
    @apply text-sm font-semibold text-gray-900 dark:text-gray-100;
}

.feedback-message {
    @apply text-sm text-gray-700 dark:text-gray-300 mt-1;
}

.feedback-timestamp {
    @apply text-xs text-gray-500 dark:text-gray-400 mt-2;
}

/* 載入進度 */
.feedback-progress {
    @apply mt-3 space-y-1;
}

.feedback-progress .progress-bar {
    @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden;
}

.feedback-progress .progress-fill {
    @apply h-full bg-blue-500 rounded-full transition-all duration-300 ease-out;
}

.feedback-progress .progress-text {
    @apply text-xs text-gray-600 dark:text-gray-400 text-right;
}

/* 操作按鈕 */
.feedback-actions {
    @apply flex items-center space-x-2 ml-auto;
}

.feedback-action-btn {
    @apply inline-flex items-center px-2 py-1 text-xs font-medium rounded;
    @apply transition-colors duration-200;
}

.feedback-action-btn.primary {
    @apply bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800;
}

.feedback-action-btn.secondary {
    @apply bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600;
}

.feedback-action-btn.danger {
    @apply bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800;
}

.feedback-close-btn {
    @apply text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
    @apply transition-colors duration-200;
}

/* 自動消失計時器 */
.feedback-timer {
    @apply absolute bottom-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 rounded-b-lg overflow-hidden;
}

.feedback-timer .timer-bar {
    @apply h-full bg-blue-500 transition-all duration-100 ease-linear;
}

/* 回饋類型樣式 */
.feedback-success {
    @apply border-green-500 bg-green-50 dark:bg-green-900 dark:bg-opacity-20;
}

.feedback-error {
    @apply border-red-500 bg-red-50 dark:bg-red-900 dark:bg-opacity-20;
}

.feedback-warning {
    @apply border-yellow-500 bg-yellow-50 dark:bg-yellow-900 dark:bg-opacity-20;
}

.feedback-info {
    @apply border-blue-500 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20;
}

.feedback-loading {
    @apply border-gray-500 bg-gray-50 dark:bg-gray-800;
}

/* 控制按鈕 */
.feedback-controls {
    @apply mt-4 text-center;
}

.clear-all-btn {
    @apply inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300;
    @apply bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md;
    @apply hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200;
}

/* 響應式設計 */
@media (max-width: 640px) {
    .operation-feedback-container {
        @apply left-4 right-4 top-4 max-w-none;
    }
    
    .feedback-item {
        @apply p-3;
    }
    
    .feedback-content {
        @apply space-x-2;
    }
    
    .feedback-actions {
        @apply flex-col space-x-0 space-y-1;
    }
}

/* 動畫效能優化 */
@media (prefers-reduced-motion: reduce) {
    .feedback-item {
        @apply transition-none;
    }
    
    .feedback-progress .progress-fill,
    .feedback-timer .timer-bar {
        @apply transition-none;
    }
}

/* 高對比模式 */
@media (prefers-contrast: high) {
    .feedback-item {
        @apply border-2;
    }
    
    .feedback-title {
        @apply font-bold;
    }
}
</style>

{{-- JavaScript 增強功能 --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 監聽全域操作事件
    window.addEventListener('operation-feedback', function(event) {
        const { type, message, title, duration, actions } = event.detail;
        
        Livewire.dispatch('show-feedback', {
            message,
            type,
            duration,
            title,
            actions
        });
    });
    
    // 鍵盤快捷鍵支援
    document.addEventListener('keydown', function(event) {
        // Escape 鍵清除所有回饋
        if (event.key === 'Escape' && event.ctrlKey) {
            Livewire.dispatch('clear-all-feedbacks');
        }
    });
    
    // 頁面可見性變更時暫停/恢復計時器
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // 頁面隱藏時暫停計時器
            document.dispatchEvent(new CustomEvent('pause-feedback-timers'));
        } else {
            // 頁面顯示時恢復計時器
            document.dispatchEvent(new CustomEvent('resume-feedback-timers'));
        }
    });
});
</script>