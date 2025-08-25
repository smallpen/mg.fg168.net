{{-- 分組檢視 --}}
<div class="space-y-4 p-4">
    @forelse($groupedPermissions as $module => $permissions)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            {{-- 分組標題 --}}
            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <button wire:click="toggleGroup('{{ $module }}')" 
                        class="flex items-center justify-between w-full text-left">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            @if(in_array($module, $expandedGroups))
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transform -rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ ucfirst($module) }} {{ __('permissions.module') }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $permissions->count() }} {{ __('permissions.permissions') }}
                                • {{ $permissions->where('roles_count', '>', 0)->count() }} {{ __('permissions.used') }}
                                • {{ $permissions->where('roles_count', 0)->count() }} {{ __('permissions.unused') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        {{-- 模組統計圖示 --}}
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-green-400 rounded-full" title="{{ __('permissions.used') }}"></div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $permissions->where('roles_count', '>', 0)->count() }}</span>
                            <div class="w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-full ml-2" title="{{ __('permissions.unused') }}"></div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $permissions->where('roles_count', 0)->count() }}</span>
                        </div>
                    </div>
                </button>
            </div>

            {{-- 分組內容 --}}
            @if(in_array($module, $expandedGroups))
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($permissions as $permission)
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3 flex-1">
                                    {{-- 選擇框 --}}
                                    <input type="checkbox" 
                                           value="{{ $permission->id }}"
                                           wire:model.live="selectedPermissions"
                                           wire:click="togglePermissionSelection({{ $permission->id }})"
                                           class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">

                                    {{-- 權限資訊 --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $permission->name }}
                                            </h4>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                {{ $this->getLocalizedType($permission->type) }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            {{ $this->getLocalizedDisplayName($permission) }}
                                        </p>

                                        @if($permission->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                {{ $this->getLocalizedDescription($permission) }}
                                            </p>
                                        @endif

                                        {{-- 權限統計 --}}
                                        <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center space-x-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                                <span>{{ $permission->roles_count }} {{ __('permissions.roles') }}</span>
                                            </div>
                                            
                                            @if($permission->dependencies_count > 0)
                                                <div class="flex items-center space-x-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                    </svg>
                                                    <span>{{ $permission->dependencies_count }} {{ __('permissions.dependencies') }}</span>
                                                </div>
                                            @endif

                                            <div class="flex items-center space-x-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>{{ $permission->created_at->format('Y-m-d') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 狀態和操作 --}}
                                <div class="flex items-center space-x-3 ml-4">
                                    {{-- 使用狀態 --}}
                                    @php $usageBadge = $this->getUsageBadge($permission) @endphp
                                    <span class="{{ $usageBadge['class'] }}">
                                        {{ $usageBadge['text'] }}
                                    </span>

                                    {{-- 操作按鈕 --}}
                                    <div class="flex items-center space-x-1">
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

                                        {{-- 更多操作 --}}
                                        <div class="relative inline-block text-left" x-data="{ open: false }">
                                            <button @click="open = !open" 
                                                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                                                    title="{{ __('permissions.more_actions') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                </svg>
                                            </button>

                                            <div x-show="open" 
                                                 @click.away="open = false"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="transform opacity-0 scale-95"
                                                 x-transition:enter-end="transform opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="transform opacity-100 scale-100"
                                                 x-transition:leave-end="transform opacity-0 scale-95"
                                                 class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-10">
                                                <div class="py-1">
                                                    <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        {{ __('permissions.view_dependencies') }}
                                                    </button>
                                                    <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        {{ __('permissions.view_usage') }}
                                                    </button>
                                                    <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        {{ __('permissions.duplicate') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
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

{{-- 快速操作工具列 --}}
@if($groupedPermissions->isNotEmpty())
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button wire:click="expandedGroups = {{ json_encode($groupedPermissions->keys()->toArray()) }}" 
                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ __('permissions.expand_all') }}
                </button>
                <button wire:click="expandedGroups = []" 
                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ __('permissions.collapse_all') }}
                </button>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $groupedPermissions->sum(function($permissions) { return $permissions->count(); }) }} {{ __('permissions.total_permissions') }}
                {{ __('permissions.in') }} {{ $groupedPermissions->count() }} {{ __('permissions.modules') }}
            </div>
        </div>
    </div>
@endif