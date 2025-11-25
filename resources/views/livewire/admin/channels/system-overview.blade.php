<div class="space-y-6">
    <!-- 控制面板 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">系統統計總覽</h2>
            
            <div class="flex items-center space-x-4">
                <!-- 時間範圍選擇 -->
                <select wire:model.live="selectedPeriod" 
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @foreach($periodOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                
                <!-- 自動刷新開關 -->
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="autoRefresh" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">自動刷新</span>
                </label>
                
                <!-- 手動刷新按鈕 -->
                <button wire:click="refreshData" 
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    刷新
                </button>
            </div>
        </div>

        <!-- 統計卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- 代理統計 -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">代理總數</p>
                        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                            {{ number_format($statistics['agents']['total'] ?? 0) }}
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">
                            啟用: {{ number_format($statistics['agents']['active'] ?? 0) }} | 
                            新增: {{ number_format($statistics['agents']['new_period'] ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 玩家統計 -->
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">玩家總數</p>
                        <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                            {{ number_format($statistics['players']['total'] ?? 0) }}
                        </p>
                        <p class="text-xs text-green-600 dark:text-green-400">
                            啟用: {{ number_format($statistics['players']['active'] ?? 0) }} | 
                            新增: {{ number_format($statistics['players']['new_period'] ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 點數統計 -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">系統總點數</p>
                        <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                            {{ number_format($statistics['points']['total_system'] ?? 0) }}
                        </p>
                        <p class="text-xs text-yellow-600 dark:text-yellow-400">
                            交易: {{ number_format($statistics['points']['transactions_period'] ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 系統健康度 -->
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if(count($statistics['health']['data_integrity'] ?? []) === 0)
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">系統健康度</p>
                        <p class="text-2xl font-bold {{ count($statistics['health']['data_integrity'] ?? []) === 0 ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100' }}">
                            {{ count($statistics['health']['data_integrity'] ?? []) === 0 ? '正常' : '異常' }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            問題: {{ count($statistics['health']['data_integrity'] ?? []) + count($statistics['health']['point_consistency'] ?? []) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 詳細統計 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 代理層級分佈 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">代理層級分佈</h3>
            
            @if(!empty($statistics['agents']['by_level']))
                <div class="space-y-3">
                    @foreach($statistics['agents']['by_level'] as $level => $count)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">第 {{ $level }} 層</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" 
                                         style="width: {{ ($count / max($statistics['agents']['by_level'])) * 100 }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $count }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">暫無資料</p>
            @endif
        </div>

        <!-- 前置符號使用情況 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">前置符號使用情況</h3>
            
            <div class="space-y-4">
                <!-- 使用率 -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">使用率</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['prefixes']['usage_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" 
                             style="width: {{ $statistics['prefixes']['usage_rate'] ?? 0 }}%"></div>
                    </div>
                </div>

                <!-- 統計數字 -->
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                        <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $statistics['prefixes']['used'] ?? 0 }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400">已使用</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-3">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $statistics['prefixes']['available'] ?? 0 }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">可用</p>
                    </div>
                </div>

                <!-- 已使用的前置符號 -->
                @if(!empty($statistics['prefixes']['list']))
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">已使用的前置符號：</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($statistics['prefixes']['list'] as $prefix)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ $prefix }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 頂級代理和玩家 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 點數最多的代理 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">點數最多的代理</h3>
            
            @if(!empty($statistics['agents']['top_agents']))
                <div class="space-y-3">
                    @foreach($statistics['agents']['top_agents'] as $agent)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $agent['name'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $agent['account'] }} (第{{ $agent['level'] }}層)</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($agent['total_points']) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">點數</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">暫無資料</p>
            @endif
        </div>

        <!-- 玩家最多的代理 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">玩家最多的代理</h3>
            
            @if(!empty($statistics['players']['by_agent']))
                <div class="space-y-3">
                    @foreach($statistics['players']['by_agent'] as $agent)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $agent['name'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $agent['account'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600 dark:text-green-400">{{ number_format($agent['players_count']) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">玩家</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">暫無資料</p>
            @endif
        </div>
    </div>

    <!-- 快速操作 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">快速操作</h3>
        
        <div class="flex flex-wrap gap-3">
            <button wire:click="exportSystemReport" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                匯出系統報表
            </button>
            
            <button wire:click="runSystemAudit" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                執行系統稽核
            </button>
        </div>
    </div>
</div>