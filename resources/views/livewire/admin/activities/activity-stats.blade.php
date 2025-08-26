<div class="space-y-6">
    {{-- 控制項 --}}
    <div class="flex justify-end">
        <div class="flex flex-col sm:flex-row gap-3">
            {{-- 時間範圍選擇 --}}
            <div class="flex items-center space-x-2">
                <label for="timeRange" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    時間範圍:
                </label>
                <select wire:model.live="timeRange" 
                        id="timeRange"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($this->timeRangeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- 圖表類型選擇 --}}
            <div class="flex items-center space-x-2">
                <label for="chartType" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    圖表類型:
                </label>
                <select wire:model.live="chartType" 
                        id="chartType"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($this->chartTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- 操作按鈕 --}}
            <div class="flex space-x-2">
                <button wire:click="toggleAutoRefresh" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    @if($autoRefresh)
                        <x-heroicon-s-pause class="w-4 h-4 mr-1"/>
                        停止自動更新
                    @else
                        <x-heroicon-s-play class="w-4 h-4 mr-1"/>
                        自動更新
                    @endif
                </button>
                
                <button wire:click="refreshStats" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <x-heroicon-s-arrow-path class="w-4 h-4 mr-1"/>
                    重新整理
                </button>
                
                <button wire:click="exportStats" 
                        class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <x-heroicon-s-arrow-down-tray class="w-4 h-4 mr-1"/>
                    匯出報告
                </button>
            </div>
        </div>
    </div>

    {{-- 載入指示器 --}}
    <div wire:loading class="flex items-center justify-center py-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">載入統計資料中...</span>
    </div>

    {{-- 綜合統計卡片 --}}
    <div wire:loading.remove class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php $stats = $this->overallStats @endphp
        
        {{-- 總活動數 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-chart-bar class="h-6 w-6 text-blue-600"/>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                總活動數
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($stats['total_activities']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        平均每日: {{ number_format($stats['average_daily'], 1) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 活躍使用者 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-users class="h-6 w-6 text-green-600"/>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                活躍使用者
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($stats['unique_users']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        高峰時段: {{ sprintf('%02d:00', $stats['peak_hour']) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 安全事件 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-shield-exclamation class="h-6 w-6 text-red-600"/>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                安全事件
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format(is_array($stats['security_events']) ? count($stats['security_events']) : $stats['security_events']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        高風險: {{ number_format($stats['high_risk_activities']) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 成功率 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-check-circle class="h-6 w-6 text-emerald-600"/>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                成功率
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($stats['success_rate'], 1) }}%
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-emerald-600 h-2 rounded-full" 
                             style="width: {{ $stats['success_rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 主要圖表區域 --}}
    <div wire:loading.remove class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 活動趨勢圖 --}}
        @if($chartType === 'timeline')
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">活動趨勢圖</h3>
                    <div class="flex items-center space-x-3">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $this->timeRangeOptions[$timeRange] }}
                        </div>
                        <button wire:click="exportChartData('timeline')" 
                                class="text-sm text-blue-600 hover:text-blue-700">
                            <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
                
                {{-- 趨勢分析 --}}
                @php $trendAnalysis = $this->trendAnalysis @endphp
                @if($trendAnalysis['trend'] !== 'insufficient_data')
                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-2">
                                @if($trendAnalysis['trend'] === 'increasing')
                                    <x-heroicon-s-arrow-trending-up class="w-4 h-4 text-green-600"/>
                                    <span class="text-green-600 font-medium">上升趨勢</span>
                                @elseif($trendAnalysis['trend'] === 'decreasing')
                                    <x-heroicon-s-arrow-trending-down class="w-4 h-4 text-red-600"/>
                                    <span class="text-red-600 font-medium">下降趨勢</span>
                                @else
                                    <x-heroicon-s-minus class="w-4 h-4 text-gray-600"/>
                                    <span class="text-gray-600 font-medium">穩定趨勢</span>
                                @endif
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                變化: {{ $trendAnalysis['change_percentage'] > 0 ? '+' : '' }}{{ $trendAnalysis['change_percentage'] }}%
                                @if($trendAnalysis['prediction'])
                                    | 預測下期: {{ number_format($trendAnalysis['prediction']) }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
                <div class="h-64">
                    @php $timelineData = $this->timelineData @endphp
                    @if(count($timelineData) > 0)
                        <canvas id="timelineChart" class="w-full h-full"></canvas>
                    @else
                        <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                            此時間範圍內沒有活動資料
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- 分佈圖 --}}
        @if($chartType === 'distribution')
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">活動類型分佈</h3>
                    <button wire:click="exportChartData('distribution')" 
                            class="text-sm text-blue-600 hover:text-blue-700">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                    </button>
                </div>
                
                @php $distributionData = $this->distributionData @endphp
                @if(count($distributionData['type_distribution']) > 0)
                    <div class="space-y-3">
                        @foreach($distributionData['type_distribution'] as $item)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full" 
                                         style="background-color: {{ $item['color'] }}"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $item['type'] }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item['count'] }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ $item['percentage'] }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full" 
                                     style="width: {{ $item['percentage'] }}%; background-color: {{ $item['color'] }}"></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        沒有活動類型資料
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">模組分佈</h3>
                    <button wire:click="exportChartData('distribution')" 
                            class="text-sm text-blue-600 hover:text-blue-700">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                    </button>
                </div>
                
                @if(count($distributionData['module_distribution']) > 0)
                    <div class="space-y-3">
                        @foreach($distributionData['module_distribution'] as $item)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full" 
                                         style="background-color: {{ $item['color'] }}"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $item['module'] }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item['count'] }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ $item['percentage'] }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full" 
                                     style="width: {{ $item['percentage'] }}%; background-color: {{ $item['color'] }}"></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        沒有模組資料
                    </div>
                @endif
            </div>
        @endif

        {{-- 熱力圖 --}}
        @if($chartType === 'heatmap')
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">24小時活動熱力圖</h3>
                    <button wire:click="exportChartData('heatmap')" 
                            class="text-sm text-blue-600 hover:text-blue-700">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                    </button>
                </div>
                
                @php $hourlyData = $this->distributionData['hourly_distribution'] @endphp
                <div class="grid grid-cols-12 gap-1">
                    @foreach($hourlyData as $hour => $data)
                        <div class="relative group">
                            <div class="w-full h-8 rounded border border-gray-200 dark:border-gray-600"
                                 style="background-color: rgba(59, 130, 246, {{ $data['intensity'] }})">
                            </div>
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                {{ $data['formatted_hour'] }}<br>
                                {{ $data['count'] }} 次活動
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>00:00</span>
                    <span>06:00</span>
                    <span>12:00</span>
                    <span>18:00</span>
                    <span>23:00</span>
                </div>
            </div>
        @endif

        {{-- 對比圖 --}}
        @if($chartType === 'comparison')
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">時間範圍對比</h3>
                    <button wire:click="exportChartData('comparison')" 
                            class="text-sm text-blue-600 hover:text-blue-700">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                    </button>
                </div>
                
                @php 
                    $comparisonRanges = ['7d', '30d', '90d'];
                    $comparisonData = $this->compareTimeRanges($comparisonRanges);
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($comparisonData as $range => $data)
                        <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg {{ $range === $timeRange ? 'ring-2 ring-blue-500' : '' }}">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                {{ $this->timeRangeOptions[$range] ?? $range }}
                                @if($range === $timeRange)
                                    <span class="ml-2 text-xs text-blue-600">(目前)</span>
                                @endif
                            </h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-xs text-gray-600 dark:text-gray-400">總活動</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($data['total_activities']) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-xs text-gray-600 dark:text-gray-400">活躍使用者</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($data['unique_users']) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-xs text-gray-600 dark:text-gray-400">安全事件</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format(is_array($data['security_events']) ? count($data['security_events']) : $data['security_events']) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-xs text-gray-600 dark:text-gray-400">成功率</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($data['success_rate'], 1) }}%
                                    </dd>
                                </div>
                            </dl>
                            @if($range !== $timeRange)
                                <button wire:click="updateTimeRange('{{ $range }}')" 
                                        class="mt-3 w-full text-xs text-blue-600 hover:text-blue-700 border border-blue-600 rounded px-2 py-1">
                                    切換到此範圍
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- 使用者排行榜和安全事件 --}}
    <div wire:loading.remove class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 最活躍使用者排行榜 --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">最活躍使用者</h3>
                    <button wire:click="exportChartData('top_users')" 
                            class="text-sm text-blue-600 hover:text-blue-700">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                    </button>
                </div>
            </div>
            <div class="p-6">
                @php $topUsers = $this->topUsers @endphp
                @if($topUsers->count() > 0)
                    <div class="space-y-4">
                        @foreach($topUsers as $index => $userActivity)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                                {{ $index + 1 }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $userActivity->user->name ?? '未知使用者' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            @{{ $userActivity->user->username ?? 'unknown' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($userActivity->activity_count) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        次活動
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        沒有使用者活動資料
                    </div>
                @endif
            </div>
        </div>

        {{-- 安全事件統計 --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">安全事件</h3>
                    <button wire:click="exportChartData('security_events')" 
                            class="text-sm text-blue-600 hover:text-blue-700">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                    </button>
                </div>
            </div>
            <div class="p-6">
                @php $securityEvents = $this->securityEvents @endphp
                @if($securityEvents->count() > 0)
                    <div class="space-y-4">
                        @foreach($securityEvents->take(10) as $event)
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    @if($event->risk_level >= 7)
                                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-500"/>
                                    @elseif($event->risk_level >= 4)
                                        <x-heroicon-s-exclamation-circle class="w-5 h-5 text-yellow-500"/>
                                    @else
                                        <x-heroicon-s-information-circle class="w-5 h-5 text-blue-500"/>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $event->description }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $event->user->name ?? '系統' }} • 
                                        {{ $event->created_at->diffForHumans() }} •
                                        風險等級: {{ $event->risk_level_text }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($securityEvents->count() > 10)
                            <div class="text-center pt-4">
                                <a href="{{ route('admin.activities.index', ['security_events_only' => true]) }}" 
                                   class="text-sm text-blue-600 hover:text-blue-500">
                                    查看全部 {{ $securityEvents->count() }} 個安全事件
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        <x-heroicon-o-shield-check class="w-12 h-12 mx-auto mb-2"/>
                        <div>沒有安全事件</div>
                        <div class="text-xs">系統運行正常</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 詳細統計（可摺疊） --}}
    @if($showDetailedStats)
        <div wire:loading.remove class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">詳細統計資料</h3>
                    <button wire:click="toggleDetailedStats" 
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <x-heroicon-s-chevron-up class="w-5 h-5"/>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">時間統計</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">最活躍日期</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $stats['most_active_day'] ? Carbon\Carbon::parse($stats['most_active_day'])->format('m/d') : '無' }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">高峰時段</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ sprintf('%02d:00', $stats['peak_hour']) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">活動統計</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">平均每日活動</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($stats['average_daily'], 1) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">成功率</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($stats['success_rate'], 1) }}%
                                </dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">安全統計</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">安全事件</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format(is_array($stats['security_events']) ? count($stats['security_events']) : $stats['security_events']) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">高風險活動</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($stats['high_risk_activities']) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center">
            <button wire:click="toggleDetailedStats" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <x-heroicon-s-chevron-down class="w-4 h-4 mr-2"/>
                顯示詳細統計
            </button>
        </div>
    @endif
</div>

{{-- JavaScript for Charts --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:initialized', function () {
    let timelineChart = null;
    
    function initTimelineChart() {
        const canvas = document.getElementById('timelineChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const timelineData = @json($this->timelineData ?? []);
        
        if (timelineChart) {
            timelineChart.destroy();
        }
        
        timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timelineData.map(item => item.formatted_date),
                datasets: [{
                    label: '活動數量',
                    data: timelineData.map(item => item.count),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // 初始化圖表
    setTimeout(initTimelineChart, 100);
    
    // 監聽統計更新事件
    Livewire.on('stats-updated', () => {
        setTimeout(initTimelineChart, 100);
    });
    
    Livewire.on('stats-refreshed', () => {
        setTimeout(initTimelineChart, 100);
    });
    
    // 自動重新整理功能
    let autoRefreshInterval = null;
    
    Livewire.on('start-auto-refresh', (event) => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        autoRefreshInterval = setInterval(() => {
            @this.refreshStats();
        }, event.interval * 1000);
    });
    
    Livewire.on('stop-auto-refresh', () => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });
    
    // 下載檔案功能
    Livewire.on('download-file', (event) => {
        const link = document.createElement('a');
        link.href = event.url;
        link.download = event.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
@endpush