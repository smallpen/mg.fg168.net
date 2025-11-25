<div class="space-y-6" wire:poll.30s="autoRefresh">
    {{-- 載入狀態 --}}
    <div wire:loading.delay class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700">載入中...</span>
        </div>
    </div>

    {{-- 頁面標題和操作 --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">系統監控儀表板</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                通路管理系統健康狀態和監控資訊
            </p>
        </div>
        
        <div class="flex space-x-3">
            <button wire:click="refreshData" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                刷新
            </button>
            
            @can('channels.integrity.check')
            <button wire:click="runIntegrityCheck" 
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                完整性檢查
            </button>
            @endcan
        </div>
    </div>

    {{-- 系統狀態概覽 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- 整體狀態 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">{{ $this->getStatusIcon($dashboardData['status'] ?? 'unknown') }}</span>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">系統狀態</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ strtoupper($dashboardData['status'] ?? 'UNKNOWN') }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 最後檢查時間 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">最後檢查</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $this->formatTime($dashboardData['last_check'] ?? null) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 活躍代理數 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">活躍代理</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $this->formatNumber($dashboardData['metrics']['active_agents_count'] ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 活躍玩家數 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">活躍玩家</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $this->formatNumber($dashboardData['metrics']['active_players_count'] ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 分頁導航 --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('overview')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                系統概覽
            </button>
            <button wire:click="switchTab('metrics')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'metrics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                系統指標
            </button>
            <button wire:click="switchTab('health')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'health' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                健康檢查
            </button>
            <button wire:click="switchTab('alerts')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'alerts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                警報記錄
                @if(count($recentAlerts) > 0)
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ count(array_filter($recentAlerts, fn($alert) => !$alert->resolved)) }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    {{-- 分頁內容 --}}
    <div class="mt-6">
        @if($selectedTab === 'overview')
            {{-- 系統概覽 --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 快速操作 --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">快速操作</h3>
                        <div class="mt-5 space-y-3">
                            @can('channels.monitoring.check')
                            <button wire:click="runMonitoringCheck" 
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                執行監控檢查
                            </button>
                            @endcan
                            
                            @can('channels.auto.repair')
                            <button wire:click="runAutoRepair" 
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                執行自動修復
                            </button>
                            @endcan
                            
                            @can('channels.backup.create')
                            <button wire:click="createBackup" 
                                    class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                建立備份
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- 最近警報 --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">最近警報</h3>
                        <div class="mt-5">
                            @if(empty($recentAlerts))
                                <p class="text-sm text-gray-500 dark:text-gray-400">暫無警報</p>
                            @else
                                <div class="space-y-3">
                                    @foreach(array_slice($recentAlerts, 0, 5) as $alert)
                                        <div class="flex items-start space-x-3 p-3 rounded-lg {{ $alert->resolved ? 'bg-gray-50' : 'bg-red-50' }}">
                                            <span class="text-lg">
                                                @if($alert->severity === 'critical')
                                                    🔴
                                                @elseif($alert->severity === 'warning')
                                                    🟡
                                                @else
                                                    ⚠️
                                                @endif
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 {{ $alert->resolved ? 'line-through' : '' }}">
                                                    {{ $alert->message }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $this->formatTime($alert->created_at) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        @elseif($selectedTab === 'metrics')
            {{-- 系統指標 --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @if(isset($dashboardData['metrics']))
                    @foreach($dashboardData['metrics'] as $key => $value)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                                {{ $this->getMetricLabel($key) }}
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                                {{ $this->formatNumber($value) }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

        @elseif($selectedTab === 'health')
            {{-- 健康檢查 --}}
            <div class="space-y-6">
                @if(isset($systemHealth))
                    @foreach($systemHealth as $category => $health)
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    {{ $this->getHealthCategoryLabel($category) }}
                                </h3>
                                <div class="mt-5">
                                    @if(is_array($health))
                                        @foreach($health as $item => $status)
                                            <div class="flex justify-between items-center py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item }}</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColorClass($status['status'] ?? 'unknown') }}">
                                                    {{ $status['status'] ?? 'unknown' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $health }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

        @elseif($selectedTab === 'alerts')
            {{-- 警報記錄 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">警報記錄</h3>
                    <div class="mt-5">
                        @if(empty($recentAlerts))
                            <p class="text-sm text-gray-500 dark:text-gray-400">暫無警報記錄</p>
                        @else
                            <div class="space-y-4">
                                @foreach($recentAlerts as $alert)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $alert->resolved ? 'bg-gray-50 dark:bg-gray-700' : '' }}">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColorClass($alert->severity) }}">
                                                        {{ $alert->severity }}
                                                    </span>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $alert->type }}
                                                    </span>
                                                    @if($alert->resolved)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            已解決
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="mt-2 text-sm text-gray-900 dark:text-white {{ $alert->resolved ? 'line-through' : '' }}">
                                                    {{ $alert->message }}
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $this->formatTime($alert->created_at) }}
                                                </p>
                                            </div>
                                            @if(!$alert->resolved)
                                                @can('channels.alerts.resolve')
                                                    <button wire:click="resolveAlert({{ $alert->id }})" 
                                                            class="ml-4 inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                        標記已解決
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    // 自動刷新功能
    setInterval(() => {
        $wire.dispatch('auto-refresh');
    }, {{ $refreshInterval * 1000 }});
</script>
@endscript
