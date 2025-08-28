<div class="space-y-6">
    {{-- 頁面標題 --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ __('admin.permissions.matrix') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('admin.permissions.matrix_description') }}
            </p>
        </div>
        
        <div class="flex space-x-3">
            {{-- 顯示模式切換 --}}
            <button wire:click="toggleViewMode" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                @if($viewMode === 'matrix')
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    列表檢視
                @else
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    矩陣檢視
                @endif
            </button>

            {{-- 描述顯示切換 --}}
            <button wire:click="toggleDescriptions" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $showDescriptions ? '隱藏描述' : '顯示描述' }}
            </button>
        </div>
    </div>

    {{-- 搜尋和篩選區域 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- 搜尋框 --}}
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin.permissions.search') }}
                </label>
                <div class="relative">
                    <input type="text" 
                           id="search"
                           wire:model.debounce.300ms="search" 
                           placeholder="{{ __('admin.permissions.search_placeholder') }}"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- 模組篩選 --}}
            <div>
                <label for="moduleFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin.permissions.filter_by_module') }}
                </label>
                <select id="moduleFilter" 
                        wire:model="moduleFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">{{ __('admin.permissions.all_modules') }}</option>
                    @foreach($this->modules as $module)
                        <option value="{{ $module }}">{{ ucfirst($module) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 清除篩選按鈕 --}}
        @if($search || $moduleFilter)
        <div class="mt-4 flex justify-end">
            <button wire:click="clearFilters" 
                    class="inline-flex items-center px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ __('admin.permissions.clear_filters') }}
            </button>
        </div>
        @endif
    </div>

    {{-- 效能指標 --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    <span class="font-medium">載入狀態:</span>
                    <span wire:loading wire:target="search,moduleFilter" class="text-blue-600">搜尋中...</span>
                    <span wire:loading.remove wire:target="search,moduleFilter" class="text-green-600">已載入</span>
                </div>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    <span class="font-medium">角色數量:</span> {{ $this->roles->count() }}
                </div>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    <span class="font-medium">權限數量:</span> {{ $this->filteredPermissions->flatten()->count() }}
                </div>
            </div>
            <div class="text-xs text-blue-600 dark:text-blue-400">
                💡 大型矩陣已啟用效能優化
            </div>
        </div>
    </div>

    {{-- 變更預覽 --}}
    @if($showPreview)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
        <div class="flex justify-between items-start">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        待應用的權限變更
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>總計 {{ $this->changeStats['total'] }} 個變更：
                            <span class="text-green-600 dark:text-green-400">新增 {{ $this->changeStats['add'] }} 個</span>，
                            <span class="text-red-600 dark:text-red-400">移除 {{ $this->changeStats['remove'] }} 個</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex space-x-2">
                <button wire:click="applyChanges" 
                        class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors duration-200">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    應用變更
                </button>
                <button wire:click="cancelChanges" 
                        class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors duration-200">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    取消變更
                </button>
            </div>
        </div>

        {{-- 變更詳情 --}}
        <div class="mt-4 max-h-40 overflow-y-auto">
            <div class="space-y-1">
                @foreach($permissionChanges as $changeKey => $change)
                    <div class="flex items-center justify-between text-xs bg-white dark:bg-gray-800 rounded px-2 py-1">
                        <span>
                            @if($change['action'] === 'add')
                                <span class="text-green-600 dark:text-green-400">新增</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">移除</span>
                            @endif
                            {{ $change['role_name'] }} → {{ $change['permission_name'] }}
                        </span>
                        <button wire:click="removeChange('{{ $changeKey }}')" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- 權限矩陣 --}}
    @if($viewMode === 'matrix')
        {{-- 矩陣檢視 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-900 z-10">
                                權限 / 角色
                            </th>
                            @foreach($this->roles as $role)
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-24">
                                    <div class="flex flex-col items-center">
                                        <span class="mb-1">{{ $role->display_name }}</span>
                                        <span class="text-xs text-gray-400">({{ $role->permissions_count }})</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->filteredPermissions as $module => $permissions)
                            {{-- 模組標題行 --}}
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white sticky left-0 bg-gray-100 dark:bg-gray-700 z-10">
                                    <div class="flex items-center justify-between">
                                        <span>{{ ucfirst($module) }} 模組 ({{ $permissions->count() }})</span>
                                        <div class="flex items-center space-x-2">
                                            {{-- 模組批量操作按鈕 --}}
                                            <div class="flex space-x-1">
                                                <button wire:click="assignModuleToAllRoles('{{ $module }}')"
                                                        class="text-xs px-2 py-1 bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800 rounded transition-colors duration-200"
                                                        title="將 {{ $module }} 模組指派給所有角色">
                                                    全部指派
                                                </button>
                                                <button wire:click="revokeModuleFromAllRoles('{{ $module }}')"
                                                        class="text-xs px-2 py-1 bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800 rounded transition-colors duration-200"
                                                        title="從所有角色移除 {{ $module }} 模組">
                                                    全部移除
                                                </button>
                                            </div>
                                            {{-- 個別角色操作按鈕 --}}
                                            <div class="flex space-x-1">
                                                @foreach($this->roles as $role)
                                                    <div class="flex space-x-1">
                                                        @if(!$this->roleHasAllModulePermissions($role->id, $module))
                                                            <button wire:click="assignModuleToRole({{ $role->id }}, '{{ $module }}')"
                                                                    class="text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 p-1 rounded hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors duration-200"
                                                                    title="指派 {{ $module }} 模組給 {{ $role->display_name }}">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                                </svg>
                                                            </button>
                                                        @endif
                                                        @if($this->roleHasAllModulePermissions($role->id, $module) || $this->roleHasSomeModulePermissions($role->id, $module))
                                                            <button wire:click="revokeModuleFromRole({{ $role->id }}, '{{ $module }}')"
                                                                    class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-200"
                                                                    title="移除 {{ $module }} 模組從 {{ $role->display_name }}">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"></path>
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @foreach($this->roles as $role)
                                    <td class="px-3 py-3 text-center">
                                        @if($this->roleHasAllModulePermissions($role->id, $module))
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                全部
                                            </span>
                                        @elseif($this->roleHasSomeModulePermissions($role->id, $module))
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                部分
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                無
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            {{-- 權限行 --}}
                            @foreach($permissions as $permission)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <td class="px-6 py-3 text-sm text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-800 z-10">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-medium flex items-center">
                                                    {{ $permission->display_name }}
                                                    {{-- 權限依賴關係指示器 --}}
                                                    @if($permission->dependencies && $permission->dependencies->count() > 0)
                                                        <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" title="此權限有 {{ $permission->dependencies->count() }} 個依賴項">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                            </svg>
                                                            {{ $permission->dependencies->count() }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($showDescriptions && $permission->description)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $permission->description }}</div>
                                                @endif
                                                {{-- 顯示權限依賴列表 --}}
                                                @if($showDescriptions && $permission->dependencies && $permission->dependencies->count() > 0)
                                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                        依賴：{{ $permission->dependencies->pluck('display_name')->join('、') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex space-x-1">
                                                <button wire:click="assignPermissionToAllRoles({{ $permission->id }})"
                                                        class="text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300"
                                                        title="指派給所有角色">
                                                    全選
                                                </button>
                                                <button wire:click="revokePermissionFromAllRoles({{ $permission->id }})"
                                                        class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                                                        title="從所有角色移除">
                                                    全清
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    @foreach($this->roles as $role)
                                        <td class="px-3 py-3 text-center">
                                            <button wire:click="togglePermission({{ $role->id }}, {{ $permission->id }})"
                                                    class="relative inline-flex items-center justify-center w-8 h-8 rounded-full transition-all duration-200 hover:scale-110">
                                                @php
                                                    $hasPermission = $this->roleHasPermission($role->id, $permission->id);
                                                    $changeStatus = $this->getPermissionChangeStatus($role->id, $permission->id);
                                                @endphp

                                                @if($hasPermission)
                                                    <svg class="w-5 h-5 {{ $changeStatus === 'remove' ? 'text-red-500' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 {{ $changeStatus === 'add' ? 'text-green-500' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                @endif

                                                {{-- 變更指示器 --}}
                                                @if($changeStatus)
                                                    <span class="absolute -top-1 -right-1 w-3 h-3 {{ $changeStatus === 'add' ? 'bg-green-500' : 'bg-red-500' }} rounded-full border-2 border-white dark:border-gray-800"></span>
                                                @endif
                                            </button>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- 列表檢視 --}}
        <div class="space-y-6">
            @foreach($this->filteredPermissions as $module => $permissions)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ ucfirst($module) }} 模組
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $permissions->count() }} 個權限
                        </p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($permissions as $permission)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $permission->display_name }}
                                            </h4>
                                            @if($showDescriptions && $permission->description)
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $permission->description }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="ml-4 flex space-x-2">
                                            <button wire:click="assignPermissionToAllRoles({{ $permission->id }})"
                                                    class="text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">
                                                全選
                                            </button>
                                            <button wire:click="revokePermissionFromAllRoles({{ $permission->id }})"
                                                    class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                                全清
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach($this->roles as $role)
                                            @php
                                                $hasPermission = $this->roleHasPermission($role->id, $permission->id);
                                                $changeStatus = $this->getPermissionChangeStatus($role->id, $permission->id);
                                            @endphp
                                            <button wire:click="togglePermission({{ $role->id }}, {{ $permission->id }})"
                                                    class="relative inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors duration-200
                                                           {{ $hasPermission 
                                                              ? ($changeStatus === 'remove' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200')
                                                              : ($changeStatus === 'add' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                                {{ $role->display_name }}
                                                @if($changeStatus)
                                                    <span class="ml-1 text-xs">
                                                        {{ $changeStatus === 'add' ? '+' : '-' }}
                                                    </span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- 空狀態 --}}
    @if($this->filteredPermissions->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                {{ __('admin.permissions.no_permissions') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                {{ __('admin.permissions.search_help') }}
            </p>
        </div>
    @endif
</div>

<script>
document.addEventListener('livewire:init', () => {
    // 權限矩陣效能優化
    let performanceOptimizations = {
        // 虛擬滾動支援
        virtualScrolling: false,
        
        // 延遲載入
        lazyLoading: true,
        
        // 批量更新
        batchUpdates: true,
        
        // 快取管理
        cacheManager: new Map(),
        
        init() {
            this.setupVirtualScrolling();
            this.setupLazyLoading();
            this.setupBatchUpdates();
            this.setupPerformanceMonitoring();
            console.log('🚀 權限矩陣效能優化已啟用');
        },
        
        setupVirtualScrolling() {
            const matrixContainer = document.querySelector('.overflow-x-auto');
            if (!matrixContainer) return;
            
            const table = matrixContainer.querySelector('table');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            if (rows.length < 50) return; // 少於 50 行不需要虛擬滾動
            
            this.virtualScrolling = true;
            console.log('📊 啟用虛擬滾動 (行數:', rows.length, ')');
            
            // 實作虛擬滾動邏輯
            let visibleStart = 0;
            let visibleEnd = Math.min(20, rows.length);
            
            const updateVisibleRows = () => {
                rows.forEach((row, index) => {
                    if (index >= visibleStart && index < visibleEnd) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            };
            
            matrixContainer.addEventListener('scroll', () => {
                const scrollTop = matrixContainer.scrollTop;
                const rowHeight = 60; // 估計行高
                const containerHeight = matrixContainer.clientHeight;
                
                visibleStart = Math.floor(scrollTop / rowHeight);
                visibleEnd = Math.min(visibleStart + Math.ceil(containerHeight / rowHeight) + 5, rows.length);
                
                updateVisibleRows();
            });
            
            updateVisibleRows();
        },
        
        setupLazyLoading() {
            if (!this.lazyLoading) return;
            
            // 延遲載入權限檢查框
            const checkboxes = document.querySelectorAll('input[type="checkbox"][wire\\:click*="togglePermission"]');
            
            if (checkboxes.length > 100) {
                console.log('⏳ 啟用延遲載入 (檢查框數量:', checkboxes.length, ')');
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const checkbox = entry.target;
                            // 預載入檢查框狀態
                            if (!checkbox.dataset.loaded) {
                                checkbox.dataset.loaded = 'true';
                                // 這裡可以添加預載入邏輯
                            }
                        }
                    });
                }, {
                    rootMargin: '50px'
                });
                
                checkboxes.forEach(checkbox => {
                    observer.observe(checkbox);
                });
            }
        },
        
        setupBatchUpdates() {
            if (!this.batchUpdates) return;
            
            let updateQueue = [];
            let updateTimer = null;
            
            // 攔截權限切換事件
            document.addEventListener('click', (e) => {
                const checkbox = e.target.closest('input[wire\\:click*="togglePermission"]');
                if (!checkbox) return;
                
                // 添加到批量更新佇列
                const wireClick = checkbox.getAttribute('wire:click');
                updateQueue.push({
                    element: checkbox,
                    action: wireClick,
                    timestamp: Date.now()
                });
                
                // 防抖動處理
                clearTimeout(updateTimer);
                updateTimer = setTimeout(() => {
                    this.processBatchUpdates();
                }, 300);
            });
            
            console.log('📦 啟用批量更新');
        },
        
        processBatchUpdates() {
            if (updateQueue.length === 0) return;
            
            console.log('🔄 處理批量更新:', updateQueue.length, '個操作');
            
            // 這裡可以實作批量 API 呼叫
            // 目前先逐個處理
            updateQueue.forEach(update => {
                // 觸發原始的 Livewire 事件
                eval(update.action);
            });
            
            updateQueue = [];
        },
        
        setupPerformanceMonitoring() {
            // 監控渲染效能
            let renderStart = performance.now();
            
            const observer = new MutationObserver(() => {
                const renderEnd = performance.now();
                const renderTime = renderEnd - renderStart;
                
                if (renderTime > 100) {
                    console.warn('⚠️ 權限矩陣渲染時間過長:', renderTime.toFixed(2), 'ms');
                }
                
                renderStart = performance.now();
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            // 監控記憶體使用
            if (performance.memory) {
                setInterval(() => {
                    const memory = performance.memory;
                    const usedMB = Math.round(memory.usedJSHeapSize / 1048576);
                    
                    if (usedMB > 100) {
                        console.warn('⚠️ 記憶體使用量較高:', usedMB, 'MB');
                    }
                }, 10000);
            }
        },
        
        // 快取權限狀態
        cachePermissionState(roleId, permissionId, state) {
            const key = `${roleId}_${permissionId}`;
            this.cacheManager.set(key, {
                state,
                timestamp: Date.now()
            });
        },
        
        getCachedPermissionState(roleId, permissionId) {
            const key = `${roleId}_${permissionId}`;
            const cached = this.cacheManager.get(key);
            
            if (cached && (Date.now() - cached.timestamp) < 30000) {
                return cached.state;
            }
            
            return null;
        }
    };
    
    // 初始化效能優化
    performanceOptimizations.init();
    
    // 監聽 Livewire 事件
    Livewire.on('permission-toggled', (data) => {
        performanceOptimizations.cachePermissionState(
            data.roleId, 
            data.permissionId, 
            !performanceOptimizations.getCachedPermissionState(data.roleId, data.permissionId)
        );
    });
    
    // 清除快取事件
    Livewire.on('permissions-applied', () => {
        performanceOptimizations.cacheManager.clear();
        console.log('🗑️ 權限快取已清除');
    });
});
</script>