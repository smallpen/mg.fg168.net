<div class="space-y-6">
    <!-- 頁面標題和操作按鈕 -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                權限使用情況分析
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                分析權限使用統計、趨勢和未使用權限
            </p>
        </div>
        
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <button 
                wire:click="refreshAnalysis"
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                重新整理
            </button>
            
            @if(auth()->user()->hasPermission('permissions.export'))
            <button 
                wire:click="exportAnalysisReport"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                匯出報告
            </button>
            @endif
        </div>
    </div>

    <!-- 分析模式切換 -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button 
                wire:click="setAnalysisMode('overview')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $analysisMode === 'overview' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}"
            >
                總覽
            </button>
            <button 
                wire:click="setAnalysisMode('detailed')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $analysisMode === 'detailed' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}"
            >
                詳細分析
            </button>
            <button 
                wire:click="setAnalysisMode('heatmap')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $analysisMode === 'heatmap' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}"
            >
                使用熱力圖
            </button>
        </nav>
    </div>

    <!-- 篩選選項 -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- 模組篩選 -->
            <div>
                <label for="moduleFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    模組
                </label>
                <select 
                    wire:model.live="moduleFilter" 
                    id="moduleFilter"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                >
                    <option value="all">所有模組</option>
                    @foreach($availableModules as $module)
                        <option value="{{ $module }}">{{ $module }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 使用狀態篩選 -->
            <div>
                <label for="usageFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    使用狀態
                </label>
                <select 
                    wire:model.live="usageFilter" 
                    id="usageFilter"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                >
                    <option value="all">全部</option>
                    <option value="used">已使用</option>
                    <option value="unused">未使用</option>
                    <option value="low_usage">低使用率</option>
                </select>
            </div>

            <!-- 天數範圍 -->
            @if($analysisMode === 'detailed' || $analysisMode === 'trends')
            <div>
                <label for="daysRange" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    分析天數
                </label>
                <select 
                    wire:model.live="daysRange" 
                    id="daysRange"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                >
                    <option value="7">7天</option>
                    <option value="30">30天</option>
                    <option value="90">90天</option>
                    <option value="180">180天</option>
                    <option value="365">365天</option>
                </select>
            </div>
            @endif

            <!-- 顯示選項 -->
            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model.live="excludeSystemPermissions"
                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">排除系統權限</span>
                </label>
            </div>
        </div>
    </div>

    <!-- 總覽模式 -->
    @if($analysisMode === 'overview')
        <!-- 整體統計卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    總權限數
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ number_format($overallStats['total_permissions']) }}
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
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    已使用權限
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ number_format($overallStats['used_permissions']) }}
                                    <span class="text-sm text-green-600 dark:text-green-400">
                                        ({{ $overallStats['usage_percentage'] }}%)
                                    </span>
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
                            <div class="w-8 h-8 bg-gray-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    未使用權限
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ number_format($overallStats['unused_permissions']) }}
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        ({{ $overallStats['unused_percentage'] }}%)
                                    </span>
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
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    平均權限/使用者
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $overallStats['average_permissions_per_user'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 模組使用統計 -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    模組使用統計
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    模組
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    總權限數
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    已使用
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    未使用
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    使用率
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    使用者數
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($moduleStats as $stat)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $stat['module'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $stat['total_permissions'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                    {{ $stat['used_permissions'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $stat['unused_permissions'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stat['usage_percentage'] }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['usage_percentage'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $stat['total_users'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 未使用權限列表 -->
        @if($unusedPermissions->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        未使用權限 ({{ $unusedPermissions->count() }})
                    </h3>
                    @if(auth()->user()->hasPermission('permissions.manage'))
                    <button 
                        wire:click="markUnusedPermissions"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                    >
                        標記未使用
                    </button>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($unusedPermissions->take(12) as $permission)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                         wire:click="selectPermission({{ $permission['id'] }})">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $permission['display_name'] }}
                            </h4>
                            @if($permission['is_system_permission'])
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                系統
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $permission['module'] }} • {{ $permission['name'] }}
                        </p>
                        <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">
                            @if($permission['has_dependencies'])
                            <span class="mr-2">有依賴</span>
                            @endif
                            @if($permission['has_dependents'])
                            <span>被依賴</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($unusedPermissions->count() > 12)
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        還有 {{ $unusedPermissions->count() - 12 }} 個未使用權限...
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endif
    @endif

    <!-- 詳細分析模式 -->
    @if($analysisMode === 'detailed')
        @if($selectedPermissionStats)
        <div class="space-y-6">
            <!-- 權限基本資訊 -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            {{ $selectedPermissionStats['display_name'] }}
                        </h3>
                        <button 
                            wire:click="clearSelection"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">權限名稱</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedPermissionStats['permission_name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">模組</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedPermissionStats['module'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">類型</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedPermissionStats['type'] }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 使用統計 -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        角色數量
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ $selectedPermissionStats['role_count'] }}
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
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        使用者數量
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ $selectedPermissionStats['user_count'] }}
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
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        使用頻率
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ $selectedPermissionStats['usage_frequency'] }}
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
                                @php
                                    $badge = $this->getUsageStatusBadge($selectedPermissionStats);
                                @endphp
                                <span class="{{ $badge['class'] }}">
                                    {{ $badge['text'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 使用趨勢圖表 -->
            @if($selectedPermissionTrend && $showTrends)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        使用趨勢 ({{ $daysRange }} 天)
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ $this->getTrendIcon($selectedPermissionTrend['trend_direction']) }}
                            {{ $selectedPermissionTrend['trend_direction'] === 'increasing' ? '上升' : ($selectedPermissionTrend['trend_direction'] === 'decreasing' ? '下降' : '穩定') }}
                        </span>
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $selectedPermissionTrend['total_usage'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">總使用次數</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $selectedPermissionTrend['average_daily'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">日平均使用</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $selectedPermissionTrend['peak_usage'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">峰值使用</div>
                        </div>
                    </div>

                    <!-- 簡單的趨勢圖表 -->
                    <div class="h-64 flex items-end space-x-1">
                        @php
                            $maxValue = max(array_values($selectedPermissionTrend['daily_usage']));
                            $maxValue = $maxValue > 0 ? $maxValue : 1;
                        @endphp
                        @foreach($selectedPermissionTrend['daily_usage'] as $date => $count)
                        <div class="flex-1 flex flex-col items-center">
                            <div 
                                class="w-full bg-blue-500 rounded-t"
                                style="height: {{ $maxValue > 0 ? ($count / $maxValue) * 200 : 0 }}px"
                                title="{{ $date }}: {{ $count }} 次使用"
                            ></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 transform rotate-45 origin-left">
                                {{ \Carbon\Carbon::parse($date)->format('m/d') }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">選擇權限進行詳細分析</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    從總覽頁面點擊權限或使用搜尋功能選擇要分析的權限
                </p>
            </div>
        </div>
        @endif
    @endif

    <!-- 使用熱力圖模式 -->
    @if($analysisMode === 'heatmap')
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                權限使用熱力圖
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-2">
                @foreach($heatmapData as $item)
                @php
                    $intensity = $item['intensity'];
                    $bgClass = 'bg-gray-100 dark:bg-gray-700';
                    if ($intensity > 5) $bgClass = 'bg-red-500';
                    elseif ($intensity > 3) $bgClass = 'bg-orange-500';
                    elseif ($intensity > 1) $bgClass = 'bg-yellow-500';
                    elseif ($intensity > 0) $bgClass = 'bg-green-500';
                @endphp
                <div 
                    class="aspect-square {{ $bgClass }} rounded p-2 cursor-pointer hover:opacity-80 transition-opacity"
                    wire:click="selectPermission({{ $item['id'] }})"
                    title="{{ $item['name'] }} - 使用者: {{ $item['user_count'] }}, 角色: {{ $item['role_count'] }}"
                >
                    <div class="text-xs text-white font-medium truncate">
                        {{ $item['name'] }}
                    </div>
                    <div class="text-xs text-white opacity-75">
                        {{ $item['user_count'] }} 使用者
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- 圖例 -->
            <div class="mt-4 flex items-center space-x-4 text-sm">
                <span class="text-gray-600 dark:text-gray-400">使用強度:</span>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-gray-100 dark:bg-gray-700 rounded"></div>
                    <span class="text-gray-600 dark:text-gray-400">未使用</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded"></div>
                    <span class="text-gray-600 dark:text-gray-400">低</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                    <span class="text-gray-600 dark:text-gray-400">中</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-orange-500 rounded"></div>
                    <span class="text-gray-600 dark:text-gray-400">高</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-red-500 rounded"></div>
                    <span class="text-gray-600 dark:text-gray-400">非常高</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>