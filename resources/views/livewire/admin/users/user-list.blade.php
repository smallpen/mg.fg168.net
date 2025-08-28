<div class="space-y-6">

    {{-- 使用者統計資訊 --}}
    <livewire:admin.users.user-stats />

    {{-- 搜尋和篩選區域 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 sm:p-6">
        {{-- 手機版：垂直佈局 --}}
        <div class="block sm:hidden space-y-4">
            {{-- 搜尋框 --}}
            <div>
                <label for="search-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin.users.search') }}
                </label>
                <div class="relative">
                    <input type="text" 
                           id="search-mobile"
                           wire:model.live="search" 
                           wire:key="search-mobile-input"
                           placeholder="{{ __('admin.users.search_placeholder') }}"
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
                {{-- 角色篩選 --}}
                <div>
                    <label for="roleFilter-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin.users.role') }}
                    </label>
                    <div class="relative">
                        <select id="roleFilter-mobile" 
                                wire:model.live="roleFilter"
                                wire:key="role-filter-mobile-select"
                                class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                            <option value="all">{{ __('admin.users.all_roles') }}</option>
                            @foreach($availableRoles as $role)
                                <option value="{{ $role->name }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="roleFilter" class="absolute inset-y-0 right-8 flex items-center">
                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- 狀態篩選 --}}
                <div>
                    <label for="statusFilter-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin.users.status') }}
                    </label>
                    <div class="relative">
                        <select id="statusFilter-mobile" 
                                wire:model.live="statusFilter"
                                wire:key="status-filter-mobile-select"
                                class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="statusFilter" class="absolute inset-y-0 right-8 flex items-center">
                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 清除篩選按鈕 --}}
            @if($search || $roleFilter !== 'all' || $statusFilter !== 'all')
            <div class="flex justify-center" wire:key="mobile-reset-filters">
                <button wire:click="resetFilters" 
                        wire:key="mobile-reset-button"
                        class="inline-flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('admin.users.clear_filters') }}
                </button>
            </div>
            @endif
        </div>

        {{-- 平板和桌面版：水平佈局 --}}
        <div class="hidden sm:block">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- 搜尋框 --}}
                <div class="sm:col-span-2 lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin.users.search') }}
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="search"
                               wire:model.live="search" 
                               wire:key="search-desktop-input"
                               placeholder="{{ __('admin.users.search_placeholder') }}"
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

                {{-- 角色篩選 --}}
                <div>
                    <label for="roleFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin.users.filter_by_role') }}
                    </label>
                    <div class="relative">
                        <select id="roleFilter" 
                                wire:model.live="roleFilter"
                                wire:key="role-filter-desktop-select"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="all">{{ __('admin.users.all_roles') }}</option>
                            @foreach($availableRoles as $role)
                                <option value="{{ $role->name }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="roleFilter" class="absolute inset-y-0 right-8 flex items-center">
                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- 狀態篩選 --}}
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin.users.filter_by_status') }}
                    </label>
                    <div class="relative">
                        <select id="statusFilter" 
                                wire:model.live="statusFilter"
                                wire:key="status-filter-desktop-select"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="statusFilter" class="absolute inset-y-0 right-8 flex items-center">
                            <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 清除篩選按鈕 --}}
            @if($search || $roleFilter !== 'all' || $statusFilter !== 'all')
            <div class="mt-4 flex justify-end" wire:key="desktop-reset-filters">
                <button wire:click="resetFilters" 
                        wire:key="desktop-reset-button"
                        class="inline-flex items-center px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('admin.users.clear_filters') }}
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- 批量操作工具列 --}}
    @if(count($selectedUsers) > 0)
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        {{-- 手機版：垂直佈局 --}}
        <div class="block sm:hidden space-y-3">
            <div class="text-center">
                <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                    {{ __('admin.users.selected_users', ['count' => count($selectedUsers)]) }}
                </span>
            </div>
            <div class="grid grid-cols-1 gap-2">
                @if($this->hasPermission('update'))
                    <button wire:click="bulkActivate" 
                            wire:loading.attr="disabled"
                            wire:target="bulkActivate"
                            class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="bulkActivate" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading wire:target="bulkActivate" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('admin.users.bulk_activate') }}
                    </button>
                    <button wire:click="bulkDeactivate" 
                            wire:loading.attr="disabled"
                            wire:target="bulkDeactivate"
                            class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="bulkDeactivate" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <svg wire:loading wire:target="bulkDeactivate" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('admin.users.bulk_deactivate') }}
                    </button>
                @endif
                <button wire:click="resetFilters" 
                        wire:key="mobile-bulk-reset-button"
                        class="w-full inline-flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    {{ __('admin.users.cancel_selection') }}
                </button>
            </div>
        </div>

        {{-- 平板和桌面版：水平佈局 --}}
        <div class="hidden sm:flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-blue-700 dark:text-blue-300">
                    {{ __('admin.users.selected_users', ['count' => count($selectedUsers)]) }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                @if($this->hasPermission('update'))
                    <button wire:click="bulkActivate" 
                            wire:loading.attr="disabled"
                            wire:target="bulkActivate"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="bulkActivate" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading wire:target="bulkActivate" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('admin.users.bulk_activate') }}
                    </button>
                    <button wire:click="bulkDeactivate" 
                            wire:loading.attr="disabled"
                            wire:target="bulkDeactivate"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="bulkDeactivate" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <svg wire:loading wire:target="bulkDeactivate" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('admin.users.bulk_deactivate') }}
                    </button>
                @endif
                <button wire:click="resetFilters" 
                        wire:key="desktop-bulk-reset-button"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    {{ __('admin.users.cancel_selection') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- 使用者列表 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        {{-- 載入狀態覆蓋層 --}}
        <div wire:loading wire:target="search,statusFilter,roleFilter,sortBy" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 z-10 flex items-center justify-center">
            <div class="flex items-center space-x-2">
                <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.users.loading') }}</span>
            </div>
        </div>

        {{-- 桌面版表格 --}}
        <div class="hidden xl:block overflow-x-auto">
            {{-- 完整桌面版表格 (≥1280px) --}}
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        {{-- 批量選擇 --}}
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" 
                                   wire:model.live="selectAll"
                                   wire:click="toggleSelectAll"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </th>

                        {{-- 使用者名稱 --}}
                        <th class="px-6 py-3 text-left">
                            <button wire:click="sortBy('username')" 
                                    class="flex items-center space-x-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-200">
                                <span>{{ __('admin.users.username') }}</span>
                                @if($sortField === 'username')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'transform rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                @endif
                            </button>
                        </th>

                        {{-- 姓名 --}}
                        <th class="px-6 py-3 text-left">
                            <button wire:click="sortBy('name')" 
                                    class="flex items-center space-x-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-200">
                                <span>{{ __('admin.users.name') }}</span>
                                @if($sortField === 'name')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'transform rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                @endif
                            </button>
                        </th>

                        {{-- 電子郵件 --}}
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('admin.users.email') }}
                        </th>

                        {{-- 角色 --}}
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('admin.users.roles') }}
                        </th>

                        {{-- 狀態 --}}
                        <th class="px-6 py-3 text-left">
                            <button wire:click="sortBy('is_active')" 
                                    class="flex items-center space-x-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-200">
                                <span>{{ __('admin.users.status') }}</span>
                                @if($sortField === 'is_active')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'transform rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                @endif
                            </button>
                        </th>

                        {{-- 建立時間 --}}
                        <th class="px-6 py-3 text-left">
                            <button wire:click="sortBy('created_at')" 
                                    class="flex items-center space-x-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-200">
                                <span>{{ __('admin.users.created_at') }}</span>
                                @if($sortField === 'created_at')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'transform rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                @endif
                            </button>
                        </th>

                        {{-- 操作 --}}
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('admin.users.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr wire:key="user-{{ $user->id }}-{{ $loop->index }}" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            {{-- 批量選擇 --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" 
                                       value="{{ $user->id }}"
                                       wire:model.live="selectedUsers"
                                       wire:click="toggleUserSelection({{ $user->id }})"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>

                            {{-- 使用者名稱 --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full" 
                                             src="{{ $user->avatar_url }}" 
                                             alt="{{ $user->display_name }}"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center" style="display: none;">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ mb_strtoupper(mb_substr($user->display_name, 0, 1, 'UTF-8'), 'UTF-8') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $user->username }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- 姓名 --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $user->name ?: '-' }}
                                </div>
                            </td>

                            {{-- 電子郵件 --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $user->email ?: '-' }}
                                </div>
                            </td>

                            {{-- 角色 --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                   {{ $role->name === 'super_admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                      ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                            {{ $role->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.users.no_role') }}</span>
                                    @endforelse
                                    @if($user->roles->count() > 1)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            +{{ $user->roles->count() - 1 }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- 狀態 --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($this->hasPermission('update') && $user->id !== auth()->id())
                                    <button wire:click="toggleUserStatus({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleUserStatus({{ $user->id }})"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-200 disabled:opacity-50
                                                   {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800' : 
                                                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-800' }}">
                                        <span wire:loading.remove wire:target="toggleUserStatus({{ $user->id }})" class="w-2 h-2 mr-1 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                        <svg wire:loading wire:target="toggleUserStatus({{ $user->id }})" class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                   'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        <span class="w-2 h-2 mr-1 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                        {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                    </span>
                                @endif
                            </td>

                            {{-- 建立時間 --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $user->formatted_created_at }}
                            </td>

                            {{-- 操作 --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($this->hasPermission('view'))
                                        <button wire:click="viewUser({{ $user->id }})" 
                                                wire:loading.attr="disabled"
                                                wire:target="viewUser({{ $user->id }})"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200 disabled:opacity-50"
                                                title="{{ __('admin.actions.view') }}">
                                            <svg wire:loading.remove wire:target="viewUser({{ $user->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg wire:loading wire:target="viewUser({{ $user->id }})" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    @endif

                                    @if($this->hasPermission('update'))
                                        <button wire:click="editUser({{ $user->id }})" 
                                                wire:loading.attr="disabled"
                                                wire:target="editUser({{ $user->id }})"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-200 disabled:opacity-50"
                                                title="{{ __('admin.actions.edit') }}">
                                            <svg wire:loading.remove wire:target="editUser({{ $user->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <svg wire:loading wire:target="editUser({{ $user->id }})" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    @endif

                                    @if($this->hasPermission('delete') && $user->id !== auth()->id())
                                        <button wire:click="deleteUser({{ $user->id }})" 
                                                wire:loading.attr="disabled"
                                                wire:target="deleteUser({{ $user->id }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200 disabled:opacity-50"
                                                title="{{ __('admin.actions.delete') }}">
                                            <svg wire:loading.remove wire:target="deleteUser({{ $user->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <svg wire:loading wire:target="deleteUser({{ $user->id }})" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        {{ __('admin.users.no_users') }}
                                    </h3>
                                    <p class="text-gray-500 dark:text-gray-400">
                                        {{ __('admin.users.search_help') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 平板版簡化表格 (1024px-1279px) --}}
        <div class="hidden lg:block xl:hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        {{-- 批量選擇 --}}
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" 
                                   wire:model.live="selectAll"
                                   wire:click="toggleSelectAll"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </th>

                        {{-- 使用者資訊 --}}
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sortBy('username')" 
                                    class="flex items-center space-x-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-200">
                                <span>{{ __('admin.users.user') }}</span>
                                @if($sortField === 'username')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'transform rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </button>
                        </th>

                        {{-- 角色 --}}
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('admin.users.roles') }}
                        </th>

                        {{-- 狀態 --}}
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sortBy('is_active')" 
                                    class="flex items-center space-x-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-200">
                                <span>{{ __('admin.users.status') }}</span>
                                @if($sortField === 'is_active')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'transform rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </button>
                        </th>

                        {{-- 操作 --}}
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('admin.users.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr wire:key="user-tablet-{{ $user->id }}-{{ $loop->index }}" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            {{-- 批量選擇 --}}
                            <td class="px-4 py-4 whitespace-nowrap">
                                <input type="checkbox" 
                                       value="{{ $user->id }}"
                                       wire:model.live="selectedUsers"
                                       wire:click="toggleUserSelection({{ $user->id }})"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>

                            {{-- 使用者資訊 --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <img class="h-8 w-8 rounded-full" 
                                             src="{{ $user->avatar_url }}" 
                                             alt="{{ $user->display_name }}"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center" style="display: none;">
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ mb_strtoupper(mb_substr($user->display_name, 0, 1, 'UTF-8'), 'UTF-8') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $user->username }}
                                        </div>
                                        @if($user->name)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $user->name }}
                                            </div>
                                        @endif
                                        @if($user->email)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                {{ $user->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- 角色 --}}
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles->take(2) as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                                   {{ $role->name === 'super_admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                      ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                            {{ $role->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.users.no_role') }}</span>
                                    @endforelse
                                    @if($user->roles->count() > 2)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            +{{ $user->roles->count() - 2 }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- 狀態 --}}
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($this->hasPermission('update') && $user->id !== auth()->id())
                                    <button wire:click="toggleUserStatus({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleUserStatus({{ $user->id }})"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors duration-200 disabled:opacity-50
                                                   {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800' : 
                                                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-800' }}">
                                        <span wire:loading.remove wire:target="toggleUserStatus({{ $user->id }})" class="w-1.5 h-1.5 mr-1 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                        <svg wire:loading wire:target="toggleUserStatus({{ $user->id }})" class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                   'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        <span class="w-1.5 h-1.5 mr-1 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                        {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                    </span>
                                @endif
                            </td>

                            {{-- 操作 --}}
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-1">
                                    @if($this->hasPermission('view'))
                                        <button wire:click="viewUser({{ $user->id }})" 
                                                class="p-1 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors duration-200"
                                                title="{{ __('admin.actions.view') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    @endif

                                    @if($this->hasPermission('update'))
                                        <button wire:click="editUser({{ $user->id }})" 
                                                class="p-1 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded transition-colors duration-200"
                                                title="{{ __('admin.actions.edit') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    @endif

                                    @if($this->hasPermission('delete') && $user->id !== auth()->id())
                                        <button wire:click="deleteUser({{ $user->id }})" 
                                                class="p-1 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors duration-200"
                                                title="{{ __('admin.actions.delete') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        {{ __('admin.users.no_users') }}
                                    </h3>
                                    <p class="text-gray-500 dark:text-gray-400">
                                        {{ __('admin.users.search_help') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 手機和小平板版卡片式佈局 (≤1023px) --}}
        <div class="lg:hidden">
            @forelse($users as $user)
                <div wire:key="user-mobile-{{ $user->id }}-{{ $loop->index }}" class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                    {{-- 手機版卡片 (≤640px) --}}
                    <div class="block sm:hidden p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <div class="flex items-start space-x-3">
                            {{-- 批量選擇 --}}
                            <div class="flex-shrink-0 pt-1">
                                <input type="checkbox" 
                                       value="{{ $user->id }}"
                                       wire:model.live="selectedUsers"
                                       wire:click="toggleUserSelection({{ $user->id }})"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </div>
                            
                            {{-- 使用者頭像 --}}
                            <div class="flex-shrink-0">
                                <img class="h-12 w-12 rounded-full" 
                                     src="{{ $user->avatar_url }}" 
                                     alt="{{ $user->display_name }}"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="h-12 w-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center" style="display: none;">
                                    <span class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                        {{ mb_strtoupper(mb_substr($user->display_name, 0, 1, 'UTF-8'), 'UTF-8') }}
                                    </span>
                                </div>
                            </div>
                            
                            {{-- 使用者資訊 --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white truncate">
                                        {{ $user->username }}
                                    </h3>
                                    {{-- 狀態標籤 --}}
                                    @if($this->hasPermission('update') && $user->id !== auth()->id())
                                        <button wire:click="toggleUserStatus({{ $user->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleUserStatus({{ $user->id }})"
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium transition-colors duration-200 disabled:opacity-50
                                                       {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800' : 
                                                          'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-800' }}">
                                            <span wire:loading.remove wire:target="toggleUserStatus({{ $user->id }})" class="w-2 h-2 mr-1.5 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                            <svg wire:loading wire:target="toggleUserStatus({{ $user->id }})" class="animate-spin w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                                    {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                       'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            <span class="w-2 h-2 mr-1.5 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                            {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                        </span>
                                    @endif
                                </div>
                                
                                @if($user->name)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate mt-1">
                                        {{ $user->name }}
                                    </p>
                                @endif
                                
                                @if($user->email)
                                    <p class="text-sm text-gray-500 dark:text-gray-500 truncate mt-1">
                                        {{ $user->email }}
                                    </p>
                                @endif
                                
                                {{-- 角色標籤 --}}
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @forelse($user->roles->take(2) as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                   {{ $role->name === 'super_admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                      ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                            {{ $role->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.users.no_role') }}</span>
                                    @endforelse
                                    @if($user->roles->count() > 2)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            +{{ $user->roles->count() - 2 }}
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ $user->formatted_created_at }}
                                </p>
                                
                                {{-- 操作按鈕 --}}
                                <div class="flex items-center space-x-2 mt-3">
                                    @if($this->hasPermission('view'))
                                        <button wire:click="viewUser({{ $user->id }})" 
                                                class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 rounded-lg transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            {{ __('admin.actions.view') }}
                                        </button>
                                    @endif

                                    @if($this->hasPermission('update'))
                                        <button wire:click="editUser({{ $user->id }})" 
                                                class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/40 rounded-lg transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            {{ __('admin.actions.edit') }}
                                        </button>
                                    @endif

                                    @if($this->hasPermission('delete') && $user->id !== auth()->id())
                                        <button wire:click="deleteUser({{ $user->id }})" 
                                                class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 rounded-lg transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            {{ __('admin.actions.delete') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 小平板版卡片 (641px-1023px) --}}
                    <div class="hidden sm:block lg:hidden p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3 flex-1">
                            {{-- 批量選擇 --}}
                            <input type="checkbox" 
                                   value="{{ $user->id }}"
                                   wire:model.live="selectedUsers"
                                   wire:click="toggleUserSelection({{ $user->id }})"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            
                            {{-- 使用者頭像 --}}
                            <div class="flex-shrink-0">
                                <img class="h-12 w-12 rounded-full" 
                                     src="{{ $user->avatar_url }}" 
                                     alt="{{ $user->display_name }}"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="h-12 w-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center" style="display: none;">
                                    <span class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                        {{ mb_strtoupper(mb_substr($user->display_name, 0, 1, 'UTF-8'), 'UTF-8') }}
                                    </span>
                                </div>
                            </div>
                            
                            {{-- 使用者資訊 --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $user->username }}
                                    </h3>
                                    {{-- 狀態標籤 --}}
                                    @if($this->hasPermission('update') && $user->id !== auth()->id())
                                        <button wire:click="toggleUserStatus({{ $user->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleUserStatus({{ $user->id }})"
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors duration-200 disabled:opacity-50
                                                       {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800' : 
                                                          'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-800' }}">
                                            <span wire:loading.remove wire:target="toggleUserStatus({{ $user->id }})" class="w-1.5 h-1.5 mr-1 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                            <svg wire:loading wire:target="toggleUserStatus({{ $user->id }})" class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                    {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                       'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            <span class="w-1.5 h-1.5 mr-1 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                            {{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                                        </span>
                                    @endif
                                </div>
                                
                                @if($user->name)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ $user->name }}
                                    </p>
                                @endif
                                
                                @if($user->email)
                                    <p class="text-sm text-gray-500 dark:text-gray-500 truncate">
                                        {{ $user->email }}
                                    </p>
                                @endif
                                
                                {{-- 角色標籤 --}}
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @forelse($user->roles->take(2) as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                   {{ $role->name === 'super_admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                      ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                            {{ $role->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.users.no_role') }}</span>
                                    @endforelse
                                    @if($user->roles->count() > 2)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            +{{ $user->roles->count() - 2 }}
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $user->formatted_created_at }}
                                </p>
                            </div>
                        </div>
                        
                        {{-- 操作按鈕 --}}
                        <div class="flex items-center space-x-2 ml-4">
                            @if($this->hasPermission('view'))
                                <button wire:click="viewUser({{ $user->id }})" 
                                        class="p-2 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200"
                                        title="{{ __('admin.actions.view') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            @endif

                            @if($this->hasPermission('update'))
                                <button wire:click="editUser({{ $user->id }})" 
                                        class="p-2 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors duration-200"
                                        title="{{ __('admin.actions.edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            @endif

                            @if($this->hasPermission('delete') && $user->id !== auth()->id())
                                <button wire:click="deleteUser({{ $user->id }})" 
                                        class="p-2 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200"
                                        title="{{ __('admin.actions.delete') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('admin.users.no_users') }}
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 text-center">
                            {{ __('admin.users.search_help') }}
                        </p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- 分頁 --}}
        @if($users->hasPages())
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- 使用者刪除確認對話框 --}}
    <livewire:admin.users.user-delete-modal />
