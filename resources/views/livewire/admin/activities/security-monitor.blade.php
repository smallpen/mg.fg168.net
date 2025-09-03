<div class="space-y-6" x-data="securityMonitorController()" x-init="init()">
    {{-- 控制項區域 --}}
    <div class="flex justify-end">
        <div class="flex items-center space-x-3">
            <button 
                wire:click="refreshData"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                wire:loading.attr="disabled"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="animate-spin">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span wire:loading.remove>重新整理</span>
                <span wire:loading>更新中...</span>
            </button>
            
            @if(app()->environment('local'))
                <button 
                    wire:click="createTestIncident"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-200"
                    title="建立測試安全事件資料（僅開發環境）"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    建立測試事件
                </button>
            @endif
        </div>
    </div>

    {{-- 安全狀態概覽 --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- 威脅等級 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-{{ $stats['threat_level']['color'] }}-100 dark:bg-{{ $stats['threat_level']['color'] }}-900/20 rounded-md flex items-center justify-center">
                            @if($stats['threat_level']['icon'] === 'check-circle')
                                <svg class="w-5 h-5 text-{{ $stats['threat_level']['color'] }}-600 dark:text-{{ $stats['threat_level']['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @elseif($stats['threat_level']['icon'] === 'exclamation-triangle')
                                <svg class="w-5 h-5 text-{{ $stats['threat_level']['color'] }}-600 dark:text-{{ $stats['threat_level']['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-{{ $stats['threat_level']['color'] }}-600 dark:text-{{ $stats['threat_level']['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">威脅等級</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['threat_level']['label'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 今日安全事件 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/20 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">今日事件</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['today_incidents'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 失敗登入次數 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 dark:bg-red-900/20 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">失敗登入</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['failed_logins'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- 可疑活動 --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/20 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">可疑活動</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['suspicious_activities'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 安全事件列表 --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    安全事件記錄
                </h3>
                
                <div x-show="showResetButton" x-transition>
                    <button 
                        wire:click="resetFilters"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        重置篩選
                    </button>
                </div>
            </div>
            
            {{-- 篩選器 --}}
            <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <select wire:model.live="eventTypeFilter" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach($eventTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                
                <select wire:model.live="severityFilter" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach($severityLevels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                
                <select wire:model.live="statusFilter" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                
                <input 
                    type="date" 
                    wire:model.live="dateFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
            </div>

            {{-- 事件列表 --}}
            @if($incidents->count() > 0)
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    時間
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    事件類型
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    嚴重程度
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    來源 IP
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    使用者
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    狀態
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($incidents as $incident)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $incident->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $incident->severity_color }}">
                                            {{ $incident->event_type_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $incident->severity_color }}">
                                            {{ $incident->severity_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $incident->ip_address ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $incident->user?->username ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $incident->status_color }}">
                                            {{ $incident->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button 
                                                wire:click="showIncidentDetails({{ $incident->id }})"
                                                class="p-1 text-gray-400 hover:text-blue-600 transition-colors duration-200"
                                                title="檢視詳情"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                            
                                            @if(!$incident->resolved)
                                                <button 
                                                    wire:click="showResolveDialog({{ $incident->id }})"
                                                    class="p-1 text-gray-400 hover:text-green-600 transition-colors duration-200"
                                                    title="標記為已處理"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <span class="p-1 text-gray-300 dark:text-gray-600" title="已處理">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- 分頁 --}}
                <div class="mt-4">
                    {{ $incidents->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有安全事件</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($eventTypeFilter !== 'all' || $severityFilter !== 'all' || $statusFilter !== 'all' || $dateFilter)
                            目前篩選條件下沒有找到安全事件
                        @else
                            系統目前沒有記錄到安全事件，這是好消息！
                        @endif
                    </p>
                    @if($eventTypeFilter !== 'all' || $severityFilter !== 'all' || $statusFilter !== 'all' || $dateFilter)
                        <div class="mt-6">
                            <button 
                                wire:click="resetFilters"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                清除篩選條件
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- 最近活動摘要 --}}
    @if($recentActivities->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    最近安全相關活動
                </h3>
                
                <div class="space-y-3">
                    @foreach($recentActivities->take(5) as $activity)
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $activity->description }}
                                    </span>
                                    @if($activity->risk_level > 2)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                            風險等級 {{ $activity->risk_level }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity->user?->username ?? 'System' }} • {{ $activity->ip_address }} • {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="ml-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                    {{ $activity->result === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 
                                       ($activity->result === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') }}">
                                    {{ ucfirst($activity->result) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- 事件詳情模態框 --}}
    @if($showDetailsModal && $selectedIncident)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showDetailsModal') }" x-show="show" x-transition>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" wire:click="closeDetailsModal"></div>

                {{-- 模態框內容 --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    {{-- 標題 --}}
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            安全事件詳情
                        </h3>
                        <button wire:click="closeDetailsModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- 事件基本資訊 --}}
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">事件類型</label>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedIncident->severity_color }}">
                                        {{ $selectedIncident->event_type_label }}
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">嚴重程度</label>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedIncident->severity_color }}">
                                        {{ $selectedIncident->severity_label }}
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">發生時間</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedIncident->created_at->format('Y-m-d H:i:s') }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">來源 IP</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedIncident->ip_address ?? 'N/A' }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">相關使用者</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedIncident->user?->username ?? 'N/A' }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">處理狀態</label>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedIncident->status_color }}">
                                        {{ $selectedIncident->status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- User Agent --}}
                        @if($selectedIncident->user_agent)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">User Agent</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white break-all">{{ $selectedIncident->user_agent }}</p>
                            </div>
                        @endif

                        {{-- 事件資料 --}}
                        @if($selectedIncident->data)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">事件資料</label>
                                <div class="mt-1 bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                                    <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ json_encode($selectedIncident->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        @endif

                        {{-- 處理資訊 --}}
                        @if($selectedIncident->resolved)
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">處理資訊</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">處理者</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedIncident->resolver?->username ?? 'N/A' }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">處理時間</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedIncident->resolved_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                
                                @if($selectedIncident->resolution_notes)
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">處理備註</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedIncident->resolution_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- 操作按鈕 --}}
                    <div class="mt-6 flex justify-end space-x-3">
                        @if(!$selectedIncident->resolved)
                            <button 
                                wire:click="showResolveDialog({{ $selectedIncident->id }})"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                標記為已處理
                            </button>
                        @endif
                        
                        <button 
                            wire:click="closeDetailsModal"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 標記已處理確認對話框 --}}
    @if($showResolveConfirm)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showResolveConfirm') }" x-show="show" x-transition>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" wire:click="cancelResolve"></div>

                {{-- 對話框內容 --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/20 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                標記為已處理
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    確定要將此安全事件標記為已處理嗎？此操作將會記錄處理時間和處理者資訊。
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="confirmResolveIncident"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            確認標記
                        </button>
                        <button 
                            wire:click="cancelResolve"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors duration-200"
                        >
                            取消
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function securityMonitorController() {
    return {
        showResetButton: @js(!empty($eventTypeFilter) && $eventTypeFilter !== 'all' || 
                             !empty($severityFilter) && $severityFilter !== 'all' || 
                             !empty($statusFilter) && $statusFilter !== 'all' || 
                             !empty($dateFilter)),
        
        init() {
            console.log('🔧 安全監控重置按鈕控制器初始化');
            
            // 監聽重置表單元素事件
            Livewire.on('reset-form-elements', () => {
                console.log('🔄 收到重置表單元素事件');
                this.resetFormElements();
            });
            
            this.checkFilters();
            
            // 監聽輸入變化
            document.addEventListener('input', () => {
                setTimeout(() => this.checkFilters(), 100);
            });
            
            document.addEventListener('change', () => {
                setTimeout(() => this.checkFilters(), 100);
            });
            
            // 監聽 Livewire 更新
            Livewire.on('force-ui-update', () => {
                setTimeout(() => {
                    this.showResetButton = false;
                    console.log('🔄 強制隱藏重置按鈕');
                }, 100);
            });
        },
        
        checkFilters() {
            const eventTypeSelect = document.querySelector('select[wire\\:model\\.live="eventTypeFilter"]');
            const severitySelect = document.querySelector('select[wire\\:model\\.live="severityFilter"]');
            const statusSelect = document.querySelector('select[wire\\:model\\.live="statusFilter"]');
            const dateInput = document.querySelector('input[wire\\:model\\.live="dateFilter"]');
            
            const hasEventTypeFilter = eventTypeSelect && eventTypeSelect.value !== 'all';
            const hasSeverityFilter = severitySelect && severitySelect.value !== 'all';
            const hasStatusFilter = statusSelect && statusSelect.value !== 'all';
            const hasDateFilter = dateInput && dateInput.value.trim() !== '';
            
            this.showResetButton = hasEventTypeFilter || hasSeverityFilter || hasStatusFilter || hasDateFilter;
            
            console.log('🔍 檢查安全監控篩選狀態:', {
                hasEventTypeFilter,
                hasSeverityFilter,
                hasStatusFilter,
                hasDateFilter,
                showResetButton: this.showResetButton
            });
        },
        
        resetFormElements() {
            console.log('🔄 開始重置安全監控表單元素');
            
            // 重置事件類型篩選
            const eventTypeSelect = document.querySelector('select[wire\\:model\\.live="eventTypeFilter"]');
            if (eventTypeSelect) {
                eventTypeSelect.value = 'all';
                eventTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            // 重置嚴重程度篩選
            const severitySelect = document.querySelector('select[wire\\:model\\.live="severityFilter"]');
            if (severitySelect) {
                severitySelect.value = 'all';
                severitySelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            // 重置狀態篩選
            const statusSelect = document.querySelector('select[wire\\:model\\.live="statusFilter"]');
            if (statusSelect) {
                statusSelect.value = 'all';
                statusSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            // 重置日期篩選
            const dateInput = document.querySelector('input[wire\\:model\\.live="dateFilter"]');
            if (dateInput) {
                dateInput.value = '';
                dateInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            // 更新重置按鈕狀態
            setTimeout(() => {
                this.checkFilters();
                console.log('✅ 安全監控表單元素重置完成');
            }, 100);
        }
    }
}
</script>

{{-- 標記已處理確認對話框 --}}
@if($showResolveConfirm)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showResolveConfirm') }" x-show="show" x-transition>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- 背景遮罩 --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" wire:click="cancelResolve"></div>

            {{-- 對話框內容 --}}
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/20 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            標記為已處理
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                確定要將此安全事件標記為已處理嗎？此操作將記錄處理時間和處理者資訊。
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button 
                        wire:click="confirmResolveIncident"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        確認標記
                    </button>
                    <button 
                        wire:click="cancelResolve"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                    >
                        取消
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

