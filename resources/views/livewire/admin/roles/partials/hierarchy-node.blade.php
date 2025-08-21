@php
    $isExpanded = $this->isNodeExpanded($role->id);
    $hasChildren = $this->hasChildren($role);
    $childrenCount = $this->getChildrenCount($role);
    $permissionInfo = $this->getPermissionInheritanceInfo($role);
    $isSelected = $selectedRoleId === $role->id;
@endphp

<div class="role-node" data-role-id="{{ $role->id }}">
    <!-- 角色節點 -->
    <div 
        class="flex items-center p-3 rounded-lg border transition-all duration-200 hover:shadow-md {{ $isSelected ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
        style="margin-left: {{ $depth * 24 }}px"
    >
        <!-- 展開/收合按鈕 -->
        <div class="flex-shrink-0 w-6 h-6 mr-2">
            @if($hasChildren)
                <button 
                    wire:click="toggleNode({{ $role->id }})"
                    class="w-6 h-6 flex items-center justify-center rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    @if($isExpanded)
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    @else
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    @endif
                </button>
            @else
                <div class="w-6 h-6 flex items-center justify-center">
                    <div class="w-2 h-2 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                </div>
            @endif
        </div>

        <!-- 角色圖示 -->
        <div class="flex-shrink-0 mr-3">
            @if($role->is_system_role)
                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            @else
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            @endif
        </div>

        <!-- 角色資訊 -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
                <!-- 角色名稱 -->
                <button 
                    wire:click="selectRole({{ $role->id }})"
                    class="text-left hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                    <div class="font-medium text-gray-900 dark:text-white truncate">
                        {{ $role->display_name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {{ $role->name }}
                    </div>
                </button>

                <!-- 狀態標籤 -->
                <div class="flex items-center space-x-1">
                    @if(!$role->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            停用
                        </span>
                    @endif
                    
                    @if($role->is_system_role)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                            系統
                        </span>
                    @endif
                    
                    @if($hasChildren)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                            {{ $childrenCount }} 子角色
                        </span>
                    @endif
                </div>
            </div>

            <!-- 權限資訊 -->
            <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                <span>
                    <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    {{ $role->users_count }} 使用者
                </span>
                
                <span>
                    <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    {{ $permissionInfo['direct_count'] }} 直接權限
                </span>
                
                @if($permissionInfo['inherited_count'] > 0)
                    <span class="text-green-600 dark:text-green-400">
                        <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        {{ $permissionInfo['inherited_count'] }} 繼承權限
                    </span>
                @endif
            </div>
        </div>

        <!-- 操作按鈕 -->
        <div class="flex-shrink-0 flex items-center space-x-1">
            <!-- 建立子角色 -->
            @if($this->can('roles.create'))
                <button 
                    wire:click="createChildRole({{ $role->id }})"
                    class="p-1 text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition-colors"
                    title="建立子角色"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </button>
            @endif

            <!-- 編輯角色 -->
            @if($this->can('roles.edit'))
                <button 
                    wire:click="editRole({{ $role->id }})"
                    class="p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                    title="編輯角色"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
            @endif

            <!-- 複製角色 -->
            @if($this->can('roles.create'))
                <button 
                    wire:click="duplicateRole({{ $role->id }})"
                    class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    title="複製角色"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </button>
            @endif

            <!-- 刪除角色 -->
            @if($this->can('roles.delete') && !$role->is_system_role)
                <button 
                    wire:click="deleteRole({{ $role->id }})"
                    class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                    title="刪除角色"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            @endif

            <!-- 更多操作 -->
            <div class="relative" x-data="{ open: false }">
                <button 
                    @click="open = !open"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    title="更多操作"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>

                <!-- 下拉選單 -->
                <div 
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
                >
                    <div class="py-1">
                        <button 
                            wire:click="selectRole({{ $role->id }})"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            檢視詳情
                        </button>
                        
                        <div class="border-t border-gray-100 dark:border-gray-700"></div>
                        
                        <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                            路徑：{{ $this->getRolePath($role) }}
                        </div>
                        <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                            深度：第 {{ $this->getRoleDepth($role) + 1 }} 層
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 子角色 -->
    @if($hasChildren && $isExpanded)
        <div class="mt-2 space-y-2">
            @foreach($role->children as $childRole)
                @include('livewire.admin.roles.partials.hierarchy-node', ['role' => $childRole, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>