</div>

@script
<script>
    // 監聽篩選重置事件
    $wire.on('filters-reset', () => {
        // 確保所有搜尋框都被清空
        const searchInputs = document.querySelectorAll('input[wire\\:model\\.defer="search"]');
        searchInputs.forEach(input => {
            if (input.value !== '') {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.blur();
            }
        });
        
        // 確保所有篩選下拉選單都重置為預設值
        const roleFilter = document.getElementById('roleFilter');
        const roleFilterMobile = document.getElementById('roleFilter-mobile');
        const statusFilter = document.getElementById('statusFilter');
        const statusFilterMobile = document.getElementById('statusFilter-mobile');
        
        [roleFilter, roleFilterMobile].forEach(select => {
            if (select && select.value !== 'all') {
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
                select.blur();
            }
        });
        
        [statusFilter, statusFilterMobile].forEach(select => {
            if (select && select.value !== 'all') {
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
                select.blur();
            }
        });
    });

    // 監聽新的使用者列表重置事件
    $wire.on('user-list-reset', () => {
        console.log('🔄 收到 user-list-reset 事件，手動更新前端...');
        
        // 清空所有搜尋框
        const searchInputs = document.querySelectorAll('#search, #search-mobile');
        searchInputs.forEach(input => {
            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.blur();
            }
        });
        
        // 重置所有篩選下拉選單
        const selects = document.querySelectorAll('#roleFilter, #roleFilter-mobile, #statusFilter, #statusFilter-mobile');
        selects.forEach(select => {
            if (select) {
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
                select.blur();
            }
        });
        
        // 延遲刷新以確保同步
        setTimeout(() => {
            console.log('🔄 延遲刷新執行');
            // 觸發 Livewire 重新渲染
            $wire.$refresh();
        }, 300);
    });

    // 為搜尋框添加手動觸發事件
    document.addEventListener('DOMContentLoaded', function() {
        const searchInputs = document.querySelectorAll('#search, #search-mobile');
        searchInputs.forEach(input => {
            if (input) {
                input.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter') {
                        this.blur();
                        $wire.$refresh();
                    }
                });
            }
        });
        
        const selects = document.querySelectorAll('#roleFilter, #roleFilter-mobile, #statusFilter, #statusFilter-mobile');
        selects.forEach(select => {
            if (select) {
                select.addEventListener('change', function() {
                    this.blur();
                    setTimeout(() => {
                        try {
                            $wire.$refresh();
                        } catch (error) {
                            console.error('🚨 Livewire 刷新錯誤:', error);
                            // 如果刷新失敗，嘗試重新載入頁面
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    }, 100);
                });
            }
        });
        
        // 監聽 Livewire 錯誤事件
        document.addEventListener('livewire:error', function(event) {
            console.error('❌ Livewire 錯誤:', event.detail);
            
            // 如果是 DOM 操作錯誤，嘗試修復
            if (event.detail.message && event.detail.message.includes('Cannot read properties of null')) {
                console.log('🔧 檢測到 DOM 操作錯誤，嘗試修復...');
                
                // 嘗試呼叫修復方法
                try {
                    $wire.fixDomState();
                } catch (fixError) {
                    console.error('修復失敗:', fixError);
                    // 最後手段：重新載入頁面
                    setTimeout(() => window.location.reload(), 2000);
                }
            }
        });
        
        // 監聽自定義重置事件
        document.addEventListener('user-list-reset', function() {
            console.log('🔄 使用者列表重置事件觸發');
            
            // 重置所有表單元素的值
            setTimeout(() => {
                const searchInputs = document.querySelectorAll('input[wire\\:model\\.live="search"]');
                searchInputs.forEach(input => {
                    if (input.value !== '') {
                        input.value = '';
                    }
                });
                
                const statusSelects = document.querySelectorAll('select[wire\\:model\\.live="statusFilter"]');
                statusSelects.forEach(select => {
                    if (select.value !== 'all') {
                        select.value = 'all';
                    }
                });
                
                const roleSelects = document.querySelectorAll('select[wire\\:model\\.live="roleFilter"]');
                roleSelects.forEach(select => {
                    if (select.value !== 'all') {
                        select.value = 'all';
                    }
                });
            }, 100);
        });
    });
    
    // 全域錯誤處理
    window.addEventListener('error', function(event) {
        if (event.message && event.message.includes('Cannot read properties of null')) {
            console.error('🚨 檢測到 DOM 操作錯誤:', event.message);
            console.log('📍 錯誤位置:', event.filename, '行號:', event.lineno);
            console.trace('錯誤堆疊追蹤');
        }
    });
</script>
@endscript