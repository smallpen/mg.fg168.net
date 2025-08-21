<div class="space-y-6">
    {{-- 標題和控制項 --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                @if($mode === 'role' && $role)
                    {{ $role->display_name }} - 統計資訊
                @else
                    角色管理統計
                @endif
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @if($mode === 'role')
                    檢視角色的詳細統計資訊和權限分佈
                @else
                    檢視系統整體角色統計和使用趨勢
                @endif
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- 模式切換 --}}
            @if($role)
                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-700 p-1">
                    <button 
                        wire:click="switchMode('role')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                               {{ $mode === 'role' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                        角色統計
                    </button>
                    <button 
                        wire:click="switchMode('system')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                               {{ $mode === 'system' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                        系統統計
                    </button>
                </div>
            @endif

            {{-- 趨勢天數選擇 --}}
            @if($mode === 'system')
                <select 
                    wire:model.live="trendDays"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="7">7天</option>
                    <option value="30">30天</option>
                    <option value="90">90天</option>
                </select>
            @endif

            {{-- 自動重新整理 --}}
            <button 
                wire:click="toggleAutoRefresh"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border transition-colors
                       {{ $autoRefresh ? 'border-green-300 bg-green-50 text-green-700 dark:border-green-600 dark:bg-green-900/20 dark:text-green-400' : 'border-gray-300 bg-white text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300' }}
                       hover:bg-gray-50 dark:hover:bg-gray-600">
                <x-heroicon-o-arrow-path class="w-4 h-4 mr-1.5 {{ $autoRefresh ? 'animate-spin' : '' }}" />
                {{ $autoRefresh ? '自動更新中' : '自動更新' }}
            </button>

            {{-- 重新整理按鈕 --}}
            <button 
                wire:click="refreshStatistics"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                <x-heroicon-o-arrow-path class="w-4 h-4 mr-1.5" wire:loading.class="animate-spin" />
                重新整理
            </button>

            {{-- 清除快取 --}}
            <button 
                wire:click="clearCacheAndRefresh"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                <x-heroicon-o-trash class="w-4 h-4 mr-1.5" />
                清除快取
            </button>
        </div>
    </div>

    {{-- 載入狀態 --}}
    <div wire:loading.delay class="flex items-center justify-center py-8">
        <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin" />
            <span>載入統計資料中...</span>
        </div>
    </div>

    {{-- 統計卡片 --}}
    <div wire:loading.remove class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($this->statisticsCards as $card)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/20 rounded-md flex items-center justify-center">
                                @switch($card['icon'])
                                    @case('users')
                                        <x-heroicon-o-users class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                    @case('key')
                                        <x-heroicon-o-key class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                    @case('arrow-down')
                                        <x-heroicon-o-arrow-down class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                    @case('shield-check')
                                        <x-heroicon-o-shield-check class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                    @case('user-group')
                                        <x-heroicon-o-user-group class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                    @case('check-circle')
                                        <x-heroicon-o-check-circle class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                    @case('cog')
                                        <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                    @case('plus-circle')
                                        <x-heroicon-o-plus-circle class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" />
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ $card['title'] }}
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ number_format($card['value']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 主要內容區域 --}}
    <div wire:loading.remove class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 權限分佈圖表 --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">權限分佈</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    @if($mode === 'role')
                        此角色的權限按模組分佈情況
                    @else
                        系統整體權限分佈情況
                    @endif
                </p>
            </div>
            <div class="p-6">
                @if(!empty($permissionDistribution['by_module']))
                    <div class="h-64 mb-4">
                        <canvas 
                            id="permissionChart"
                            x-data="chartComponent"
                            x-init="initChart('permissionChart', 'doughnut', @js($this->permissionChartData), @js($this->chartConfig))"
                            class="w-full h-full">
                        </canvas>
                    </div>
                    
                    {{-- 權限模組列表 --}}
                    <div class="space-y-2">
                        @foreach($permissionDistribution['by_module'] as $module)
                            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $module['module'] }}
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $module['count'] }}/{{ $module['total_in_module'] }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                        {{ $module['percentage'] }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-chart-pie class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有權限資料</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">目前沒有可顯示的權限分佈資料</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- 角色詳細資訊或使用趨勢 --}}
        @if($mode === 'role' && $role)
            {{-- 角色詳細資訊 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">角色詳細資訊</h3>
                </div>
                <div class="p-6">
                    @if(!empty($statistics))
                        <dl class="space-y-4">
                            {{-- 基本資訊 --}}
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">角色名稱</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">顯示名稱</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->display_name }}</dd>
                            </div>
                            @if($role->description)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">描述</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->description }}</dd>
                                </div>
                            @endif
                            
                            {{-- 層級資訊 --}}
                            @if(!empty($statistics['hierarchy']))
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">層級路徑</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $statistics['hierarchy']['hierarchy_path'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">層級深度</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $statistics['hierarchy']['depth'] }}</dd>
                                </div>
                                @if($statistics['hierarchy']['children_count'] > 0)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">子角色數量</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $statistics['hierarchy']['children_count'] }}</dd>
                                    </div>
                                @endif
                            @endif

                            {{-- 狀態標籤 --}}
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">狀態</dt>
                                <dd class="mt-1 flex space-x-2">
                                    @if($role->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                            啟用
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                            停用
                                        </span>
                                    @endif
                                    @if($role->is_system_role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                            系統角色
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            {{-- 時間資訊 --}}
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">建立時間</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->formatted_created_at }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">最後更新</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->formatted_updated_at }}</dd>
                            </div>
                        </dl>

                        {{-- 最近的使用者指派 --}}
                        @if(!empty($statistics['users']['recent_assignments']))
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">最近指派的使用者</h4>
                                <div class="space-y-2">
                                    @foreach($statistics['users']['recent_assignments'] as $assignment)
                                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                            <div>
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $assignment['name'] }}</span>
                                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ $assignment['username'] }})</span>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($assignment['assigned_at'])->diffForHumans() }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @elseif($mode === 'system')
            {{-- 使用趨勢圖表 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">使用趨勢</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        過去 {{ $trendDays }} 天的角色建立和指派趨勢
                    </p>
                </div>
                <div class="p-6">
                    @if(!empty($usageTrends))
                        <div class="h-64">
                            <canvas 
                                id="trendChart"
                                x-data="chartComponent"
                                x-init="initChart('trendChart', 'line', @js($this->usageTrendChartData), @js($this->chartConfig))"
                                class="w-full h-full">
                            </canvas>
                        </div>
                        
                        {{-- 趨勢摘要 --}}
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $usageTrends['role_creations']['total'] }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">新增角色</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $usageTrends['role_assignments']['total'] }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">角色指派</div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有趨勢資料</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">目前沒有可顯示的使用趨勢資料</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- 系統統計詳細資訊 --}}
    @if($mode === 'system' && !empty($systemStatistics))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">系統統計詳情</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- 層級統計 --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">層級結構</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">根角色</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemStatistics['hierarchy']['root_roles'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">子角色</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemStatistics['hierarchy']['child_roles'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">最大深度</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemStatistics['hierarchy']['max_depth'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">平均深度</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemStatistics['hierarchy']['avg_depth'] }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- 權限統計 --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">權限統計</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">總權限數</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemStatistics['permissions']['total_permissions'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">平均權限/角色</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemStatistics['permissions']['avg_permissions_per_role'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">權限覆蓋率</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemStatistics['permissions']['permission_coverage']['usage_percentage'] }}%</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- 熱門角色 --}}
                @if(!empty($systemStatistics['top_roles']['most_used']))
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">最常使用的角色</h4>
                        <div class="space-y-2">
                            @foreach($systemStatistics['top_roles']['most_used'] as $role)
                                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $role['name'] }}</span>
                                        @if($role['is_system_role'])
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                系統
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $role['users_count'] }} 使用者</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- Chart.js 整合 --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chartComponent', () => ({
        charts: {},
        
        initChart(canvasId, type, data, options) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            
            // 銷毀現有圖表
            if (this.charts[canvasId]) {
                this.charts[canvasId].destroy();
            }
            
            // 建立新圖表
            this.charts[canvasId] = new Chart(ctx, {
                type: type,
                data: data,
                options: options
            });
        },
        
        updateChart(canvasId, data) {
            if (this.charts[canvasId]) {
                this.charts[canvasId].data = data;
                this.charts[canvasId].update();
            }
        }
    }));
});

// 自動重新整理功能
let autoRefreshInterval;

document.addEventListener('livewire:init', () => {
    Livewire.on('start-auto-refresh', (event) => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        autoRefreshInterval = setInterval(() => {
            Livewire.dispatch('auto-refresh-tick');
        }, event.interval * 1000);
    });
    
    Livewire.on('stop-auto-refresh', () => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });
});
</script>
@endpush