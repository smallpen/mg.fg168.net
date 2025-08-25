<div class="space-y-6">
    {{-- 監控控制面板 --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-900">即時活動監控</h2>
                    <div class="flex items-center space-x-2">
                        @if($isMonitoring)
                            <div class="flex items-center space-x-2 text-green-600">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium">監控中</span>
                            </div>
                        @else
                            <div class="flex items-center space-x-2 text-gray-500">
                                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                <span class="text-sm font-medium">已停止</span>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    {{-- 警報計數 --}}
                    @if($alertCount > 0)
                        <div class="flex items-center space-x-2 text-red-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium">{{ $alertCount }} 個警報</span>
                        </div>
                    @endif
                    
                    {{-- 控制按鈕 --}}
                    <button 
                        wire:click="toggleMonitoring"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white {{ $isMonitoring ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        @if($isMonitoring)
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                            </svg>
                            停止監控
                        @else
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-9 4h10a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            開始監控
                        @endif
                    </button>
                    
                    <button 
                        wire:click="refreshMonitorData"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        {{-- 統計摘要 --}}
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-600">今日活動</p>
                            <p class="text-2xl font-semibold text-blue-900">{{ number_format($todayStats['total_activities']) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-600">安全事件</p>
                            <p class="text-2xl font-semibold text-yellow-900">{{ number_format($todayStats['security_events']) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-red-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 4.828A4 4 0 015.5 4H9v1H5.5a3 3 0 00-2.121.879l-.707.707A1 1 0 002 7.414V11H1V7.414a2 2 0 01.586-1.414l.707-.707A5 5 0 015.5 3H9a1 1 0 011 1v5.586l4.707-4.707A1 1 0 0116 4h3a1 1 0 011 1v3a1 1 0 01-.293.707L15 13.414V9a1 1 0 01-1-1H9V7h5a2 2 0 012 2v4.586l4.707 4.707A1 1 0 0121 19v3a1 1 0 01-1 1h-3a1 1 0 01-.707-.293L11.586 18H7a1 1 0 01-1-1v-3a1 1 0 01.293-.707L10.586 9H7a2 2 0 01-2-2V4.828z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-600">警報</p>
                            <p class="text-2xl font-semibold text-red-900">{{ number_format($todayStats['alerts']) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-600">活躍使用者</p>
                            <p class="text-2xl font-semibold text-green-900">{{ number_format($todayStats['unique_users']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 監控規則管理 --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">監控規則</h3>
                    <button 
                        wire:click="$set('showRuleModal', true)"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        新增規則
                    </button>
                </div>
            </div>
            
            <div class="px-6 py-4">
                @if(empty($monitorRules))
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">尚無監控規則</h3>
                        <p class="mt-1 text-sm text-gray-500">開始建立您的第一個監控規則</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($monitorRules as $rule)
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg {{ $rule['is_active'] ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h4 class="text-sm font-medium text-gray-900">{{ $rule['name'] }}</h4>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $rule['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $rule['is_active'] ? '啟用' : '停用' }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            優先級 {{ $rule['priority'] }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ $rule['description'] }}</p>
                                    @if($rule['triggered_count'] > 0)
                                        <p class="text-xs text-gray-500 mt-1">
                                            觸發 {{ $rule['triggered_count'] }} 次
                                            @if($rule['last_triggered'])
                                                ，最後觸發：{{ $rule['last_triggered'] }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <button 
                                        wire:click="toggleRule({{ $rule['id'] }})"
                                        class="text-sm text-indigo-600 hover:text-indigo-900"
                                    >
                                        {{ $rule['is_active'] ? '停用' : '啟用' }}
                                    </button>
                                    <button 
                                        wire:click="removeRule({{ $rule['id'] }})"
                                        wire:confirm="確定要刪除此監控規則嗎？"
                                        class="text-sm text-red-600 hover:text-red-900"
                                    >
                                        刪除
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        
        {{-- 最新警報 --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">最新警報</h3>
            </div>
            
            <div class="px-6 py-4">
                @if(empty($recentAlerts))
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">目前沒有警報</h3>
                        <p class="mt-1 text-sm text-gray-500">系統運行正常</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($recentAlerts as $alert)
                            <div class="border border-gray-200 rounded-lg p-4 {{ $alert['is_acknowledged'] ? 'bg-gray-50' : 'bg-red-50 border-red-200' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            @if($alert['severity'] === 'high')
                                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                            @elseif($alert['severity'] === 'medium')
                                                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                            
                                            <h4 class="text-sm font-medium text-gray-900">{{ $alert['title'] }}</h4>
                                            <span class="text-xs text-gray-500">{{ $alert['created_at'] }}</span>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 mt-1">{{ $alert['description'] }}</p>
                                        
                                        @if($alert['causer_name'])
                                            <p class="text-xs text-gray-500 mt-1">使用者：{{ $alert['causer_name'] }}</p>
                                        @endif
                                    </div>
                                    
                                    @if(!$alert['is_acknowledged'])
                                        <div class="flex items-center space-x-2 ml-4">
                                            <button 
                                                wire:click="viewAlertDetail({{ $alert['id'] }})"
                                                class="text-xs text-indigo-600 hover:text-indigo-900"
                                            >
                                                詳情
                                            </button>
                                            <button 
                                                wire:click="acknowledgeAlert({{ $alert['id'] }})"
                                                class="text-xs text-green-600 hover:text-green-900"
                                            >
                                                確認
                                            </button>
                                            <button 
                                                wire:click="ignoreAlert({{ $alert['id'] }})"
                                                class="text-xs text-gray-600 hover:text-gray-900"
                                            >
                                                忽略
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    {{-- 活動頻率圖表 --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">活動頻率監控（過去 10 分鐘）</h3>
        </div>
        
        <div class="px-6 py-4">
            <div class="flex items-end space-x-2 h-32">
                @if(!empty($activityFrequency) && is_array($activityFrequency))
                    @php
                        $maxCount = max(array_column($activityFrequency, 'count'));
                        $maxCount = $maxCount > 0 ? $maxCount : 1;
                    @endphp
                    @foreach($activityFrequency as $interval)
                        <div class="flex-1 flex flex-col items-center">
                            <div 
                                class="w-full {{ $interval['is_abnormal'] ? 'bg-red-500' : 'bg-blue-500' }} rounded-t"
                                style="height: {{ max(($interval['count'] / $maxCount) * 100, 5) }}%"
                            ></div>
                            <div class="text-xs text-gray-600 mt-1">{{ $interval['time'] }}</div>
                            <div class="text-xs text-gray-500">{{ $interval['count'] }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="flex-1 text-center text-gray-500">
                        <p>暫無活動頻率資料</p>
                    </div>
                @endif
            </div>
            
            <div class="flex items-center justify-between mt-4 text-sm text-gray-600">
                <span>正常閾值：{{ $normalFrequencyThreshold }} 次/分鐘</span>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-blue-500 rounded"></div>
                        <span>正常</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-red-500 rounded"></div>
                        <span>異常</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- 最新活動記錄 --}}
    @if($isMonitoring)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">最新活動記錄</h3>
            </div>
            
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">時間</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">使用者</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">類型</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">描述</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">風險等級</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->recentActivities as $activity)
                            <tr class="{{ $activity->risk_level > 3 ? 'bg-red-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $activity->created_at->format('H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $activity->user?->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $activity->type }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ Str::limit($activity->description, 50) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($activity->risk_level <= 2)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            低
                                        </span>
                                    @elseif($activity->risk_level <= 4)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            中
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            高
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    
    {{-- 新增規則模態框 --}}
    @if($showRuleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showRuleModal', false)"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="addRule">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">新增監控規則</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="rule-name" class="block text-sm font-medium text-gray-700">規則名稱</label>
                                    <input 
                                        type="text" 
                                        id="rule-name"
                                        wire:model="newRule.name"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="例如：登入失敗監控"
                                    >
                                    @error('newRule.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="rule-description" class="block text-sm font-medium text-gray-700">規則描述</label>
                                    <textarea 
                                        id="rule-description"
                                        wire:model="newRule.description"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="描述此規則的用途和觸發條件"
                                    ></textarea>
                                    @error('newRule.description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label for="rule-priority" class="block text-sm font-medium text-gray-700">優先級</label>
                                    <select 
                                        id="rule-priority"
                                        wire:model="newRule.priority"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                        <option value="1">1 - 最低</option>
                                        <option value="2">2 - 低</option>
                                        <option value="3">3 - 中</option>
                                        <option value="4">4 - 高</option>
                                        <option value="5">5 - 最高</option>
                                    </select>
                                    @error('newRule.priority') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="flex items-center">
                                    <input 
                                        id="rule-active"
                                        type="checkbox" 
                                        wire:model="newRule.is_active"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                    <label for="rule-active" class="ml-2 block text-sm text-gray-900">
                                        立即啟用此規則
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                新增規則
                            </button>
                            <button 
                                type="button"
                                wire:click="$set('showRuleModal', false)"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                取消
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    {{-- 警報詳情模態框 --}}
    @if($showAlertDetail && $selectedAlert)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeAlertDetail"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                @if($selectedAlert->severity === 'high')
                                    <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-3 w-0 flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $selectedAlert->title }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        {{ $selectedAlert->description }}
                                    </p>
                                </div>
                                
                                @if($selectedAlert->activity)
                                    <div class="mt-4">
                                        <h4 class="text-sm font-medium text-gray-900">相關活動</h4>
                                        <div class="mt-2 bg-gray-50 rounded-md p-3">
                                            <p class="text-sm text-gray-700">
                                                <strong>時間：</strong>{{ $selectedAlert->activity->created_at->format('Y-m-d H:i:s') }}
                                            </p>
                                            <p class="text-sm text-gray-700">
                                                <strong>類型：</strong>{{ $selectedAlert->activity->type }}
                                            </p>
                                            <p class="text-sm text-gray-700">
                                                <strong>描述：</strong>{{ $selectedAlert->activity->description }}
                                            </p>
                                            @if($selectedAlert->activity->causer)
                                                <p class="text-sm text-gray-700">
                                                    <strong>使用者：</strong>{{ $selectedAlert->activity->causer->name }}
                                                </p>
                                            @endif
                                            @if($selectedAlert->activity->ip_address)
                                                <p class="text-sm text-gray-700">
                                                    <strong>IP 位址：</strong>{{ $selectedAlert->activity->ip_address }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        @if(!$selectedAlert->is_acknowledged)
                            @if($selectedAlert->activity && $selectedAlert->activity->ip_address)
                                <button 
                                    wire:click="blockIp('{{ $selectedAlert->activity->ip_address }}')"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    封鎖 IP
                                </button>
                            @endif
                            <button 
                                wire:click="acknowledgeAlert({{ $selectedAlert->id }})"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                確認警報
                            </button>
                        @endif
                        <button 
                            wire:click="closeAlertDetail"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- JavaScript for real-time updates --}}
@script
<script>
    // 即時更新功能
    let monitoringInterval;
    
    $wire.on('monitoring-started', () => {
        if (monitoringInterval) {
            clearInterval(monitoringInterval);
        }
        
        monitoringInterval = setInterval(() => {
            $wire.call('refreshMonitorData');
        }, {{ $refreshInterval * 1000 }});
    });
    
    $wire.on('monitoring-stopped', () => {
        if (monitoringInterval) {
            clearInterval(monitoringInterval);
            monitoringInterval = null;
        }
    });
    
    // 顯示警報通知
    $wire.on('show-alert-notification', (event) => {
        const { title, message, type } = event[0];
        
        // 瀏覽器通知
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/favicon.ico'
            });
        }
        
        // 頁面通知（可以使用 toast 或其他通知系統）
        console.log(`${type.toUpperCase()}: ${title} - ${message}`);
    });
    
    // 請求通知權限
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // 頁面卸載時清理定時器
    window.addEventListener('beforeunload', () => {
        if (monitoringInterval) {
            clearInterval(monitoringInterval);
        }
    });
</script>
@endscript