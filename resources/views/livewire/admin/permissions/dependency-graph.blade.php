<div class="space-y-6">
    {{-- 權限選擇和控制面板 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 sm:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- 權限選擇 --}}
            <div class="lg:col-span-1">
                <label for="permission-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    選擇權限
                </label>
                <select id="permission-select" 
                        wire:model.live="selectedPermissionId"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">請選擇權限</option>
                    @foreach($availablePermissions as $permission)
                        <option value="{{ $permission['id'] }}">
                            {{ $permission['display_name'] }} ({{ $permission['name'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 檢視控制 --}}
            <div class="lg:col-span-2">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {{-- 檢視模式 --}}
                    <div>
                        <label for="view-mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            檢視模式
                        </label>
                        <select id="view-mode" 
                                wire:model.live="viewMode"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="tree">樹狀圖</option>
                            <option value="network">網路圖</option>
                            <option value="list">列表</option>
                            <option value="paths">路徑</option>
                        </select>
                    </div>

                    {{-- 方向 --}}
                    <div>
                        <label for="direction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            顯示方向
                        </label>
                        <select id="direction" 
                                wire:model.live="direction"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="dependencies">依賴關係</option>
                            <option value="dependents">被依賴關係</option>
                            <option value="both">雙向</option>
                        </select>
                    </div>

                    {{-- 模組篩選 --}}
                    <div>
                        <label for="module-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            模組篩選
                        </label>
                        <select id="module-filter" 
                                wire:model.live="moduleFilter"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="all">所有模組</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}">{{ ucfirst($module) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 最大深度 --}}
                    <div>
                        <label for="max-depth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            最大深度
                        </label>
                        <select id="max-depth" 
                                wire:model.live="maxDepth"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="1">1 層</option>
                            <option value="2">2 層</option>
                            <option value="3">3 層</option>
                            <option value="4">4 層</option>
                            <option value="5">5 層</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- 操作按鈕 --}}
        @if($selectedPermission)
        <div class="mt-4 flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('permissions.edit'))
                <button wire:click="openAddDependency" 
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    新增依賴
                </button>

                <button wire:click="autoResolveDependencies" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    自動解析
                </button>
            @endif

            <button wire:click="checkCircularDependencies" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                檢查循環依賴
            </button>
        </div>
        @endif
    </div>

    {{-- 依賴關係圖表 --}}
    @if($selectedPermission)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        {{-- 載入狀態 --}}
        <div wire:loading wire:target="selectedPermissionId,viewMode,direction,moduleFilter,maxDepth" 
             class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 z-10 flex items-center justify-center">
            <div class="flex items-center space-x-2">
                <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">載入中...</span>
            </div>
        </div>

        {{-- 權限資訊標題 --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $selectedPermission->display_name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $selectedPermission->name }} 
                        @if($selectedPermission->is_system_permission)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 ml-2">
                                系統權限
                            </span>
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        依賴: {{ $selectedPermission->dependencies->count() }} | 
                        被依賴: {{ $selectedPermission->dependents->count() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 圖表內容 --}}
        <div class="p-4">
            @if($viewMode === 'tree')
                {{-- 樹狀檢視 --}}
                @include('livewire.admin.permissions.partials.dependency-tree')
            @elseif($viewMode === 'network')
                {{-- 網路圖檢視 --}}
                @include('livewire.admin.permissions.partials.dependency-network')
            @elseif($viewMode === 'list')
                {{-- 列表檢視 --}}
                @include('livewire.admin.permissions.partials.dependency-list')
            @elseif($viewMode === 'paths')
                {{-- 路徑檢視 --}}
                @include('livewire.admin.permissions.partials.dependency-paths')
            @endif
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">請選擇權限</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">選擇一個權限來查看其依賴關係圖表</p>
    </div>
    @endif

    {{-- 新增依賴對話框 --}}
    <div x-data="{ show: @entangle('showAddDependency') }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <!-- 背景遮罩 -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity" 
                 aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75 dark:bg-gray-900"></div>
            </div>

            <!-- 對話框內容 -->
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        新增依賴關係
                    </h3>
                    <button type="button" 
                            wire:click="$set('showAddDependency', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        為權限「{{ $selectedPermission?->display_name }}」選擇依賴的權限：
                    </p>

                    <div class="max-h-64 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg">
                        @foreach($availablePermissions as $permission)
                            <div class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           value="{{ $permission['id'] }}"
                                           wire:model="selectedDependencies"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3 flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $permission['display_name'] }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $permission['name'] }} ({{ $permission['module'] }}.{{ $permission['type'] }})
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button"
                                wire:click="$set('showAddDependency', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            取消
                        </button>
                        <button type="button"
                                wire:click="addDependencies"
                                wire:loading.attr="disabled"
                                wire:target="addDependencies"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="addDependencies">新增依賴</span>
                            <span wire:loading wire:target="addDependencies" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                處理中...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>