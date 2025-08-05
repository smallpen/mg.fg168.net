<div class="space-y-6">
    {{-- 頁面標題和控制項 --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('系統監控') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('最後更新') }}: {{ $lastUpdated }}
            </p>
        </div>
        
        <div class="flex items-center space-x-4">
            {{-- 自動刷新切換 --}}
            <label class="flex items-center">
                <input type="checkbox" 
                       wire:model.live="autoRefresh" 
                       wire:change="toggleAutoRefresh"
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('自動刷新') }}</span>
            </label>
            
            {{-- 刷新間隔選擇 --}}
            <select wire:model.live="refreshInterval" 
                    wire:change="setRefreshInterval($event.target.value)"
                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
                <option value="10">10秒</option>
                <option value="30">30秒</option>
                <option value="60">1分鐘</option>
                <option value="300">5分鐘</option>
            </select>
            
            {{-- 手動刷新按鈕 --}}
            <button wire:click="refreshData" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ __('刷新') }}
            </button>
        </div>
    </div>

    {{-- 系統健康狀態概覽 --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- 整體狀態 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 {{ $this->getStatusColor($healthStatus['overall_status'] ?? 'unknown') }}" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if(($healthStatus['overall_status'] ?? '') === 'healthy')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @elseif(($healthStatus['overall_status'] ?? '') === 'warning')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @endif
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('整體狀態') }}</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ ucfirst($healthStatus['overall_status'] ?? 'Unknown') }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 記憶體使用量 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('記憶體使用量') }}</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $performanceMetrics['memory']['current_mb'] ?? 0 }} MB
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 磁碟使用量 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('磁碟使用量') }}</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $performanceMetrics['disk']['usage_percent'] ?? 0 }}%
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 備份狀態 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('備份數量') }}</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ ($backupStatus['database_count'] ?? 0) + ($backupStatus['files_count'] ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 詳細健康狀態 --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">{{ __('系統組件狀態') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach(($healthStatus['components'] ?? []) as $component => $status)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ ucfirst(str_replace('_', ' ', $component)) }}
                            </h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($status['status']) }}">
                                {{ ucfirst($status['status']) }}
                            </span>
                        </div>
                        
                        @if(isset($status['response_time_ms']))
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ __('回應時間') }}: {{ $status['response_time_ms'] }}ms
                            </p>
                        @endif
                        
                        @if(isset($status['message']))
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                {{ $status['message'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 效能指標詳情 --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 記憶體和 CPU --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">{{ __('系統資源') }}</h3>
                
                <div class="space-y-4">
                    {{-- 記憶體使用量 --}}
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('記憶體使用量') }}</span>
                            <span class="text-gray-900 dark:text-white">
                                {{ $performanceMetrics['memory']['current_mb'] ?? 0 }} MB / 
                                {{ $performanceMetrics['memory']['peak_mb'] ?? 0 }} MB (峰值)
                            </span>
                        </div>
                    </div>

                    {{-- CPU 負載（如果可用） --}}
                    @if(isset($performanceMetrics['cpu_load']))
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('CPU 負載') }}</span>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <div>1分鐘: {{ $performanceMetrics['cpu_load']['1min'] }}</div>
                                <div>5分鐘: {{ $performanceMetrics['cpu_load']['5min'] }}</div>
                                <div>15分鐘: {{ $performanceMetrics['cpu_load']['15min'] }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- 磁碟使用量 --}}
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('磁碟使用量') }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $performanceMetrics['disk']['usage_percent'] ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $performanceMetrics['disk']['usage_percent'] ?? 0 }}%"></div>
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ $performanceMetrics['disk']['free_gb'] ?? 0 }} GB 可用 / {{ $performanceMetrics['disk']['total_gb'] ?? 0 }} GB 總計
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 備份管理 --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">{{ __('備份管理') }}</h3>
                
                <div class="space-y-4">
                    {{-- 備份統計 --}}
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('資料庫備份') }}</span>
                            <div class="text-lg font-medium text-gray-900 dark:text-white">{{ $backupStatus['database_count'] ?? 0 }}</div>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('檔案備份') }}</span>
                            <div class="text-lg font-medium text-gray-900 dark:text-white">{{ $backupStatus['files_count'] ?? 0 }}</div>
                        </div>
                    </div>

                    {{-- 最新備份時間 --}}
                    @if($backupStatus['latest_database'] ?? null)
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('最新資料庫備份') }}: {{ \Carbon\Carbon::parse($backupStatus['latest_database'])->diffForHumans() }}
                        </div>
                    @endif

                    @if($backupStatus['latest_files'] ?? null)
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('最新檔案備份') }}: {{ \Carbon\Carbon::parse($backupStatus['latest_files'])->diffForHumans() }}
                        </div>
                    @endif

                    {{-- 備份操作按鈕 --}}
                    <div class="flex space-x-2">
                        <button wire:click="performBackup('database')" 
                                class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('資料庫備份') }}
                        </button>
                        <button wire:click="performBackup('files')" 
                                class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('檔案備份') }}
                        </button>
                        <button wire:click="performBackup('full')" 
                                class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('完整備份') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 自動刷新腳本 --}}
    @if($autoRefresh)
        <script>
            setInterval(function() {
                @this.call('handleAutoRefresh');
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif
</div>