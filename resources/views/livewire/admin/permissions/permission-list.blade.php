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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 sm:p-6">
        {{-- 手機版：垂直佈局 --}}
        <div class="block lg:hidden space-y-4">
            {{-- 搜尋框 --}}
            <div>
                <label for="search-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('permissions.search_label') }}
                </label>
                <div class="relative">
                    <input type="text" 
                           id="search-mobile"
                           wire:model.live.debounce.500ms="search" 
                           placeholder="{{ __('permissions.search.search_placeholder') }}"
                           class="w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div wire:loading wire:target="search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- 篩選器行 --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- 模組篩選 --}}
                <div>
                    <label for="moduleFilter-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.module') }}
                    </label>
                    <select id="moduleFilter-mobile" 
                            wire:model.live="moduleFilter"
                            class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                        <option value="all">{{ __('permissions.all_modules') }}</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}">{{ ucfirst($module) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 類型篩選 --}}
                <div>
                    <label for="typeFilter-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.type') }}
                    </label>
                    <select id="typeFilter-mobile" 
                            wire:model.live="typeFilter"
                            class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                        <option value="all">{{ __('permissions.all_types') }}</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ $this->getLocalizedType($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- 使用狀態和檢視模式 --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- 使用狀態篩選 --}}
                <div>
                    <label for="usageFilter-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.usage_status') }}
                    </label>
                    <select id="usageFilter-mobile" 
                            wire:model.live="usageFilter"
                            class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                        @foreach($usageOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 檢視模式 --}}
                <div>
                    <label for="viewMode-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.view_mode') }}
                    </label>
                    <select id="viewMode-mobile" 
                            wire:model.live="viewMode"
                            class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                        @foreach($viewModeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- 操作按鈕 --}}
            <div class="flex flex-col space-y-2">
                @if($this->hasPermission('create'))
                    <button wire:click="createPermission" 
                            class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ __('permissions.create_permission') }}
                    </button>
                @endif
                
                <div class="grid grid-cols-2 gap-2">
                    @if($this->hasPermission('export'))
                        <button wire:click="exportPermissions" 
                                class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('permissions.export') }}
                        </button>
                    @endif
                    
                    @if($this->hasPermission('import'))
                        <button wire:click="importPermissions" 
                                class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            {{ __('permissions.import') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- 清除篩選按鈕 --}}
            @if($search || $moduleFilter !== 'all' || $typeFilter !== 'all' || $usageFilter !== 'all')
            <div class="flex justify-center">
                <button wire:click="resetFilters" 
                        class="inline-flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('permissions.clear_filters') }}
                </button>
            </div>
            @endif
        </div>

        {{-- 桌面版：水平佈局 --}}
        <div class="hidden lg:block">
            <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 mb-4">
                {{-- 搜尋框 --}}
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.search_label') }}
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="search"
                               wire:model.live.debounce.500ms="search" 
                               placeholder="{{ __('permissions.search.search_placeholder') }}"
                               class="w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <div wire:loading wire:target="search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- 模組篩選 --}}
                <div>
                    <label for="moduleFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.filter_by_module') }}
                    </label>
                    <select id="moduleFilter" 
                            wire:model.live="moduleFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="all">{{ __('permissions.all_modules') }}</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}">{{ ucfirst($module) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 類型篩選 --}}
                <div>
                    <label for="typeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.filter_by_type') }}
                    </label>
                    <select id="typeFilter" 
                            wire:model.live="typeFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="all">{{ __('permissions.all_types') }}</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ $this->getLocalizedType($type) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 使用狀態篩選 --}}
                <div>
                    <label for="usageFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.filter_by_usage') }}
                    </label>
                    <select id="usageFilter" 
                            wire:model.live="usageFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        @foreach($usageOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 檢視模式 --}}
                <div>
                    <label for="viewMode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('permissions.view_mode') }}
                    </label>
                    <select id="viewMode" 
                            wire:model.live="viewMode"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        @foreach($viewModeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- 操作按鈕行 --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    @if($this->hasPermission('create'))
                        <button wire:click="createPermission" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            {{ __('permissions.create_permission') }}
                        </button>
                    @endif
                    
                    @if($this->hasPermission('export'))
                        <button wire:click="exportPermissions" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('permissions.export') }}
                        </button>
                    @endif
                    
                    @if($this->hasPermission('import'))
                        <button wire:click="importPermissions" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            {{ __('permissions.import') }}
                        </button>
                    @endif
                </div>

                {{-- 清除篩選按鈕 --}}
                @if($search || $moduleFilter !== 'all' || $typeFilter !== 'all' || $usageFilter !== 'all')
                <button wire:click="resetFilters" 
                        class="inline-flex items-center px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('permissions.clear_filters') }}
                </button>
                @endif
            </div>
        </div>
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
        <div wire:loading wire:target="search,moduleFilter,typeFilter,usageFilter,viewMode,sortBy" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 z-10 flex items-center justify-center">
            <div class="flex items-center space-x-2">
                <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('permissions.loading') }}</span>
            </div>
        </div>

        @if($viewMode === 'list')
            {{-- 列表檢視 --}}
            @include('livewire.admin.permissions.partials.list-view')
        @elseif($viewMode === 'grouped')
            {{-- 分組檢視 --}}
            @include('livewire.admin.permissions.partials.grouped-view')
        @elseif($viewMode === 'tree')
            {{-- 樹狀檢視 --}}
            @include('livewire.admin.permissions.partials.tree-view')
        @endif
    </div>

    {{-- 權限表單元件 --}}
    <livewire:admin.permissions.permission-form />

    {{-- 權限依賴關係圖表元件 --}}
    <livewire:admin.permissions.dependency-graph />

    {{-- 權限刪除確認對話框 --}}
    <livewire:admin.permissions.permission-delete-modal />
