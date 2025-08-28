<div class="dashboard-container">
    <!-- 頁面標題 -->
    <div class="dashboard-header mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('admin.dashboard.title') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('layout.welcome_back', ['name' => Auth::user()->name]) }}</p>
            </div>
            <button 
                wire:click="refresh" 
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                wire:loading.attr="disabled"
            >
                <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                重新整理
            </button>
        </div>
    </div>

    <!-- 載入狀態 -->
    <div wire:loading.delay class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700 dark:text-gray-300">載入中...</span>
        </div>
    </div>

    <!-- 統計卡片區域 -->
    <div class="stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- 使用者總數卡片 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">使用者總數</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['total_users']['count'] ?? 0) }}
                    </p>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                        本月新增 {{ $stats['total_users']['new_this_month'] ?? 0 }} 人
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 啟用使用者數卡片 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">啟用使用者</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['active_users']['count'] ?? 0) }}
                    </p>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                        活躍度 {{ $stats['active_users']['percentage'] ?? 0 }}%
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 角色總數卡片 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">角色總數</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['total_roles']['count'] ?? 0) }}
                    </p>
                    <p class="text-sm text-purple-600 dark:text-purple-400 mt-1">
                        平均 {{ number_format($stats['total_roles']['permissions_avg'] ?? 0, 1) }} 個權限
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 今日活動數卡片 -->
        <div class="stat-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">今日活動</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['today_activities']['count'] ?? 0) }}
                    </p>
                    <p class="text-sm text-orange-600 dark:text-orange-400 mt-1">
                        安全事件 {{ $stats['today_activities']['security_events'] ?? 0 }} 筆
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- 圖表和快速操作區域 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- 圖表區域 -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 使用者活動趨勢圖 -->
            <div class="chart-container bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">使用者活動趨勢</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">過去 7 天</span>
                </div>
                
                @php
                    $maxValue = max(array_column($chartData['activity_trend'] ?? [], 'count'));
                    $totalActivities = array_sum(array_column($chartData['activity_trend'] ?? [], 'count'));
                @endphp
                
                <!-- 簡潔的統計摘要 -->
                <div class="flex items-center justify-between mb-6 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalActivities) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">總活動數</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($maxValue) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">單日最高</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $totalActivities > 0 ? number_format($totalActivities / 7, 1) : 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">日均活動</div>
                    </div>
                </div>
                
                <!-- 簡化的圖表區域 -->
                <div class="relative bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                    <div class="h-40 flex items-end justify-between space-x-2">
                        @foreach($chartData['activity_trend'] ?? [] as $index => $data)
                            @php
                                $height = $maxValue > 0 ? max(($data['count'] / $maxValue) * 120, 8) : 8;
                                $isToday = $index === count($chartData['activity_trend']) - 1;
                            @endphp
                            <div class="flex flex-col items-center flex-1 group">
                                <!-- 柱狀圖 -->
                                <div class="relative w-full max-w-6 mb-2">
                                    <div class="w-full rounded-t {{ $isToday ? 'bg-blue-600' : 'bg-blue-500' }} transition-all duration-300 group-hover:bg-blue-700" 
                                         style="height: {{ $height }}px">
                                    </div>
                                    <!-- 懸停顯示數值 -->
                                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        {{ $data['count'] }} 次
                                    </div>
                                </div>
                                
                                <!-- 日期標籤 -->
                                <span class="text-xs {{ $isToday ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $data['date'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 登入時間分佈圖 -->
            <div class="chart-container bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">登入時間分佈</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">24 小時</span>
                </div>
                
                @php
                    $loginData = $chartData['login_distribution'] ?? [];
                    $maxLoginValue = max(array_column($loginData, 'count'));
                    $totalLogins = array_sum(array_column($loginData, 'count'));
                    $peakHour = '';
                    $peakCount = 0;
                    foreach($loginData as $data) {
                        if($data['count'] > $peakCount) {
                            $peakCount = $data['count'];
                            $peakHour = $data['hour'];
                        }
                    }
                @endphp
                
                <!-- 簡潔的統計摘要 -->
                <div class="flex items-center justify-between mb-6 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($totalLogins) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">總登入次數</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $peakHour }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">尖峰時段</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($peakCount) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">尖峰登入數</div>
                    </div>
                </div>
                
                <!-- 簡化的圖表區域 -->
                <div class="relative bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                    <div class="h-32 flex items-end justify-between">
                        @foreach($loginData as $index => $data)
                            @php
                                $height = $maxLoginValue > 0 ? max(($data['count'] / $maxLoginValue) * 100, 4) : 4;
                                $hour = intval(substr($data['hour'], 0, 2));
                                $isPeakHour = $data['count'] === $peakCount;
                                
                                // 簡化的顏色系統
                                if ($isPeakHour) {
                                    $colorClass = 'bg-green-600'; // 尖峰時段
                                } elseif ($hour >= 9 && $hour <= 17) {
                                    $colorClass = 'bg-green-500'; // 工作時間
                                } else {
                                    $colorClass = 'bg-green-400'; // 其他時間
                                }
                            @endphp
                            <div class="flex flex-col items-center group" style="width: {{ 100/24 }}%;">
                                <!-- 柱狀圖 -->
                                <div class="relative w-full max-w-2 mb-1">
                                    <div class="w-full rounded-t {{ $colorClass }} transition-all duration-300 group-hover:bg-green-700" 
                                         style="height: {{ $height }}px">
                                    </div>
                                    <!-- 懸停顯示數值 -->
                                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        {{ substr($data['hour'], 0, 2) }}:00 - {{ $data['count'] }} 次
                                    </div>
                                </div>
                                
                                <!-- 時間標籤 -->
                                @if($index % 4 === 0)
                                    <span class="text-xs {{ $isPeakHour ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ substr($data['hour'], 0, 2) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- 簡化的說明 -->
                <div class="mt-4 flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-600 rounded mr-2"></div>
                            <span class="text-gray-600 dark:text-gray-400">尖峰時段</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
                            <span class="text-gray-600 dark:text-gray-400">工作時間</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded mr-2"></div>
                            <span class="text-gray-600 dark:text-gray-400">其他時間</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 功能使用統計圓餅圖 -->
            <div class="chart-container bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">功能使用統計</h3>
                <div class="flex items-center justify-center mb-4">
                    <!-- 簡化的圓餅圖顯示 -->
                    <div class="relative w-32 h-32">
                        @php
                            $total = array_sum(array_column($chartData['feature_usage'] ?? [], 'value'));
                            $currentAngle = 0;
                            $colors = ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444'];
                        @endphp
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                            @foreach($chartData['feature_usage'] ?? [] as $index => $feature)
                                @php
                                    $percentage = $total > 0 ? ($feature['value'] / $total) * 100 : 0;
                                    $angle = ($percentage / 100) * 360;
                                    $largeArcFlag = $angle > 180 ? 1 : 0;
                                    $x1 = 50 + 40 * cos(deg2rad($currentAngle));
                                    $y1 = 50 + 40 * sin(deg2rad($currentAngle));
                                    $x2 = 50 + 40 * cos(deg2rad($currentAngle + $angle));
                                    $y2 = 50 + 40 * sin(deg2rad($currentAngle + $angle));
                                    $currentAngle += $angle;
                                @endphp
                                @if($percentage > 0)
                                    <path d="M 50 50 L {{ $x1 }} {{ $y1 }} A 40 40 0 {{ $largeArcFlag }} 1 {{ $x2 }} {{ $y2 }} Z" 
                                          fill="{{ $colors[$index % count($colors)] }}" 
                                          class="hover:opacity-80 transition-opacity">
                                    </path>
                                @endif
                            @endforeach
                        </svg>
                    </div>
                </div>
                <div class="space-y-2">
                    @foreach($chartData['feature_usage'] ?? [] as $index => $feature)
                        @php
                            $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-red-500'];
                            $color = $colors[$index % count($colors)];
                            $percentage = $total > 0 ? round(($feature['value'] / $total) * 100, 1) : 0;
                        @endphp
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 {{ $color }} rounded-full mr-2"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $feature['name'] }}</span>
                            </div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $feature['value'] }} ({{ $percentage }}%)
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 系統效能監控圖表 -->
            <div class="chart-container bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">系統效能監控</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">過去 24 小時</span>
                </div>
                <div class="space-y-4">
                    <!-- CPU 使用率 -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">CPU 使用率</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                @php
                                    $performanceData = $chartData['performance'] ?? [];
                                    $cpuUsage = !empty($performanceData) ? end($performanceData)['cpu'] ?? 0 : 0;
                                @endphp
                                {{ $cpuUsage }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $cpuUsage }}%"></div>
                        </div>
                    </div>
                    
                    <!-- 記憶體使用率 -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">記憶體使用率</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                @php
                                    $performanceData = $chartData['performance'] ?? [];
                                    $memoryUsage = !empty($performanceData) ? end($performanceData)['memory'] ?? 0 : 0;
                                @endphp
                                {{ $memoryUsage }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $memoryUsage }}%"></div>
                        </div>
                    </div>
                    
                    <!-- 回應時間 -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">平均回應時間</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                @php
                                    $performanceData = $chartData['performance'] ?? [];
                                    $responseTime = !empty($performanceData) ? end($performanceData)['response_time'] ?? 0 : 0;
                                @endphp
                                {{ $responseTime }}ms
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            @php
                                $responsePercentage = min(($responseTime / 1000) * 100, 100);
                                $responseColor = $responseTime < 200 ? 'bg-green-500' : ($responseTime < 500 ? 'bg-yellow-500' : 'bg-red-500');
                            @endphp
                            <div class="{{ $responseColor }} h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $responsePercentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 快速操作區域 -->
        <div class="space-y-6">
            <div class="quick-actions bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('admin.dashboard.quick_actions') }}</h3>
                <div class="space-y-3">
                    @foreach($quickActions as $action)
                        <button 
                            wire:click="navigateTo('{{ $action['route'] }}')"
                            class="w-full flex items-center p-3 text-left bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200"
                        >
                            <div class="w-10 h-10 bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($action['icon'] === 'user-plus')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    @elseif($action['icon'] === 'shield-plus')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    @elseif($action['icon'] === 'list')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    @endif
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $action['title'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $action['description'] }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 最近活動列表 -->
    <div class="recent-activities bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $showAllActivities ? '所有活動記錄' : '最近活動' }}
            </h3>
            <div class="flex items-center space-x-2">
                @if(!$showAllActivities)
                    <button 
                        wire:click="toggleShowAllActivities"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200"
                    >
                        查看全部
                    </button>
                @else
                    <button 
                        wire:click="toggleShowAllActivities"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200"
                    >
                        顯示最近
                    </button>
                @endif
            </div>
        </div>
        
        @php
            $activitiesToShow = $showAllActivities ? $this->activities : collect($recentActivities);
        @endphp
        
        @if($activitiesToShow->isEmpty())
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">暫無活動記錄</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($activitiesToShow as $activity)
                    <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                        <div class="w-8 h-8 bg-{{ $activity['color'] }}-100 dark:bg-{{ $activity['color'] }}-900 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-{{ $activity['color'] }}-600 dark:text-{{ $activity['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($activity['icon'] === 'plus-circle')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                @elseif($activity['icon'] === 'edit')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                @elseif($activity['icon'] === 'trash')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                @elseif($activity['icon'] === 'log-in')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                @elseif($activity['icon'] === 'log-out')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                @elseif($activity['icon'] === 'key')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                @elseif($activity['icon'] === 'shield')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                @elseif($activity['icon'] === 'unlock')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                @elseif($activity['icon'] === 'alert-triangle')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                @endif
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white">
                                <span class="font-medium">{{ $activity['causer_name'] }}</span>
                                {{ $activity['description'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $activity['created_at_human'] }}
                            </p>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center">
                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                                {{ $activity['event'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- 分頁控制 -->
            @if($showAllActivities && method_exists($this->activities, 'hasPages') && $this->activities->hasPages())
                <div class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        顯示第 {{ $this->activities->firstItem() }} - {{ $this->activities->lastItem() }} 筆，
                        共 {{ $this->activities->total() }} 筆記錄
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($this->activities->onFirstPage())
                            <span class="px-3 py-2 text-sm text-gray-400 dark:text-gray-600 cursor-not-allowed">上一頁</span>
                        @else
                            <button 
                                wire:click="previousPage" 
                                class="px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200"
                            >
                                上一頁
                            </button>
                        @endif
                        
                        <span class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                            第 {{ $this->activities->currentPage() }} 頁，共 {{ $this->activities->lastPage() }} 頁
                        </span>
                        
                        @if($this->activities->hasMorePages())
                            <button 
                                wire:click="nextPage" 
                                class="px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200"
                            >
                                下一頁
                            </button>
                        @else
                            <span class="px-3 py-2 text-sm text-gray-400 dark:text-gray-600 cursor-not-allowed">下一頁</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 統計數字動畫
    function animateNumbers() {
        const numbers = document.querySelectorAll('.stat-number');
        numbers.forEach(number => {
            const target = parseInt(number.textContent.replace(/,/g, ''));
            const duration = 1000;
            const step = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                number.textContent = Math.floor(current).toLocaleString();
            }, 16);
        });
    }
    
    // 圖表懸停效果
    function setupChartInteractions() {
        const chartBars = document.querySelectorAll('.chart-area > div > div');
        chartBars.forEach(bar => {
            bar.addEventListener('mouseenter', function() {
                this.style.transform = 'scaleY(1.1)';
                this.style.transformOrigin = 'bottom';
            });
            
            bar.addEventListener('mouseleave', function() {
                this.style.transform = 'scaleY(1)';
            });
        });
    }
    
    // 載入完成後執行動畫
    setTimeout(() => {
        animateNumbers();
        setupChartInteractions();
    }, 500);
    
    // Livewire 重新載入後重新執行動畫
    document.addEventListener('livewire:navigated', function() {
        setTimeout(() => {
            animateNumbers();
            setupChartInteractions();
        }, 100);
    });
});

// 主題切換時的平滑過渡
document.addEventListener('theme-changed', function(event) {
    document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
    setTimeout(() => {
        document.body.style.transition = '';
    }, 300);
});

// 響應式圖表調整
function adjustChartsForMobile() {
    const isMobile = window.innerWidth < 768;
    const charts = document.querySelectorAll('.chart-area');
    
    charts.forEach(chart => {
        if (isMobile) {
            chart.style.height = '8rem';
        } else {
            chart.style.height = '';
        }
    });
}

window.addEventListener('resize', adjustChartsForMobile);
adjustChartsForMobile();
</script>
@endpush