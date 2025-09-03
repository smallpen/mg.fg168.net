<div class="space-y-6">
    {{-- 權限統計資訊 --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('permissions.usage.total_permissions') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_permissions'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('permissions.usage.used_permissions') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['used_permissions'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-gray-100 dark:bg-gray-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('permissions.usage.unused_permissions') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['unused_permissions'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('permissions.usage.usage_frequency') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['usage_percentage'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 搜尋和篩選區域 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            {{-- 手機版佈局 --}}
            <div class="block sm:hidden space-y-4">
                {{-- 搜尋框 --}}
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('permissions.search.search_placeholder') }}"
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base"
                    />
                    @if($search)
                        <button 
                            wire:click="$set('search', '')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- 篩選器和重置按鈕 --}}
                <div class="flex items-center justify-between">
                    <button 
                        wire:click="toggleFilters"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        {{ __('permissions.filters.toggle') }}
                    </button>

                    <div x-data="resetButtonController()" x-init="init()">
                        <button 
                            x-show="showResetButton"
                            wire:click="resetFilters"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                            x-transition
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ __('permissions.filters.reset') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- 桌面版佈局 --}}
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center gap-4">
                {{-- 搜尋框 --}}
                <div class="flex-1">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input 
                            type="text" 
                            wire:model.live="search"
                            placeholder="{{ __('permissions.search.search_placeholder') }}"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        @if($search)
                            <button 
                                wire:click="$set('search', '')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- 篩選器切換按鈕 --}}
                <button 
                    wire:click="toggleFilters"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    {{ __('permissions.filters.toggle') }}
                    @if($showFilters)
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    @endif
                </button>

                {{-- 操作按鈕 --}}
                @if($this->hasPermission('create'))
                    <button 
                        wire:click="createPermission"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ __('permissions.create_permission') }}
                    </button>
                @endif

                {{-- 重置按鈕 --}}
                <div x-data="resetButtonController()" x-init="init()">
                    <button 
                        x-show="showResetButton"
                        wire:click="resetFilters"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                        x-transition
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ __('permissions.filters.reset') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- 進階篩選器 --}}
        @if($showFilters)
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {{-- 模組篩選 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('permissions.filters.module') }}
                        </label>
                        <select 
                            wire:model.live="moduleFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">{{ __('permissions.all_modules') }}</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}">{{ $this->getLocalizedModule($module) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 類型篩選 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('permissions.filters.type') }}
                        </label>
                        <select 
                            wire:model.live="typeFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">{{ __('permissions.all_types') }}</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ $this->getLocalizedType($type) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 使用狀態篩選 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('permissions.filters.usage') }}
                        </label>
                        <select 
                            wire:model.live="usageFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            @foreach($usageOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>


                </div>

                {{-- 額外操作按鈕（手機版） --}}
                <div class="mt-4 flex flex-wrap gap-2 sm:hidden">
                    @if($this->hasPermission('create'))
                        <button 
                            wire:click="createPermission"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            {{ __('permissions.create_permission') }}
                        </button>
                    @endif
                    
                    @if($this->hasPermission('export'))
                        <button 
                            wire:click="exportPermissions"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('permissions.export') }}
                        </button>
                    @endif
                    
                    @if($this->hasPermission('import'))
                        <button 
                            wire:click="importPermissions"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            {{ __('permissions.import') }}
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- 批量操作工具列 --}}
    @if(count($selectedPermissions) > 0)
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-blue-700 dark:text-blue-300">
                    {{ __('permissions.selected_permissions', ['count' => count($selectedPermissions)]) }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <button wire:click="resetFilters" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    {{ __('permissions.cancel_selection') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- 權限列表內容 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        {{-- 載入狀態覆蓋層 --}}
        <div wire:loading wire:target="search,moduleFilter,typeFilter,usageFilter,viewMode" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 z-10 flex items-center justify-center">
            <div class="flex items-center space-x-2">
                <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 01 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 01 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('permissions.loading') }}</span>
            </div>
        </div>

        {{-- 權限列表表格 --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            權限名稱
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            顯示名稱
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            模組
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            使用角色
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('permissions.actions_label') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($permissions as $permission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $permission->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $permission->display_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ ucfirst($permission->module) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $permission->roles_count ?? 0 }} 個角色
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    {{-- 檢視按鈕 --}}
                                    @can('permissions.view')
                                        <button 
                                            wire:click="viewPermission({{ $permission->id }})"
                                            class="p-1 text-gray-400 hover:text-blue-600 transition-colors duration-200"
                                            title="檢視權限"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    @endcan
                                    
                                    {{-- 編輯按鈕 --}}
                                    @can('permissions.edit')
                                        <button 
                                            wire:click="editPermission({{ $permission->id }})"
                                            class="p-1 text-gray-400 hover:text-green-600 transition-colors duration-200"
                                            title="編輯權限"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    @endcan
                                    
                                    {{-- 刪除按鈕 --}}
                                    @can('permissions.delete')
                                        <button 
                                            wire:click="deletePermission({{ $permission->id }})"
                                            class="p-1 text-gray-400 hover:text-red-600 transition-colors duration-200"
                                            title="刪除權限"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">沒有找到權限</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">請嘗試調整搜尋條件或建立新的權限</p>
                                    <div x-data="{ 
                                        showResetButton: @js(!empty($search) || $moduleFilter !== 'all' || $typeFilter !== 'all' || $usageFilter !== 'all')
                                    }" 
                                    x-init="
                                        Livewire.on('force-ui-update', () => {
                                            showResetButton = false;
                                        });
                                    ">
                                        <button x-show="showResetButton" 
                                                wire:click="resetFilters" 
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                x-transition>
                                            清除篩選
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 分頁和每頁顯示筆數 --}}
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                {{-- 每頁顯示筆數選擇器 --}}
                <div class="flex items-center space-x-3">
                    <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        每頁顯示：
                    </label>
                    <select 
                        id="perPage"
                        wire:model.live="perPage"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent min-w-[80px]"
                    >
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }} 筆</option>
                        @endforeach
                    </select>
                    <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        共 {{ $permissions->total() }} 筆
                    </span>
                </div>

                {{-- 分頁導航 --}}
                @if($permissions->hasPages())
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-between">
                            {{-- 手機版分頁 --}}
                            <div class="flex-1 flex justify-between sm:hidden">
                                @if ($permissions->onFirstPage())
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                                        上一頁
                                    </span>
                                @else
                                    <button 
                                        wire:click="previousPage" 
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300"
                                    >
                                        上一頁
                                    </button>
                                @endif

                                @if ($permissions->hasMorePages())
                                    <button 
                                        wire:click="nextPage" 
                                        class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300"
                                    >
                                        下一頁
                                    </button>
                                @else
                                    <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                                        下一頁
                                    </span>
                                @endif
                            </div>

                            {{-- 桌面版分頁 --}}
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700 leading-5 dark:text-gray-400">
                                        顯示第
                                        <span class="font-medium">{{ $permissions->firstItem() ?? 0 }}</span>
                                        到
                                        <span class="font-medium">{{ $permissions->lastItem() ?? 0 }}</span>
                                        筆，共
                                        <span class="font-medium">{{ $permissions->total() }}</span>
                                        筆結果
                                    </p>
                                </div>

                                <div>
                                    <span class="relative z-0 inline-flex shadow-sm rounded-md">
                                        {{-- 上一頁按鈕 --}}
                                        @if ($permissions->onFirstPage())
                                            <span aria-disabled="true" aria-label="上一頁">
                                                <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5 dark:bg-gray-800 dark:border-gray-600" aria-hidden="true">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </span>
                                        @else
                                            <button 
                                                wire:click="previousPage" 
                                                class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800" 
                                                aria-label="上一頁"
                                            >
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        @endif

                                        {{-- 頁碼按鈕 --}}
                                        @for ($page = 1; $page <= $permissions->lastPage(); $page++)
                                            @if ($page == $permissions->currentPage())
                                                <span aria-current="page">
                                                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-blue-600 border border-blue-600 cursor-default leading-5">{{ $page }}</span>
                                                </span>
                                            @else
                                                <button 
                                                    wire:click="gotoPage({{ $page }})" 
                                                    class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800" 
                                                    aria-label="前往第 {{ $page }} 頁"
                                                >
                                                    {{ $page }}
                                                </button>
                                            @endif
                                        @endfor

                                        {{-- 下一頁按鈕 --}}
                                        @if ($permissions->hasMorePages())
                                            <button 
                                                wire:click="nextPage" 
                                                class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800" 
                                                aria-label="下一頁"
                                            >
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        @else
                                            <span aria-disabled="true" aria-label="下一頁">
                                                <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md leading-5 dark:bg-gray-800 dark:border-gray-600" aria-hidden="true">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

