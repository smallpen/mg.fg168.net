{{-- 樹狀檢視 --}}
<div class="space-y-4 p-4">
    @forelse($groupedPermissions as $module => $permissions)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            {{-- 模組節點 --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <button wire:click="toggleGroup('{{ $module }}')" 
                        class="flex items-center justify-between w-full text-left group">
                    <div class="flex items-center space-x-3">
                        {{-- 展開/收合圖示 --}}
                        <div class="flex-shrink-0">
                            @if(in_array($module, $expandedGroups))
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 transform -rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @endif
                        </div>

                        {{-- 模組圖示 --}}
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7H5m14 14H5"/>
                                </svg>
                            </div>
                        </div>

                        {{-- 模組資訊 --}}
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200">
                                {{ ucfirst($module) }}
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ $permissions->count() }} {{ __('permissions.permissions') }}</span>
                                <span class="flex items-center space-x-1">
                                    <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                    <span>{{ $permissions->where('roles_count', '>', 0)->count() }} {{ __('permissions.active') }}</span>
                                </span>
                                <span class="flex items-center space-x-1">
                                    <div class="w-2 h-2 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                                    <span>{{ $permissions->where('roles_count', 0)->count() }} {{ __('permissions.inactive') }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- 模組統計 --}}
                    <div class="flex items-center space-x-2">
                        {{-- 使用率環形圖 --}}
                        @php
                            $total = $permissions->count();
                            $used = $permissions->where('roles_count', '>', 0)->count();
                            $percentage = $total > 0 ? round(($used / $total) * 100) : 0;
                        @endphp
                        <div class="relative w-12 h-12">
                            <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-200 dark:text-gray-700" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="text-green-500" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="{{ $percentage }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $percentage }}%</span>
                            </div>
                        </div>
                    </div>
                </button>
            </div>

            {{-- 權限樹狀結構 --}}
            @if(in_array($module, $expandedGroups))
                <div class="p-4">
                    {{-- 按類型分組的權限樹 --}}
                    @php
                        $permissionsByType = $permissions->groupBy('type');
                        $typeOrder = ['view', 'create', 'edit', 'delete', 'manage'];
                        $sortedTypes = $typeOrder + $permissionsByType->keys()->diff($typeOrder)->toArray();
                    @endphp

                    <div class="space-y-4">
                        @foreach($sortedTypes as $type)
                            @if($permissionsByType->has($type))
                                @php $typePermissions = $permissionsByType[$type] @endphp
                                <div class="relative">
                                    {{-- 類型節點 --}}
                                    <div class="flex items-center space-x-3 mb-3">
                                        {{-- 連接線 --}}
                                        <div class="flex items-center">
                                            <div class="w-4 h-px bg-gray-300 dark:bg-gray-600"></div>
                                            <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
                                        </div>

                                        {{-- 類型標籤 --}}
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                {{ $this->getLocalizedType($type) }}
                                            </span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                ({{ $typePermissions->count() }})
                                            </span>
                                        </div>
                                    </div>

                                    {{-- 類型下的權限列表 --}}
                                    <div class="ml-6 space-y-2">
                                        @foreach($typePermissions as $index => $permission)
                                            <div class="relative flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 group">
                                                {{-- 樹狀連接線 --}}
                                                <div class="absolute left-0 top-0 bottom-0 flex items-center">
                                                    <div class="w-4 h-px bg-gray-300 dark:bg-gray-600"></div>
                                                    @if($index === $typePermissions->count() - 1)
                                                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                                    @else
                                                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                                    @endif
                                                </div>

                                                {{-- 選擇框 --}}
                                                <input type="checkbox" 
                                                       value="{{ $permission->id }}"
                                                       wire:model.live="selectedPermissions"
                                                       wire:click="togglePermissionSelection({{ $permission->id }})"
                                                       class="ml-6 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">

                                                {{-- 權限資訊 --}}
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center space-x-2 mb-1">
                                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $permission->name }}
                                                        </h4>
                                                        
                                                        {{-- 權限狀態指示器 --}}
                                                        @if($permission->is_system_permission)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ __('permissions.system_permission') }}">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                                </svg>
                                                            </span>
                                                        @endif

                                                        @if($permission->dependencies_count > 0)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200" title="{{ __('permissions.has_dependencies') }}">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                                        {{ $this->getLocalizedDisplayName($permission) }}
                                                    </p>

                                                    @if($permission->description)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                            {{ $this->getLocalizedDescription($permission) }}
                                                        </p>
                                                    @endif

                                                    {{-- 權限詳細資訊 --}}
                                                    <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                                        <div class="flex items-center space-x-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                            </svg>
                                                            <span>{{ $permission->roles_count }}</span>
                                                        </div>
                                                        
                                                        @if($permission->dependencies_count > 0)
                                                            <div class="flex items-center space-x-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                                </svg>
                                                                <span>{{ $permission->dependencies_count }}</span>
                                                            </div>
                                                        @endif

                                                        <div class="flex items-center space-x-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <span>{{ $permission->created_at->format('m/d') }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- 狀態和操作 --}}
                                                <div class="flex items-center space-x-2">
                                                    {{-- 使用狀態 --}}
                                                    @php $usageBadge = $this->getUsageBadge($permission) @endphp
                                                    <span class="{{ $usageBadge['class'] }}">
                                                        {{ $usageBadge['text'] }}
                                                    </span>

                                                    {{-- 操作按鈕（懸停時顯示） --}}
                                                    <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                        @if($this->hasPermission('edit'))
                                                            <button wire:click="editPermission({{ $permission->id }})" 
                                                                    class="p-1 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200"
                                                                    title="{{ __('permissions.edit') }}">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                </svg>
                                                            </button>
                                                        @endif

                                                        @if($this->hasPermission('delete') && $permission->can_be_deleted)
                                                            <button wire:click="deletePermission({{ $permission->id }})" 
                                                                    class="p-1 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                                                    title="{{ __('permissions.delete') }}">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                        @endif

                                                        <button class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                                                                title="{{ __('permissions.view_details') }}">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="text-center py-12">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                {{ __('permissions.no_permissions_found') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                {{ __('permissions.no_permissions_description') }}
            </p>
            @if($this->hasPermission('create'))
                <button wire:click="createPermission" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ __('permissions.create_first_permission') }}
                </button>
            @endif
        </div>
    @endforelse
</div>

{{-- 樹狀檢視控制工具列 --}}
@if($groupedPermissions->isNotEmpty())
    <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-900 dark:to-blue-900/20 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button wire:click="expandedGroups = {{ json_encode($groupedPermissions->keys()->toArray()) }}" 
                        class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 bg-white dark:bg-gray-800 rounded-md border border-blue-200 dark:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    {{ __('permissions.expand_all') }}
                </button>
                <button wire:click="expandedGroups = []" 
                        class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 bg-white dark:bg-gray-800 rounded-md border border-blue-200 dark:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    {{ __('permissions.collapse_all') }}
                </button>
            </div>
            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                    <span>{{ __('permissions.modules') }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-purple-400 rounded-full"></div>
                    <span>{{ __('permissions.types') }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                    <span>{{ __('permissions.permissions') }}</span>
                </div>
                <span class="text-gray-400">|</span>
                <span>{{ $groupedPermissions->sum(function($permissions) { return $permissions->count(); }) }} {{ __('permissions.total') }}</span>
            </div>
        </div>
    </div>
@endif