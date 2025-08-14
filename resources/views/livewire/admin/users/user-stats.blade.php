<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 sm:p-6">
    {{-- 標題和控制按鈕 --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ __('admin.users.statistics') }}
        </h3>
        <div class="flex items-center space-x-2">
            <button wire:click="toggleDetails" 
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors duration-200">
                @if($showDetails)
                    {{ __('admin.users.hide_details') }}
                @else
                    {{ __('admin.users.show_details') }}
                @endif
            </button>
            <button wire:click="refreshStats" 
                    wire:loading.attr="disabled"
                    wire:target="refreshStats"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors duration-200 disabled:opacity-50">
                {{ __('admin.users.refresh') }}
            </button>
        </div>
    </div>

    {{-- 載入狀態 --}}
    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <div class="flex items-center space-x-2">
                <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.users.loading_stats') }}</span>
            </div>
        </div>
    @else
        {{-- 統計卡片 --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($statsCards as $card)
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400">
                        {{ number_format($card['value']) }}
                    </div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $card['title'] }}
                    </div>
                    @if($showDetails)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $card['description'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- 詳細統計資訊 --}}
        @if($showDetails)
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 活躍率 --}}
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ __('admin.users.activity_rate') }}
                    </h4>
                    <div class="flex items-center justify-between">
                        <div class="text-3xl font-bold {{ $activityRateColor }}">
                            {{ $activityRate }}
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('admin.users.active_vs_total') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-500">
                                {{ number_format($stats['active_users'] ?? 0) }} / {{ number_format($stats['total_users'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 最受歡迎角色 --}}
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ __('admin.users.top_role') }}
                    </h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $topRole['name'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('admin.users.users_count', ['count' => $topRole['count']]) }}
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($topRole['count']) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 無角色使用者提醒 --}}
            @if(($stats['users_without_roles'] ?? 0) > 0)
                <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div>
                            <div class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                {{ __('admin.users.users_without_roles_warning') }}
                            </div>
                            <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                {{ __('admin.users.users_without_roles_count', ['count' => $stats['users_without_roles']]) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif
</div>