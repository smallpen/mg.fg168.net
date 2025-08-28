<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('最近活動') }}</h3>
            <div class="flex items-center space-x-2">
                <!-- 篩選按鈕 -->
                @if($this->canViewActivityDetails())
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="btn btn-sm btn-outline-secondary"
                            type="button"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            {{ __('篩選') }}
                        </button>
                        
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-700"
                        >
                            <div class="py-1">
                                <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('活動類型') }}
                                </div>
                                <button 
                                    wire:click="setFilterType(null)"
                                    class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ !$filterType ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                                >
                                    {{ __('全部') }}
                                </button>
                                @foreach($this->filterOptions['types'] as $type)
                                    <button 
                                        wire:click="setFilterType('{{ $type }}')"
                                        class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $filterType === $type ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                                    >
                                        {{ $this->getActivityTypeName($type) }}
                                    </button>
                                @endforeach
                                
                                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                
                                <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('模組') }}
                                </div>
                                <button 
                                    wire:click="setFilterModule(null)"
                                    class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ !$filterModule ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                                >
                                    {{ __('全部') }}
                                </button>
                                @foreach($this->filterOptions['modules'] as $module)
                                    <button 
                                        wire:click="setFilterModule('{{ $module }}')"
                                        class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $filterModule === $module ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                                    >
                                        {{ $this->getModuleName($module) }}
                                    </button>
                                @endforeach
                                
                                @if($filterType || $filterModule)
                                    <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                    <button 
                                        wire:click="clearFilters"
                                        class="block w-full text-left px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900"
                                    >
                                        {{ __('清除篩選') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- 詳細資訊切換 -->
                <button 
                    wire:click="toggleDetails" 
                    class="btn btn-sm btn-outline-secondary"
                    title="{{ $showDetails ? __('隱藏詳細資訊') : __('顯示詳細資訊') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($showDetails)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        @endif
                    </svg>
                </button>
                
                <!-- 重新整理按鈕 -->
                <button 
                    wire:click="refresh" 
                    class="btn btn-sm btn-outline-primary"
                    wire:loading.attr="disabled"
                    title="{{ __('重新整理') }}"
                >
                    <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <svg wire:loading class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- 篩選狀態顯示 -->
        @if($filterType || $filterModule)
            <div class="mt-2 flex flex-wrap gap-2">
                @if($filterType)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ __('類型') }}: {{ $this->getActivityTypeName($filterType) }}
                        <button wire:click="setFilterType(null)" class="ml-1 text-blue-600 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-100">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </span>
                @endif
                @if($filterModule)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ __('模組') }}: {{ $this->getModuleName($filterModule) }}
                        <button wire:click="setFilterModule(null)" class="ml-1 text-green-600 hover:text-green-800 dark:text-green-300 dark:hover:text-green-100">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </span>
                @endif
            </div>
        @endif
    </div>
    
    <div class="card-body p-0">
        @if($this->recentActivities->isEmpty())
            <!-- 無活動記錄時的顯示 -->
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">{{ __('目前沒有活動記錄') }}</p>
                @if($filterType || $filterModule)
                    <button 
                        wire:click="clearFilters"
                        class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                    >
                        {{ __('清除篩選條件') }}
                    </button>
                @endif
            </div>
        @else
            <!-- 活動記錄列表 -->
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->recentActivities as $activity)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150">
                        <div class="flex items-start space-x-3">
                            <!-- 活動圖示 -->
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-{{ $activity->color }}-100 dark:bg-{{ $activity->color }}-900 rounded-full flex items-center justify-center">
                                    @switch($activity->icon)
                                        @case('login')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                            </svg>
                                            @break
                                        @case('logout')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            @break
                                        @case('plus-circle')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            @break
                                        @case('pencil')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            @break
                                        @case('trash')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            @break
                                        @case('user-group')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            @break
                                        @case('chart-bar')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                            @break
                                        @case('download')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            @break
                                        @case('lightning-bolt')
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            @break
                                        @default
                                            <svg class="w-4 h-4 text-{{ $activity->color }}-600 dark:text-{{ $activity->color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                    @endswitch
                                </div>
                            </div>
                            
                            <!-- 活動內容 -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $activity->description }}
                                        </p>
                                        <div class="flex items-center space-x-2 mt-1">
                                            @if($activity->user)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $activity->user->name }}
                                                </span>
                                                <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                                            @endif
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $activity->formatted_time }}
                                            </span>
                                            @if($activity->module)
                                                <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                    {{ $this->getModuleName($activity->module) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $activity->color }}-100 text-{{ $activity->color }}-800 dark:bg-{{ $activity->color }}-900 dark:text-{{ $activity->color }}-200">
                                            {{ $this->getActivityTypeName($activity->type) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- 詳細資訊 -->
                                @if($showDetails && $activity->properties)
                                    <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded text-xs">
                                        <div class="font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('詳細資訊') }}:</div>
                                        <pre class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                @endif
                                
                                @if($showDetails && ($activity->ip_address || $activity->user_agent))
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        @if($activity->ip_address)
                                            <div>{{ __('IP 位址') }}: {{ $activity->ip_address }}</div>
                                        @endif
                                        @if($activity->user_agent)
                                            <div class="truncate">{{ __('使用者代理') }}: {{ $activity->user_agent }}</div>
                                        @endif
                                        <div>{{ __('時間') }}: {{ $activity->detailed_time }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- 載入更多按鈕 -->
            @if($this->recentActivities->count() >= $limit)
                <div class="p-4 text-center border-t border-gray-200 dark:border-gray-700">
                    <button 
                        wire:click="loadMore" 
                        class="btn btn-sm btn-outline-primary"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="loadMore">{{ __('載入更多') }}</span>
                        <span wire:loading wire:target="loadMore">{{ __('載入中...') }}</span>
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        // 監聽活動記錄更新事件
        Livewire.on('activities-refreshed', (event) => {
            if (event.message) {
                console.log('📊 活動記錄已更新:', event.message);
                
                // 顯示成功訊息
                showSuccessMessage(event.message);
            }
        });

        // 監聽篩選清除事件
        Livewire.on('recent-activity-filters-cleared', () => {
            console.log('🗑️ 最近活動篩選已清除');
            
            // 顯示篩選狀態視覺指示器
            const filterIndicators = document.querySelectorAll('.inline-flex.items-center.px-2\\.5.py-0\\.5.rounded-full');
            filterIndicators.forEach(indicator => {
                indicator.style.opacity = '0.5';
                setTimeout(() => {
                    indicator.style.opacity = '1';
                }, 300);
            });
            
            showSuccessMessage('篩選條件已清除');
        });
    });

    function showSuccessMessage(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg z-50';
        successDiv.innerHTML = `
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(successDiv);
        
        setTimeout(() => {
            successDiv.remove();
        }, 3000);
    }
</script>
@endpush