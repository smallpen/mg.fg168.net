<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    {{-- 標題列 --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">角色統計概覽</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">系統角色使用情況統計</p>
            </div>
            <div class="flex items-center space-x-2">
                <button 
                    wire:click="toggleDetails"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    {{ $showDetails ? '隱藏詳情' : '顯示詳情' }}
                </button>
                <button 
                    wire:click="refreshStats"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400">
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-1" wire:loading.class="animate-spin" />
                    重新整理
                </button>
            </div>
        </div>
    </div>

    {{-- 快速統計卡片 --}}
    <div class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->quickStats as $stat)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/20 rounded-md flex items-center justify-center">
                                @switch($stat['icon'])
                                    @case('user-group')
                                        <x-heroicon-o-user-group class="w-5 h-5 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                                        @break
                                    @case('check-circle')
                                        <x-heroicon-o-check-circle class="w-5 h-5 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                                        @break
                                    @case('users')
                                        <x-heroicon-o-users class="w-5 h-5 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                                        @break
                                    @case('key')
                                        <x-heroicon-o-key class="w-5 h-5 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 權限覆蓋率 --}}
        @if(!empty($this->permissionCoverage))
            <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">權限覆蓋率</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->permissionCoverage['used'] }}/{{ $this->permissionCoverage['total'] }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div 
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        style="width: {{ $this->permissionCoverage['percentage'] }}%">
                    </div>
                </div>
                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mt-1">
                    <span>{{ $this->permissionCoverage['percentage'] }}% 已使用</span>
                    <span>{{ $this->permissionCoverage['unused'] }} 未使用</span>
                </div>
            </div>
        @endif

        {{-- 詳細統計（可摺疊） --}}
        @if($showDetails)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- 熱門角色 --}}
                    @if(!empty($this->topRoles))
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">最常使用的角色</h4>
                            <div class="space-y-2">
                                @foreach(array_slice($this->topRoles, 0, 5) as $role)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $role['name'] }}</span>
                                            @if($role['is_system_role'])
                                                <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                    系統
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $role['users_count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- 權限模組分佈 --}}
                    @if(!empty($permissionDistribution['by_module']))
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">權限模組分佈</h4>
                            <div class="space-y-2">
                                @foreach(array_slice($permissionDistribution['by_module'], 0, 5) as $module)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $module['module'] }}</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $module['count'] }}</span>
                                            <div class="w-16 bg-gray-200 rounded-full h-1.5 dark:bg-gray-600">
                                                <div 
                                                    class="bg-blue-600 h-1.5 rounded-full"
                                                    style="width: {{ $module['percentage'] }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- 層級統計 --}}
                @if(!empty($systemStats['hierarchy']))
                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">層級結構統計</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $systemStats['hierarchy']['root_roles'] }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">根角色</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $systemStats['hierarchy']['child_roles'] }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">子角色</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $systemStats['hierarchy']['max_depth'] }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">最大深度</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $systemStats['hierarchy']['avg_depth'] }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">平均深度</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>