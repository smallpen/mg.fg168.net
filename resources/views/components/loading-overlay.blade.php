{{-- 載入覆蓋層元件 --}}
@props([
    'show' => false,
    'message' => null,
    'type' => 'default', // default, spinner, dots, pulse
    'size' => 'md', // sm, md, lg
    'transparent' => false
])

@php
    $sizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-6 h-6', 
        'lg' => 'w-8 h-8'
    ];
    
    $spinnerSize = $sizeClasses[$size] ?? $sizeClasses['md'];
    $overlayClasses = $transparent 
        ? 'bg-white/50 dark:bg-gray-800/50' 
        : 'bg-white/75 dark:bg-gray-800/75';
@endphp

<div 
    {{ $attributes->merge(['class' => "absolute inset-0 {$overlayClasses} z-10 flex items-center justify-center transition-opacity duration-200"]) }}
    x-show="{{ $show ? 'true' : 'false' }}"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="{{ $show ? '' : 'display: none;' }}"
>
    <div class="flex flex-col items-center space-y-3">
        {{-- 載入動畫 --}}
        @if($type === 'spinner')
            <svg class="animate-spin {{ $spinnerSize }} text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @elseif($type === 'dots')
            <div class="flex space-x-1">
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
        @elseif($type === 'pulse')
            <div class="{{ $spinnerSize }} bg-blue-500 rounded-full animate-pulse"></div>
        @else
            {{-- 預設 spinner --}}
            <svg class="animate-spin {{ $spinnerSize }} text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif

        {{-- 載入訊息 --}}
        @if($message)
            <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                {{ $message }}
            </span>
        @endif
    </div>
</div>