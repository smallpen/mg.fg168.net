{{-- 依賴路徑顯示 --}}
<div class="space-y-6">
    {{-- 依賴路徑 --}}
    @if($direction === 'dependencies' || $direction === 'both')
        @if(!empty($dependencyPaths))
            <div>
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    依賴路徑
                </h4>
                
                <div class="space-y-3">
                    @foreach($dependencyPaths as $index => $path)
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-800 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">
                                    路徑 {{ $index + 1 }} ({{ count($path) }} 層)
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    深度: {{ count($path) - 1 }}
                                </span>
                            </div>
                            
                            <div class="flex items-center space-x-2 overflow-x-auto pb-2">
                                @foreach($path as $pathIndex => $permission)
                                    {{-- 權限節點 --}}
                                    <div class="flex-shrink-0 flex items-center space-x-2">
                                        <div class="bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700 rounded-lg px-3 py-2 min-w-0">
                                            <div class="text-sm font-medium text-green-900 dark:text-green-100 truncate">
                                                {{ $permission['display_name'] }}
                                            </div>
                                            <div class="text-xs text-green-700 dark:text-green-300 truncate">
                                                {{ $permission['name'] }}
                                            </div>
                                            <div class="text-xs text-green-600 dark:text-green-400">
                                                {{ ucfirst($permission['module']) }} • {{ ucfirst($permission['type']) }}
                                            </div>
                                        </div>
                                        
                                        {{-- 箭頭 --}}
                                        @if($pathIndex < count($path) - 1)
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            </svg>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-8 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <svg class="mx-auto h-8 w-8 text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                <p class="text-sm text-green-600 dark:text-green-400">沒有依賴路徑</p>
            </div>
        @endif
    @endif

    {{-- 被依賴路徑 --}}
    @if($direction === 'dependents' || $direction === 'both')
        @if(!empty($dependentPaths))
            <div>
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                    被依賴路徑
                </h4>
                
                <div class="space-y-3">
                    @foreach($dependentPaths as $index => $path)
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-orange-200 dark:border-orange-800 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-orange-700 dark:text-orange-300">
                                    路徑 {{ $index + 1 }} ({{ count($path) }} 層)
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    深度: {{ count($path) - 1 }}
                                </span>
                            </div>
                            
                            <div class="flex items-center space-x-2 overflow-x-auto pb-2">
                                @foreach($path as $pathIndex => $permission)
                                    {{-- 權限節點 --}}
                                    <div class="flex-shrink-0 flex items-center space-x-2">
                                        <div class="bg-orange-100 dark:bg-orange-900 border border-orange-300 dark:border-orange-700 rounded-lg px-3 py-2 min-w-0">
                                            <div class="text-sm font-medium text-orange-900 dark:text-orange-100 truncate">
                                                {{ $permission['display_name'] }}
                                            </div>
                                            <div class="text-xs text-orange-700 dark:text-orange-300 truncate">
                                                {{ $permission['name'] }}
                                            </div>
                                            <div class="text-xs text-orange-600 dark:text-orange-400">
                                                {{ ucfirst($permission['module']) }} • {{ ucfirst($permission['type']) }}
                                            </div>
                                        </div>
                                        
                                        {{-- 箭頭 --}}
                                        @if($pathIndex < count($path) - 1)
                                            <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                            </svg>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-8 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <svg class="mx-auto h-8 w-8 text-orange-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                <p class="text-sm text-orange-600 dark:text-orange-400">沒有被依賴路徑</p>
            </div>
        @endif
    @endif

    {{-- 路徑統計 --}}
    @if($selectedPermission)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">路徑統計</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ count($dependencyPaths) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">依賴路徑</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                        {{ count($dependentPaths) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">被依賴路徑</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ !empty($dependencyPaths) ? max(array_map('count', $dependencyPaths)) - 1 : 0 }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">最大依賴深度</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ !empty($dependentPaths) ? max(array_map('count', $dependentPaths)) - 1 : 0 }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">最大被依賴深度</div>
                </div>
            </div>
        </div>
    @endif
</div>