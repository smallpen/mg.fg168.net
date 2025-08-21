{{-- 批量權限設定模態 --}}
<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                {{-- 模態內容 --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    {{-- 標題列 --}}
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/20 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                        {{ __('admin.roles.bulk_permissions.title') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('admin.roles.bulk_permissions.description', ['count' => count($selectedRoleIds)]) }}
                                    </p>
                                </div>
                            </div>
                            <button 
                                wire:click="closeModal"
                                class="bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <span class="sr-only">{{ __('admin.common.close') }}</span>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    @if(!$showResults)
                        {{-- 設定表單 --}}
                        <div class="bg-white dark:bg-gray-800 px-4 pb-4 sm:px-6">
                            {{-- 選中的角色列表 --}}
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    {{ __('admin.roles.bulk_permissions.selected_roles') }}
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($selectedRoles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $role->is_system_role ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' }}">
                                            @if($role->is_system_role)
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            @endif
                                            {{ $role->display_name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- 操作類型選擇 --}}
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    {{ __('admin.roles.bulk_permissions.operation_type') }}
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    @foreach($this->operationTypes as $type => $label)
                                        <label class="relative flex cursor-pointer rounded-lg border {{ $operationType === $type ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700' }} p-4 focus:outline-none">
                                            <input 
                                                type="radio" 
                                                wire:model.live="operationType" 
                                                value="{{ $type }}"
                                                class="sr-only"
                                            />
                                            <div class="flex flex-1">
                                                <div class="flex flex-col">
                                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $label }}
                                                    </span>
                                                    <span class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                        {{ __('admin.roles.bulk_permissions.operation_descriptions.' . $type) }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($operationType === $type)
                                                <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- 模組篩選和統計 --}}
                            <div class="mb-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            {{ __('admin.roles.bulk_permissions.module_filter') }}
                                        </label>
                                        <select 
                                            wire:model.live="selectedModule"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="all">{{ __('admin.roles.bulk_permissions.all_modules') }}</option>
                                            @foreach($modules as $module => $permissions)
                                                <option value="{{ $module }}">
                                                    {{ __('admin.permissions.modules.' . $module) }} ({{ $permissions->count() }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="selectAllModulePermissions"
                                            class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            {{ __('admin.roles.bulk_permissions.select_all') }}
                                        </button>
                                        <button 
                                            wire:click="clearModulePermissions"
                                            class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                        >
                                            {{ __('admin.roles.bulk_permissions.clear_all') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- 選擇統計 --}}
                                @if(count($selectedPermissions) > 0)
                                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <div class="flex items-center text-sm text-blue-800 dark:text-blue-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ __('admin.roles.bulk_permissions.selected_count', [
                                                'selected' => $this->selectedPermissionsStats['total_selected'],
                                                'total' => $this->selectedPermissionsStats['total_permissions'],
                                                'percentage' => $this->selectedPermissionsStats['percentage']
                                            ]) }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- 權限列表 --}}
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    {{ __('admin.roles.bulk_permissions.permissions') }}
                                </h4>
                                
                                <div class="max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                                    @forelse($this->groupedPermissions as $module => $permissions)
                                        <div class="border-b border-gray-200 dark:border-gray-600 last:border-b-0">
                                            {{-- 模組標題 --}}
                                            <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3">
                                                <div class="flex items-center justify-between">
                                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ __('admin.permissions.modules.' . $module) }}
                                                    </h5>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $permissions->count() }} {{ __('admin.roles.bulk_permissions.permissions_count') }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            {{-- 權限列表 --}}
                                            <div class="p-4">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    @foreach($permissions as $permission)
                                                        <label class="flex items-center cursor-pointer">
                                                            <input 
                                                                type="checkbox" 
                                                                wire:click="togglePermission({{ $permission->id }})"
                                                                {{ $this->isPermissionSelected($permission->id) ? 'checked' : '' }}
                                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                            />
                                                            <div class="ml-3">
                                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {{ $permission->localized_display_name }}
                                                                </span>
                                                                @if($permission->localized_description)
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                        {{ $permission->localized_description }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-8 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                                {{ __('admin.roles.bulk_permissions.no_permissions') }}
                                            </h3>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            {{-- 錯誤訊息 --}}
                            @if($errors->any())
                                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-red-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        <div>
                                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                                {{ __('admin.roles.bulk_permissions.errors.title') }}
                                            </h3>
                                            <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                                <ul class="list-disc list-inside space-y-1">
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

                        {{-- 操作按鈕 --}}
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                wire:click="executeBulkPermissionOperation"
                                :disabled="!$wire.selectedPermissions.length"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:bg-gray-400 disabled:cursor-not-allowed"
                            >
                                {{ __('admin.roles.bulk_permissions.execute') }}
                            </button>
                            <button 
                                wire:click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ __('admin.common.cancel') }}
                            </button>
                        </div>
                    @else
                        {{-- 操作結果 --}}
                        <div class="bg-white dark:bg-gray-800 px-4 pb-4 sm:px-6">
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                                    {{ __('admin.roles.bulk_permissions.results.title') }}
                                </h4>
                                
                                <div class="space-y-3">
                                    @foreach($operationResults as $result)
                                        <div class="flex items-start p-3 rounded-lg {{ $result['success'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                                            <div class="flex-shrink-0">
                                                @if($result['success'])
                                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium {{ $result['success'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                                    {{ $result['role']['display_name'] }}
                                                </p>
                                                <p class="text-sm {{ $result['success'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                                    {{ $result['message'] }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- 結果操作按鈕 --}}
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                wire:click="closeModal"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ __('admin.common.close') }}
                            </button>
                            <button 
                                wire:click="retryOperation"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ __('admin.roles.bulk_permissions.retry') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>