</div>

<script>
document.addEventListener('livewire:init', () => {
    // 權限管理頁面鍵盤導航支援
    let currentRowIndex = -1;
    let isNavigating = false;
    
    // 取得所有可導航的行
    function getNavigableRows() {
        return document.querySelectorAll('tbody tr:not(.hidden)');
    }
    
    // 取得行內的可操作按鈕
    function getRowButtons(row) {
        return row.querySelectorAll('button:not([disabled])');
    }
    
    // 高亮顯示當前行
    function highlightRow(index) {
        const rows = getNavigableRows();
        
        // 移除所有高亮
        rows.forEach(row => {
            row.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-500');
        });
        
        // 高亮當前行
        if (index >= 0 && index < rows.length) {
            const currentRow = rows[index];
            currentRow.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-500');
            currentRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    // 鍵盤事件處理
    document.addEventListener('keydown', function(e) {
        const rows = getNavigableRows();
        if (rows.length === 0) return;
        
        // 檢查是否在輸入框中
        const activeElement = document.activeElement;
        if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'SELECT')) {
            return;
        }
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                isNavigating = true;
                currentRowIndex = Math.min(currentRowIndex + 1, rows.length - 1);
                highlightRow(currentRowIndex);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                isNavigating = true;
                currentRowIndex = Math.max(currentRowIndex - 1, 0);
                highlightRow(currentRowIndex);
                break;
                
            case 'Home':
                e.preventDefault();
                isNavigating = true;
                currentRowIndex = 0;
                highlightRow(currentRowIndex);
                break;
                
            case 'End':
                e.preventDefault();
                isNavigating = true;
                currentRowIndex = rows.length - 1;
                highlightRow(currentRowIndex);
                break;
                
            case 'Enter':
                if (isNavigating && currentRowIndex >= 0) {
                    e.preventDefault();
                    const currentRow = rows[currentRowIndex];
                    const editButton = currentRow.querySelector('button[wire\\:click*="editPermission"]');
                    if (editButton) {
                        editButton.click();
                    }
                }
                break;
                
            case 'Delete':
                if (isNavigating && currentRowIndex >= 0) {
                    e.preventDefault();
                    const currentRow = rows[currentRowIndex];
                    const deleteButton = currentRow.querySelector('button[wire\\:click*="deletePermission"]:not([disabled])');
                    if (deleteButton) {
                        deleteButton.click();
                    }
                }
                break;
                
            case 'Escape':
                if (isNavigating) {
                    e.preventDefault();
                    isNavigating = false;
                    currentRowIndex = -1;
                    highlightRow(-1);
                }
                break;
                
            case 'Tab':
                if (isNavigating && currentRowIndex >= 0) {
                    e.preventDefault();
                    const currentRow = rows[currentRowIndex];
                    const buttons = getRowButtons(currentRow);
                    if (buttons.length > 0) {
                        buttons[0].focus();
                        isNavigating = false;
                        highlightRow(-1);
                    }
                }
                break;
                
            // 快捷鍵
            case 'n':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    const createButton = document.querySelector('button[wire\\:click="createPermission"]');
                    if (createButton) {
                        createButton.click();
                    }
                }
                break;
                
            case 'f':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    const searchInput = document.querySelector('input[wire\\:model*="search"]');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }
                break;
                
            case 'r':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    const resetButton = document.querySelector('button[wire\\:click="resetFilters"]');
                    if (resetButton) {
                        resetButton.click();
                    }
                }
                break;
        }
    });
    
    // 滑鼠點擊時取消鍵盤導航模式
    document.addEventListener('click', function() {
        if (isNavigating) {
            isNavigating = false;
            currentRowIndex = -1;
            highlightRow(-1);
        }
    });
    
    // 顯示鍵盤快捷鍵幫助
    function showKeyboardHelp() {
        const helpModal = document.createElement('div');
        helpModal.className = 'fixed inset-0 z-50 overflow-y-auto';
        helpModal.innerHTML = `
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="this.parentElement.parentElement.remove()"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                鍵盤快捷鍵
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">導航</h4>
                                        <div class="space-y-1 text-gray-600 dark:text-gray-400">
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">↑↓</kbd> 上下移動</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Home</kbd> 第一行</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">End</kbd> 最後一行</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Enter</kbd> 編輯</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Delete</kbd> 刪除</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Tab</kbd> 聚焦按鈕</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Esc</kbd> 取消導航</div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">快捷鍵</h4>
                                        <div class="space-y-1 text-gray-600 dark:text-gray-400">
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+N</kbd> 新增權限</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+F</kbd> 搜尋</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+R</kbd> 重設篩選</div>
                                            <div><kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">?</kbd> 顯示幫助</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" onclick="this.closest('.fixed').remove()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(helpModal);
    }
    
    // ? 鍵顯示幫助
    document.addEventListener('keydown', function(e) {
        if (e.key === '?' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (!activeElement || (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA')) {
                e.preventDefault();
                showKeyboardHelp();
            }
        }
    });
    
    console.log('🎹 權限管理鍵盤導航已啟用');
    console.log('💡 按 ? 鍵查看快捷鍵幫助');
});
</script>