<script>
// Alpine.js 重置按鈕控制器
function resetButtonController() {
    return {
        showResetButton: @js(!empty($search) || $moduleFilter !== 'all' || $typeFilter !== 'all' || $usageFilter !== 'all'),
        
        init() {
            console.log('🔧 重置按鈕控制器初始化');
            
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
            const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
            const moduleSelect = document.querySelector('select[wire\\:model\\.live="moduleFilter"]');
            const typeSelect = document.querySelector('select[wire\\:model\\.live="typeFilter"]');
            const usageSelect = document.querySelector('select[wire\\:model\\.live="usageFilter"]');
            
            const hasSearch = searchInput && searchInput.value.trim() !== '';
            const hasModuleFilter = moduleSelect && moduleSelect.value !== 'all';
            const hasTypeFilter = typeSelect && typeSelect.value !== 'all';
            const hasUsageFilter = usageSelect && usageSelect.value !== 'all';
            
            this.showResetButton = hasSearch || hasModuleFilter || hasTypeFilter || hasUsageFilter;
            
            console.log('🔍 檢查篩選狀態:', {
                hasSearch,
                hasModuleFilter,
                hasTypeFilter,
                hasUsageFilter,
                showResetButton: this.showResetButton
            });
        },
        
        resetFormElements() {
            console.log('🔄 Alpine.js 開始重置表單元素');
            
            // 重置所有搜尋框（包括手機版和桌面版）
            const searchInputs = document.querySelectorAll('input[wire\\:model\\.live="search"], input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
            searchInputs.forEach((input, index) => {
                console.log(`Alpine.js 重置搜尋框 ${index + 1}`);
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.blur();
            });
            
            // 重置模組篩選器
            const moduleSelects = document.querySelectorAll('select[wire\\:model\\.live="moduleFilter"]');
            moduleSelects.forEach((select, index) => {
                console.log(`Alpine.js 重置模組篩選器 ${index + 1}:`, select.value, '→ all');
                select.value = 'all';
                select.selectedIndex = 0; // 強制設定選中索引
                select.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('Alpine.js 模組篩選器重置後:', select.value, select.options[select.selectedIndex].text);
            });
            
            // 重置類型篩選器
            const typeSelects = document.querySelectorAll('select[wire\\:model\\.live="typeFilter"]');
            typeSelects.forEach((select, index) => {
                console.log(`Alpine.js 重置類型篩選器 ${index + 1}:`, select.value, '→ all');
                select.value = 'all';
                select.selectedIndex = 0;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            // 重置使用狀態篩選器
            const usageSelects = document.querySelectorAll('select[wire\\:model\\.live="usageFilter"]');
            usageSelects.forEach((select, index) => {
                console.log(`Alpine.js 重置使用狀態篩選器 ${index + 1}:`, select.value, '→ all');
                select.value = 'all';
                select.selectedIndex = 0;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            // 更新重置按鈕狀態
            setTimeout(() => {
                this.checkFilters();
                console.log('✅ Alpine.js 表單元素重置完成');
            }, 150);
        }
    }
}

document.addEventListener('livewire:initialized', () => {
    console.log('🔧 權限列表 JavaScript 初始化');
    
    // 監聽強制表單重置事件
    Livewire.on('force-form-reset', (data) => {
        console.log('🔄 強制重置表單元素', data);
        setTimeout(() => {
            // 重置搜尋框 - 使用新的選擇器
            const searchInputs = document.querySelectorAll('input[wire\\:model\\.live="search"]');
            searchInputs.forEach(input => {
                console.log('🔄 重置搜尋框:', input.value, '->', '');
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            // 重置所有篩選 select 元素
            const moduleSelects = document.querySelectorAll('select[wire\\:model*="moduleFilter"]');
            moduleSelects.forEach(select => {
                console.log('🔄 重置模組篩選:', select.value, '->', 'all');
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            const typeSelects = document.querySelectorAll('select[wire\\:model*="typeFilter"]');
            typeSelects.forEach(select => {
                console.log('🔄 重置類型篩選:', select.value, '->', 'all');
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            const usageSelects = document.querySelectorAll('select[wire\\:model*="usageFilter"]');
            usageSelects.forEach(select => {
                console.log('🔄 重置使用篩選:', select.value, '->', 'all');
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            console.log('✅ 表單元素已重置');
        }, 200);
    });
    
    // 監聽篩選重置完成事件
    Livewire.on('filters-reset', () => {
        console.log('🔄 篩選器已重置，等待 UI 更新');
        setTimeout(() => {
            // 檢查重置按鈕是否正確隱藏
            const resetButtons = document.querySelectorAll('button[wire\\:click="resetFilters"]');
            resetButtons.forEach(button => {
                if (button.offsetParent !== null) {
                    console.log('⚠️ 重置按鈕仍然可見，可能需要手動隱藏');
                }
            });
        }, 1000);
    });
});
</script>
</div>