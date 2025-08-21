{{-- 列表依賴關係檢視 --}}
<div class="space-y-6">
    {{-- 依賴的權限列表 --}}
    @if($direction === 'dependencies' || $direction === 'both')
        <div>
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    此權限依賴的權限
                </h4>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $selectedPermission->dependencies->count() }} 個權限
                </span>
            </div>

            @if($selectedPermission->dependencies->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($selectedPermission->dependencies as $dependency)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $dependency->display_name }}
                                                    </h5>
                                                    
                                                    @if($dependency->is_system_permission)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                            系統權限
                                                        </span>
                                                    @endif
                                                    
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                                 @if($dependency->type === 'view') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                                 @elseif($dependency->type === 'create') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                                 @elseif($dependency->type === 'edit') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                                 @elseif($dependency->type === 'delete') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                                 @elseif($dependency->type === 'manage') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                                                 @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                                                 @endif">
                                                        {{ ucfirst($dependency->type) }}
                                                    </span>
                                                </div>
                                                
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $dependency->name }} • {{ ucfirst($dependency->module) }}
                                                </p>
                                                
                                                @if($dependency->description)
                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                        {{ $dependency->description }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2 ml-4">
                                        {{-- 選擇此權限 --}}
                                        <button wire:click="selectPermission({{ $dependency->id }})" 
                                                class="p-2 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200"
                                                title="選擇此權限">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>

                                        {{-- 移除依賴關係 --}}
                                        @if(auth()->user()->hasPermission('permissions.edit'))
                                            <button wire:click="removeDependency({{ $dependency->id }})" 
                                                    wire:confirm="確定要移除對「{{ $dependency->display_name }}」的依賴關係嗎？"
                                                    class="p-2 rounded-full text-red-400 hover:text-red-600 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-200"
                                                    title="移除依賴關係">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">此權限沒有依賴其他權限</p>
                </div>
            @endif
        </div>
    @endif

    {{-- 被依賴的權限列表 --}}
    @if($direction === 'dependents' || $direction === 'both')
        <div>
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    依賴此權限的權限
                </h4>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $selectedPermission->dependents->count() }} 個權限
                </span>
            </div>

            @if($selectedPermission->dependents->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($selectedPermission->dependents as $dependent)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $dependent->display_name }}
                                                    </h5>
                                                    
                                                    @if($dependent->is_system_permission)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                            系統權限
                                                        </span>
                                                    @endif
                                                    
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                                 @if($dependent->type === 'view') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                                 @elseif($dependent->type === 'create') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                                 @elseif($dependent->type === 'edit') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                                 @elseif($dependent->type === 'delete') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                                 @elseif($dependent->type === 'manage') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                                                 @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                                                 @endif">
                                                        {{ ucfirst($dependent->type) }}
                                                    </span>
                                                </div>
                                                
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $dependent->name }} • {{ ucfirst($dependent->module) }}
                                                </p>
                                                
                                                @if($dependent->description)
                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                        {{ $dependent->description }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2 ml-4">
                                        {{-- 選擇此權限 --}}
                                        <button wire:click="selectPermission({{ $dependent->id }})" 
                                                class="p-2 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200"
                                                title="選擇此權限">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">沒有其他權限依賴此權限</p>
                </div>
            @endif
        </div>
    @endif

    {{-- 統計資訊 --}}
    @if($selectedPermission)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">依賴關係統計</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ $selectedPermission->dependencies->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">直接依賴</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                        {{ $selectedPermission->dependents->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">直接被依賴</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $selectedPermission->getAllDependencies()->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">總依賴數</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $selectedPermission->getAllDependents()->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">總被依賴數</div>
                </div>
            </div>
        </div>
    @endif
</div>