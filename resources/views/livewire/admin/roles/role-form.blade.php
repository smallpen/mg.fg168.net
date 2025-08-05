<div class="space-y-6">
    {{-- 頁面標題 --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEditMode ? __('admin.roles.edit') : __('admin.roles.create') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $isEditMode ? '編輯角色資訊和權限設定' : '建立新的系統角色並設定權限' }}
            </p>
        </div>
        
        <div class="flex space-x-3">
            <a href="{{ route('admin.roles.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('admin.actions.back') }}
            </a>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- 基本資訊 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                {{ __('admin.roles.basic_info') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 角色名稱 --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin.roles.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name"
                           wire:model.debounce.300ms="name" 
                           placeholder="例如：admin, editor, viewer"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    
                    {{-- 角色名稱建議 --}}
                    @if(!$isEditMode && empty($name))
                    <div class="mt-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">常用角色名稱：</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($roleNameSuggestions as $suggestedName => $suggestedDisplayName)
                                <button type="button" 
                                        wire:click="useSuggestion('{{ $suggestedName }}', '{{ $suggestedDisplayName }}')"
                                        class="inline-flex items-center px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                                    {{ $suggestedName }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- 顯示名稱 --}}
                <div>
                    <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin.roles.display_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="display_name"
                           wire:model.debounce.300ms="display_name" 
                           placeholder="例如：系統管理員、內容編輯者"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('display_name') border-red-500 @enderror">
                    @error('display_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 描述 --}}
            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin.roles.description') }}
                </label>
                <textarea id="description"
                          wire:model.debounce.500ms="description" 
                          rows="3"
                          placeholder="描述此角色的職責和權限範圍..."
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('description') border-red-500 @enderror"></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ strlen($description) }}/500 字元
                </p>
            </div>

            {{-- 狀態設定（如果支援） --}}
            @if($supportsStatus)
            <div class="mt-6">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="is_active"
                           wire:model="is_active"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        {{ __('admin.roles.active') }}
                    </label>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    停用的角色將無法指派給使用者
                </p>
            </div>
            @endif
        </div>

        {{-- 權限設定 --}}
        @if($this->hasPermission('permissions.edit'))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('admin.roles.permissions') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            設定此角色可以存取的系統功能
                        </p>
                    </div>
                    <button type="button" 
                            wire:click="togglePermissions"
                            class="inline-flex items-center px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-1 transform transition-transform duration-200 {{ $showPermissions ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        {{ $showPermissions ? '隱藏權限設定' : '顯示權限設定' }}
                    </button>
                </div>
            </div>

            @if($showPermissions)
            <div class="p-6">
                {{-- 權限統計 --}}
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                已選擇 {{ count($selectedPermissions) }} 個權限
                            </span>
                        </div>
                        @if(count($selectedPermissions) > 0)
                        <button type="button" 
                                wire:click="$set('selectedPermissions', [])"
                                class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                            清除所有選擇
                        </button>
                        @endif
                    </div>
                </div>

                {{-- 權限列表（按模組分組） --}}
                <div class="space-y-6">
                    @foreach($groupedPermissions as $module => $permissions)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ ucfirst($module) }} 模組
                                        </h3>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                            {{ $permissions->count() }} 個權限
                                        </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        @if(!$this->isModuleFullySelected($module))
                                        <button type="button" 
                                                wire:click="selectModulePermissions('{{ $module }}')"
                                                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                            全選
                                        </button>
                                        @endif
                                        @if($this->isModuleFullySelected($module) || $this->isModulePartiallySelected($module))
                                        <button type="button" 
                                                wire:click="deselectModulePermissions('{{ $module }}')"
                                                class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                            取消全選
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($permissions as $permission)
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="permission_{{ $permission->id }}"
                                                   wire:click="togglePermission({{ $permission->id }})"
                                                   {{ $this->isPermissionSelected($permission->id) ? 'checked' : '' }}
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                                            <label for="permission_{{ $permission->id }}" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                                <div class="font-medium">{{ $permission->display_name }}</div>
                                                @if($permission->description)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $permission->description }}</div>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($groupedPermissions->isEmpty())
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        {{ __('admin.permissions.no_permissions') }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        系統中尚未建立任何權限
                    </p>
                </div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- 表單操作按鈕 --}}
        <div class="flex justify-end space-x-3">
            <button type="button" 
                    wire:click="resetForm"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                {{ __('admin.actions.reset') }}
            </button>
            
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $isEditMode ? __('admin.actions.update') : __('admin.actions.create') }}
            </button>
        </div>
    </form>

    {{-- 表單驗證提示 --}}
    @if($errors->any())
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                    表單驗證錯誤
                </h3>
                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>