@props([
    'type' => 'error',
    'title' => '發生錯誤',
    'message' => '',
    'errors' => [],
    'actions' => [],
    'icon' => 'exclamation-triangle',
    'dismissible' => true,
    'retry' => false,
    'retryDelay' => 0,
])

@php
    $typeClasses = [
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        'success' => 'bg-green-50 border-green-200 text-green-800',
    ];
    
    $iconClasses = [
        'error' => 'text-red-400',
        'warning' => 'text-yellow-400',
        'info' => 'text-blue-400',
        'success' => 'text-green-400',
    ];
    
    $buttonClasses = [
        'error' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
        'info' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
        'success' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
    ];
    
    $containerClass = $typeClasses[$type] ?? $typeClasses['error'];
    $iconClass = $iconClasses[$type] ?? $iconClasses['error'];
    $buttonClass = $buttonClasses[$type] ?? $buttonClasses['error'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 {$containerClass}"]) }}
     x-data="{ 
         show: true, 
         retryCount: 0,
         maxRetries: 3,
         retryDelay: {{ $retryDelay }},
         canRetry: {{ $retry ? 'true' : 'false' }}
     }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95">
    
    <div class="flex">
        {{-- 錯誤圖示 --}}
        <div class="flex-shrink-0">
            @switch($icon)
                @case('exclamation-triangle')
                    <svg class="h-5 w-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    @break
                @case('shield-exclamation')
                    <svg class="h-5 w-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/>
                    </svg>
                    @break
                @case('wifi-slash')
                    <svg class="h-5 w-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0 0L12 12m6.364 6.364L12 12m-6.364-6.364L12 12"/>
                    </svg>
                    @break
                @case('database')
                    <svg class="h-5 w-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                    @break
                @default
                    <svg class="h-5 w-5 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
            @endswitch
        </div>

        {{-- 錯誤內容 --}}
        <div class="ml-3 flex-1">
            {{-- 標題 --}}
            <h3 class="text-sm font-medium">
                {{ $title }}
            </h3>

            {{-- 訊息 --}}
            @if($message)
                <div class="mt-2 text-sm">
                    {{ $message }}
                </div>
            @endif

            {{-- 詳細錯誤列表 --}}
            @if(!empty($errors))
                <div class="mt-3">
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors as $field => $fieldErrors)
                            @if(is_array($fieldErrors))
                                @foreach($fieldErrors['messages'] as $error)
                                    <li>{{ $fieldErrors['field'] ?? $field }}: {{ $error }}</li>
                                @endforeach
                            @else
                                <li>{{ $fieldErrors }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 操作按鈕 --}}
            @if(!empty($actions) || $retry)
                <div class="mt-4 flex flex-wrap gap-2">
                    {{-- 重試按鈕 --}}
                    @if($retry)
                        <button type="button"
                                x-show="canRetry && retryCount < maxRetries"
                                @click="
                                    retryCount++;
                                    if (retryDelay > 0) {
                                        setTimeout(() => {
                                            $dispatch('retry-operation');
                                        }, retryDelay);
                                    } else {
                                        $dispatch('retry-operation');
                                    }
                                "
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white {{ $buttonClass }} focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            重試 (<span x-text="retryCount"></span>/{{ 3 }})
                        </button>
                    @endif

                    {{-- 自定義操作按鈕 --}}
                    @foreach($actions as $action)
                        @if($action['action'] === 'retry')
                            <button type="button"
                                    @click="$dispatch('retry-operation')"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white {{ $buttonClass }} focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200">
                                {{ $action['label'] }}
                            </button>
                        @elseif($action['action'] === 'redirect')
                            <a href="{{ $action['url'] }}"
                               class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                {{ $action['label'] }}
                            </a>
                        @elseif($action['action'] === 'go_back')
                            <button type="button"
                                    @click="history.back()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                {{ $action['label'] }}
                            </button>
                        @elseif($action['action'] === 'refresh')
                            <button type="button"
                                    @click="location.reload()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                {{ $action['label'] }}
                            </button>
                        @else
                            <button type="button"
                                    @click="$dispatch('{{ $action['action'] }}')"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                {{ $action['label'] }}
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 關閉按鈕 --}}
        @if($dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button"
                            @click="show = false"
                            class="inline-flex rounded-md p-1.5 hover:bg-black hover:bg-opacity-10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent focus:ring-gray-600">
                        <span class="sr-only">關閉</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>