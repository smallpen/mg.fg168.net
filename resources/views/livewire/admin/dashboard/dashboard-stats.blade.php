<div class="space-y-6">
    <!-- 統計卡片網格 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($this->statsCards as $card)
            <div class="card hover:shadow-lg transition-shadow duration-200">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900 rounded-lg flex items-center justify-center">
                                        @switch($card['icon'])
                                            @case('users')
                                                <svg class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                </svg>
                                                @break
                                            @case('user-check')
                                                <svg class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                @break
                                            @case('shield-check')
                                                <svg class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                </svg>
                                                @break
                                            @case('lock-closed')
                                                <svg class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                </svg>
                                                @break
                                            @case('wifi')
                                                <svg class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                                </svg>
                                                @break
                                            @case('user-add')
                                                <svg class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                                </svg>
                                                @break
                                        @endswitch
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['title'] }}</p>
                                    <div class="flex items-baseline">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            {{ number_format($card['value']) }}
                                        </p>
                                        @if(isset($card['percentage']))
                                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                ({{ $card['percentage'] }}%)
                                            </span>
                                        @endif
                                    </div>
                                    @if(isset($card['trend']))
                                        <div class="flex items-center mt-1">
                                            @if($card['trend']['trend'] === 'up')
                                                <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                </svg>
                                                <span class="text-sm text-green-600 dark:text-green-400">
                                                    +{{ $card['trend']['percentage'] }}%
                                                </span>
                                            @else
                                                <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                                </svg>
                                                <span class="text-sm text-red-600 dark:text-red-400">
                                                    {{ $card['trend']['percentage'] }}%
                                                </span>
                                            @endif
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">vs 上月</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(isset($card['description']))
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $card['description'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- 角色分佈圖表 -->
    @if(!empty($stats['role_distribution']))
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">角色分佈</h3>
                <button 
                    wire:click="refreshStats" 
                    class="btn btn-sm btn-outline-primary"
                    wire:loading.attr="disabled"
                >
                    <svg wire:loading.remove class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <svg wire:loading class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    重新整理
                </button>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    @foreach($stats['role_distribution'] as $role)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1">
                                <div class="w-3 h-3 rounded-full bg-primary-500 mr-3"></div>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $role['name'] }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 min-w-[100px]">
                                    <div 
                                        class="bg-primary-600 h-2 rounded-full transition-all duration-300" 
                                        style="width: {{ $role['percentage'] }}%"
                                    ></div>
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400 min-w-[60px] text-right">
                                    {{ $role['count'] }} ({{ $role['percentage'] }}%)
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- 載入狀態 -->
    <div wire:loading.delay class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-900 dark:text-gray-100">載入中...</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // 監聽統計資料更新事件
    window.addEventListener('stats-refreshed', event => {
        // 顯示成功訊息
        if (event.detail.message) {
            // 這裡可以整合通知系統
            console.log(event.detail.message);
        }
    });
</script>
@endpush