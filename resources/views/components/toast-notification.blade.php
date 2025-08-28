{{-- Toast 通知元件 --}}
@props([
    'type' => 'info', // success, error, warning, info
    'title' => null,
    'message' => '',
    'duration' => 5000,
    'closable' => true,
    'position' => 'top-right' // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
])

@php
    $typeClasses = [
        'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200',
        'error' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200'
    ];
    
    $iconClasses = [
        'success' => 'text-green-400',
        'error' => 'text-red-400',
        'warning' => 'text-yellow-400',
        'info' => 'text-blue-400'
    ];
    
    $positionClasses = [
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        'top-center' => 'top-4 left-1/2 transform -translate-x-1/2',
        'bottom-center' => 'bottom-4 left-1/2 transform -translate-x-1/2'
    ];
    
    $toastClass = $typeClasses[$type] ?? $typeClasses['info'];
    $iconClass = $iconClasses[$type] ?? $iconClasses['info'];
    $positionClass = $positionClasses[$position] ?? $positionClasses['top-right'];
@endphp

<div 
    x-data="{ 
        show: true,
        init() {
            @if($duration > 0)
                setTimeout(() => { this.show = false }, {{ $duration }})
            @endif
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    {{ $attributes->merge(['class' => "fixed {$positionClass} z-50 max-w-sm w-full"]) }}
>
    <div class="rounded-lg border p-4 shadow-lg {{ $toastClass }}">
        <div class="flex items-start">
            {{-- 圖示 --}}
            <div class="flex-shrink-0">
                @if($type === 'success')
                    <svg class="w-5 h-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($type === 'error')
                    <svg class="w-5 h-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($type === 'warning')
                    <svg class="w-5 h-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                @else
                    <svg class="w-5 h-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
            </div>
            
            {{-- 內容 --}}
            <div class="ml-3 flex-1">
                @if($title)
                    <h3 class="text-sm font-medium">{{ $title }}</h3>
                    <div class="mt-1 text-sm opacity-90">{{ $message }}</div>
                @else
                    <div class="text-sm font-medium">{{ $message }}</div>
                @endif
            </div>
            
            {{-- 關閉按鈕 --}}
            @if($closable)
                <div class="ml-4 flex-shrink-0">
                    <button 
                        @click="show = false"
                        class="inline-flex rounded-md p-1.5 hover:bg-black/5 dark:hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent focus:ring-current transition-colors duration-200"
                    >
                        <span class="sr-only">關閉</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif
        </div>
        
        {{-- 進度條（如果有持續時間） --}}
        @if($duration > 0)
            <div class="mt-3 w-full bg-black/10 dark:bg-white/10 rounded-full h-1">
                <div 
                    class="h-1 rounded-full bg-current opacity-50"
                    x-data="{ width: 100 }"
                    x-init="
                        let startTime = Date.now();
                        let duration = {{ $duration }};
                        let interval = setInterval(() => {
                            let elapsed = Date.now() - startTime;
                            width = Math.max(0, 100 - (elapsed / duration * 100));
                            if (width <= 0) {
                                clearInterval(interval);
                            }
                        }, 50);
                    "
                    :style="`width: ${width}%`"
                ></div>
            </div>
        @endif
    </div>
</div>