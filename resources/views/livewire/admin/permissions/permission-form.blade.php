<div>
    <!-- 權限表單模態框 -->
    <div x-data="{ show: @entangle('showForm') }" 
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

            <!-- 模態框內容 -->
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                
                <!-- 表單標題 -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $mode === 'create' ? __('permissions.form.create_title') : __('permissions.form.edit_title') }}
                    </h3>
                    <button type="button" 
                            wire:click="cancel"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- 表單內容 -->
                <form wire:submit.prevent="save" class="space-y-6">
                    
                    <!-- 權限名稱 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('permissions.form.name_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name"
                               wire:model.defer="name"
                               wire:key="permission-name-input"
                               @if($isSystemPermission && $mode === 'edit') readonly @endif
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @if($isSystemPermission && $mode === 'edit') bg-gray-100 dark:bg-gray-600 @endif"
                               placeholder="{{ __('permissions.form.name_placeholder') }}">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if($isSystemPermission && $mode === 'edit')
                            <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                                {{ __('permissions.form.system_permission_name_readonly') }}
                            </p>
                        @endif
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('permissions.form.name_help') }}
                        </p>
                    </div>

                    <!-- 顯示名稱 -->
                    <div>
                        <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('permissions.form.display_name_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="display_name"
                               wire:model.defer="display_name"
                               wire:key="permission-display-name-input"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                               placeholder="{{ __('permissions.form.display_name_placeholder') }}">
                        @error('display_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 描述 -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('permissions.form.description_label') }}
                        </label>
                        <textarea id="description"
                                  wire:model.defer="description"
                                  wire:key="permission-description-textarea"
                                  rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                  placeholder="{{ __('permissions.form.description_placeholder') }}"></textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 模組和類型 -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- 模組 -->
                        <div>
                            <label for="module" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('permissions.form.module_label') }} <span class="text-red-500">*</span>
                            </label>
                            <select id="module"
                                    wire:model.defer="module"
                                    wire:key="permission-module-select"
                                    @if($isSystemPermission && $mode === 'edit') disabled @endif
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @if($isSystemPermission && $mode === 'edit') bg-gray-100 dark:bg-gray-600 @endif">
                                <option value="">{{ __('permissions.form.module_placeholder') }}</option>
                                @foreach($availableModules as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('module')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @if($isSystemPermission && $mode === 'edit')
                                <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                                    {{ __('permissions.form.system_permission_module_readonly') }}
                                </p>
                            @endif
                        </div>

                        <!-- 類型 -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('permissions.form.type_label') }} <span class="text-red-500">*</span>
                            </label>
                            <select id="type"
                                    wire:model.defer="type"
                                    wire:key="permission-type-select"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="">{{ __('permissions.form.type_placeholder') }}</option>
                                @foreach($availableTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- 依賴權限 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            {{ __('permissions.form.dependencies_label') }}
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            {{ __('permissions.form.dependencies_help') }}
                        </p>
                        
                        <!-- 已選擇的依賴權限 -->
                        @if(!empty($dependencies))
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('permissions.dependencies.depends_on') }}：</h4>
                                <div class="space-y-2">
                                    @foreach($dependencies as $dependencyId)
                                        @php $depInfo = $this->getDependencyInfo($dependencyId); @endphp
                                        @if($depInfo)
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                                <div class="flex-1">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $depInfo['display_name'] }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $depInfo['name'] }} ({{ $depInfo['module'] }}.{{ $depInfo['type'] }})
                                                    </div>
                                                </div>
                                                <button type="button"
                                                        wire:click="removeDependency({{ $dependencyId }})"
                                                        class="ml-3 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- 可選擇的權限列表 -->
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-48 overflow-y-auto">
                            @if($availablePermissions->count() > 0)
                                @foreach($availablePermissions->groupBy('module') as $module => $permissions)
                                    <div class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                        <div class="bg-gray-50 dark:bg-gray-700 px-3 py-2">
                                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ $availableModules[$module] ?? $module }}
                                            </h5>
                                        </div>
                                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($permissions as $perm)
                                                <div class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <label class="flex items-center cursor-pointer">
                                                        <input type="checkbox"
                                                               value="{{ $perm->id }}"
                                                               wire:model.defer="dependencies"
                                                               wire:key="permission-dependency-{{ $perm->id }}"
                                                               @if(!$this->canSelectAsDependency($perm->id)) disabled @endif
                                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                                        <div class="ml-3 flex-1">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                                {{ $perm->display_name }}
                                                            </div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $perm->name }}
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('permissions.dependencies.no_dependencies') }}
                                </div>
                            @endif
                        </div>
                        
                        @error('dependencies')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 表單按鈕 -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button"
                                wire:click="cancel"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            {{ __('permissions.form.cancel') }}
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="save">{{ __('permissions.form.save') }}</span>
                            <span wire:loading wire:target="save" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('permissions.form.saving') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        // 監聽權限表單重置事件
        Livewire.on('permission-form-reset', () => {
            console.log('🔄 收到 permission-form-reset 事件，手動更新前端...');
            
            setTimeout(() => {
                // 清除權限表單欄位
                const permissionForm = document.querySelector('form[wire\\:submit\\.prevent="save"]');
                if (permissionForm) {
                    const inputs = permissionForm.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        if (input.type === 'checkbox') {
                            input.checked = false;
                        } else if (input.type === 'radio') {
                            input.checked = false;
                        } else {
                            input.value = '';
                        }
                        // 觸發 blur 事件確保 Livewire 同步
                        input.dispatchEvent(new Event('blur', { bubbles: true }));
                    });
                }
            }, 100);
        });
    });
</script>