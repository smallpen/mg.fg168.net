<div class="space-y-6">
    {{-- 載入中骨架 --}}
    <div wire:loading.delay.longer class="fixed inset-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm">
        <div class="flex items-center justify-center min-h-screen">
            <div class="flex flex-col items-center space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="text-sm text-gray-600 dark:text-gray-400">載入活動記錄中...</p>
            </div>
        </div>
    </div>

    {{-- 操作按鈕 --}}
    <div class="flex justify-end">
        <div class="flex items-center space-x-3">
            {{-- 即時監控切換 --}}
            <button 
                wire:click="toggleRealTime"
                wire:loading.attr="disabled"
                wire:target="toggleRealTime"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="toggleRealTime">
                    @if($realTimeMode)
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                        {{ __('監控中') }}
                    @else
                        <div class="w-2 h-2 bg-gray-400 rounded-full mr-2"></div>
                        {{ __('即時監控') }}
                    @endif
                </span>
                <span wire:loading wire:target="toggleRealTime" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('處理中...') }}
                </span>
            </button>

            {{-- 匯出按鈕 --}}
            <div class="relative inline-block text-left">
                <button 
                    type="button" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    onclick="document.getElementById('export-dropdown').classList.toggle('hidden')"
                >
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                    {{ __('匯出') }}
                    <svg class="-mr-1 ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                <div id="export-dropdown" class="hidden absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="py-1">
                        <a href="{{ route('admin.activities.export') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <svg class="mr-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            進階匯出
                            <span class="ml-auto text-xs text-gray-500">多格式、批量</span>
                        </a>
                        <button 
                            wire:click="$set('showExportModal', true)"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        >
                            <svg class="mr-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            快速匯出
                            <span class="ml-auto text-xs text-gray-500">當前篩選</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 統計分析按鈕 --}}
            <button 
                onclick="window.location.href='{{ route('admin.activities.stats') }}'"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
            >
                <x-heroicon-o-chart-bar class="w-4 h-4 mr-2" />
                {{ __('統計分析') }}
            </button>
        </div>
    </div>

    {{-- 統計摘要 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-document-text class="h-6 w-6 text-gray-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                {{ __('總記錄數') }}
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($stats['total']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-shield-exclamation class="h-6 w-6 text-red-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                {{ __('安全事件') }}
                            </dt>
                            <dd class="text-lg font-medium text-red-600 dark:text-red-400">
                                {{ number_format($stats['security_events']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-orange-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                {{ __('高風險') }}
                            </dt>
                            <dd class="text-lg font-medium text-orange-600 dark:text-orange-400">
                                {{ number_format($stats['high_risk']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-x-circle class="h-6 w-6 text-red-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                {{ __('失敗操作') }}
                            </dt>
                            <dd class="text-lg font-medium text-red-600 dark:text-red-400">
                                {{ number_format($stats['failed']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-users class="h-6 w-6 text-blue-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                {{ __('活躍使用者') }}
                            </dt>
                            <dd class="text-lg font-medium text-blue-600 dark:text-blue-400">
                                {{ number_format($stats['unique_users']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-globe-alt class="h-6 w-6 text-green-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                {{ __('不同 IP') }}
                            </dt>
                            <dd class="text-lg font-medium text-green-600 dark:text-green-400">
                                {{ number_format($stats['unique_ips']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 搜尋和篩選 --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            {{-- 搜尋列 --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 sm:space-x-4">
                <div class="flex-1">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                        </div>
                        <input 
                            wire:model.live.debounce.300ms="search"
                            type="text" 
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="{{ __('搜尋活動記錄...') }}"
                        >
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    {{-- 篩選切換按鈕 --}}
                    <button 
                        wire:click="toggleFilters"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <x-heroicon-o-funnel class="w-4 h-4 mr-2" />
                        {{ __('篩選') }}
                        @if($showFilters)
                            <x-heroicon-o-chevron-up class="w-4 h-4 ml-2" />
                        @else
                            <x-heroicon-o-chevron-down class="w-4 h-4 ml-2" />
                        @endif
                    </button>

                    {{-- 清除篩選 --}}
                    <button 
                        wire:click="clearFilters"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <x-heroicon-o-x-mark class="w-4 h-4 mr-2" />
                        {{ __('清除') }}
                    </button>
                </div>
            </div>

            {{-- 篩選面板 --}}
            @if($showFilters)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- 日期範圍 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('開始日期') }}
                            </label>
                            <input 
                                wire:model.live="dateFrom"
                                type="date" 
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('結束日期') }}
                            </label>
                            <input 
                                wire:model.live="dateTo"
                                type="date" 
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        {{-- 使用者篩選 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('使用者') }}
                            </label>
                            <select 
                                wire:model.live="userFilter"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">{{ __('所有使用者') }}</option>
                                @foreach($filterOptions['users'] as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->username }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 活動類型篩選 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('活動類型') }}
                            </label>
                            <select 
                                wire:model.live="typeFilter"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">{{ __('所有類型') }}</option>
                                @foreach($filterOptions['types'] as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 模組篩選 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('模組') }}
                            </label>
                            <select 
                                wire:model.live="moduleFilter"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">{{ __('所有模組') }}</option>
                                @foreach($filterOptions['modules'] as $module => $label)
                                    <option value="{{ $module }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 結果篩選 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('操作結果') }}
                            </label>
                            <select 
                                wire:model.live="resultFilter"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">{{ __('所有結果') }}</option>
                                @foreach($filterOptions['results'] as $result => $label)
                                    <option value="{{ $result }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 風險等級篩選 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('風險等級') }}
                            </label>
                            <select 
                                wire:model.live="riskLevelFilter"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">{{ __('所有風險等級') }}</option>
                                @foreach($filterOptions['riskLevels'] as $level => $label)
                                    <option value="{{ $level }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- IP 位址篩選 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('IP 位址') }}
                            </label>
                            <input 
                                wire:model.live.debounce.300ms="ipFilter"
                                type="text" 
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="{{ __('輸入 IP 位址...') }}"
                            >
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- 批量操作 --}}
    @if(count($selectedActivities) > 0)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400 mr-2" />
                    <span class="text-sm text-blue-700 dark:text-blue-300">
                        {{ __('已選擇 :count 筆記錄', ['count' => count($selectedActivities)]) }}
                    </span>
                </div>
                
                <div class="flex items-center space-x-3">
                    <select 
                        wire:model="bulkAction"
                        class="px-3 py-1 border border-blue-300 dark:border-blue-600 rounded text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">{{ __('選擇操作') }}</option>
                        <option value="export">{{ __('匯出選中記錄') }}</option>
                        <option value="mark_reviewed">{{ __('標記為已審查') }}</option>
                    </select>
                    
                    <button 
                        wire:click="executeBulkAction"
                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        {{ __('執行') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- 活動記錄表格 --}}
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        {{-- 桌面版表格 --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left">
                            <input 
                                wire:model.live="selectAll"
                                type="checkbox" 
                                class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            >
                        </th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('created_at')">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('時間') }}</span>
                                @if($sortField === 'created_at')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="w-4 h-4" />
                                    @else
                                        <x-heroicon-o-chevron-down class="w-4 h-4" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('user_id')">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('使用者') }}</span>
                                @if($sortField === 'user_id')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="w-4 h-4" />
                                    @else
                                        <x-heroicon-o-chevron-down class="w-4 h-4" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('type')">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('類型') }}</span>
                                @if($sortField === 'type')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="w-4 h-4" />
                                    @else
                                        <x-heroicon-o-chevron-down class="w-4 h-4" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('描述') }}
                        </th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('IP 位址') }}
                        </th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('result')">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('結果') }}</span>
                                @if($sortField === 'result')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="w-4 h-4" />
                                    @else
                                        <x-heroicon-o-chevron-down class="w-4 h-4" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('risk_level')">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('風險') }}</span>
                                @if($sortField === 'risk_level')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="w-4 h-4" />
                                    @else
                                        <x-heroicon-o-chevron-down class="w-4 h-4" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('操作') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($activities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $activity->is_security_event ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input 
                                    wire:model.live="selectedActivities"
                                    type="checkbox" 
                                    value="{{ $activity->id }}"
                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                >
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $activity->created_at->format('H:i:s') }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at->format('Y-m-d') }}</span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($activity->user)
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    {{ substr($activity->user->name, 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $activity->user->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $activity->user->username }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-full bg-gray-400 dark:bg-gray-500 flex items-center justify-center">
                                                    <x-heroicon-o-cog-6-tooth class="h-4 w-4 text-white" />
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                    {{ __('系統') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <x-dynamic-component :component="'heroicon-o-' . $activity->icon" class="h-4 w-4 text-{{ $activity->color }}-500 mr-2" />
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $filterOptions['types'][$activity->type] ?? $activity->type }}
                                    </span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <div class="max-w-xs truncate" title="{{ $activity->description }}">
                                    {{ $activity->description }}
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $activity->ip_address }}
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                @switch($activity->result)
                                    @case('success')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400">
                                            <x-heroicon-o-check-circle class="w-3 h-3 mr-1" />
                                            {{ __('成功') }}
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400">
                                            <x-heroicon-o-x-circle class="w-3 h-3 mr-1" />
                                            {{ __('失敗') }}
                                        </span>
                                        @break
                                    @case('warning')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400">
                                            <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1" />
                                            {{ __('警告') }}
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900/20 text-gray-800 dark:text-gray-400">
                                            {{ $activity->result }}
                                        </span>
                                @endswitch
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $riskLevel = $activity->risk_level ?? 0;
                                    $riskColor = match(true) {
                                        $riskLevel <= 2 => 'green',
                                        $riskLevel <= 5 => 'yellow',
                                        $riskLevel <= 8 => 'orange',
                                        default => 'red'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $riskColor }}-100 dark:bg-{{ $riskColor }}-900/20 text-{{ $riskColor }}-800 dark:text-{{ $riskColor }}-400">
                                    {{ $activity->risk_level_text }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button 
                                    wire:click="viewDetail({{ $activity->id }})"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                >
                                    {{ __('詳情') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <x-heroicon-o-document-text class="h-12 w-12 text-gray-400 mb-4" />
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                        {{ __('找不到符合條件的記錄') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('請嘗試調整搜尋條件或篩選設定') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 行動版卡片列表 --}}
        <div class="lg:hidden">
            @forelse($activities as $activity)
                <div class="border-b border-gray-200 dark:border-gray-700 p-4 {{ $activity->is_security_event ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3 flex-1">
                            {{-- 選擇框 --}}
                            <input 
                                wire:model.live="selectedActivities"
                                type="checkbox" 
                                value="{{ $activity->id }}"
                                class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            >
                            
                            {{-- 使用者頭像 --}}
                            <div class="flex-shrink-0">
                                @if($activity->user)
                                    <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ substr($activity->user->name, 0, 1) }}
                                        </span>
                                    </div>
                                @else
                                    <div class="h-10 w-10 rounded-full bg-gray-400 dark:bg-gray-500 flex items-center justify-center">
                                        <x-heroicon-o-cog-6-tooth class="h-5 w-5 text-white" />
                                    </div>
                                @endif
                            </div>
                            
                            {{-- 活動資訊 --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-1">
                                    <x-dynamic-component :component="'heroicon-o-' . $activity->icon" class="h-4 w-4 text-{{ $activity->color }}-500" />
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $filterOptions['types'][$activity->type] ?? $activity->type }}
                                    </span>
                                    
                                    {{-- 風險等級 --}}
                                    @php
                                        $riskLevel = $activity->risk_level ?? 0;
                                        $riskColor = match(true) {
                                            $riskLevel <= 2 => 'green',
                                            $riskLevel <= 5 => 'yellow',
                                            $riskLevel <= 8 => 'orange',
                                            default => 'red'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $riskColor }}-100 dark:bg-{{ $riskColor }}-900/20 text-{{ $riskColor }}-800 dark:text-{{ $riskColor }}-400">
                                        {{ $activity->risk_level_text }}
                                    </span>
                                </div>
                                
                                <p class="text-sm text-gray-900 dark:text-white mb-2">
                                    {{ $activity->description }}
                                </p>
                                
                                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <x-heroicon-o-clock class="h-3 w-3 mr-1" />
                                        {{ $activity->created_at->format('m/d H:i') }}
                                    </span>
                                    
                                    @if($activity->user)
                                        <span class="flex items-center">
                                            <x-heroicon-o-user class="h-3 w-3 mr-1" />
                                            {{ $activity->user->name }}
                                        </span>
                                    @endif
                                    
                                    <span class="flex items-center">
                                        <x-heroicon-o-globe-alt class="h-3 w-3 mr-1" />
                                        {{ $activity->ip_address }}
                                    </span>
                                    
                                    {{-- 結果狀態 --}}
                                    @switch($activity->result)
                                        @case('success')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400">
                                                <x-heroicon-o-check-circle class="w-3 h-3 mr-1" />
                                                成功
                                            </span>
                                            @break
                                        @case('failed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400">
                                                <x-heroicon-o-x-circle class="w-3 h-3 mr-1" />
                                                失敗
                                            </span>
                                            @break
                                        @case('warning')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400">
                                                <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1" />
                                                警告
                                            </span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                        
                        {{-- 操作按鈕 --}}
                        <button 
                            wire:click="viewDetail({{ $activity->id }})"
                            class="ml-2 text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-sm font-medium"
                        >
                            詳情
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <x-heroicon-o-document-text class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                        {{ __('找不到符合條件的記錄') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('請嘗試調整搜尋條件或篩選設定') }}
                    </p>
                </div>
            @endforelse
        </div>
        
        {{-- 分頁和載入更多 --}}
        @if($activities->hasPages())
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                    <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('顯示') }}
                            <span class="font-medium">{{ $activities->firstItem() }}</span>
                            {{ __('到') }}
                            <span class="font-medium">{{ $activities->lastItem() }}</span>
                            {{ __('筆，共') }}
                            <span class="font-medium">{{ $activities->total() }}</span>
                            {{ __('筆記錄') }}
                        </p>
                        
                        <div class="flex items-center space-x-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">{{ __('每頁顯示') }}:</label>
                            <select 
                                wire:model.live="perPage"
                                class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="25">25 筆</option>
                                <option value="50">50 筆</option>
                                <option value="100">100 筆</option>
                                <option value="200">200 筆</option>
                            </select>
                        </div>

                        {{-- 無限滾動切換 --}}
                        <div class="flex items-center space-x-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">{{ __('載入模式') }}:</label>
                            <button 
                                wire:click="toggleLoadMode"
                                class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                @if($infiniteScroll)
                                    <x-heroicon-o-arrow-down class="w-4 h-4 mr-1" />
                                    {{ __('無限滾動') }}
                                @else
                                    <x-heroicon-o-squares-2x2 class="w-4 h-4 mr-1" />
                                    {{ __('分頁模式') }}
                                @endif
                            </button>
                        </div>
                    </div>
                    
                    {{-- 分頁導航 --}}
                    @if(!$infiniteScroll)
                        <div class="flex justify-center sm:justify-end">
                            {{ $activities->links() }}
                        </div>
                    @endif
                </div>

                {{-- 無限滾動載入更多按鈕 --}}
                @if($infiniteScroll && $activities->hasMorePages())
                    <div class="mt-4 text-center">
                        <button 
                            wire:click="loadMore"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove wire:target="loadMore">
                                <x-heroicon-o-arrow-down class="w-4 h-4 mr-2" />
                                {{ __('載入更多') }}
                            </span>
                            <span wire:loading wire:target="loadMore" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('載入中...') }}
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- 活動詳情元件 --}}
    <livewire:admin.activities.activity-detail />

    {{-- 匯出模態框 --}}
    @if($showExportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showExportModal', false)"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/20 sm:mx-0 sm:h-10 sm:w-10">
                                <x-heroicon-o-arrow-down-tray class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    {{ __('匯出活動記錄') }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('選擇匯出格式，將根據目前的篩選條件匯出活動記錄。') }}
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('匯出格式') }}
                                    </label>
                                    <select 
                                        wire:model="exportFormat"
                                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="csv">CSV (Excel 相容)</option>
                                        <option value="json">JSON (程式處理)</option>
                                        <option value="pdf">PDF (報告列印)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="exportActivities"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ __('開始匯出') }}
                        </button>
                        <button 
                            wire:click="$set('showExportModal', false)"
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ __('取消') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>