<div class="max-w-4xl mx-auto">
    <!-- 表單標題 -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $this->formTitle }}
                </h2>
                @if($isEditing && $role)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        建立時間：{{ $role->formatted_created_at }}
                        @if($role->updated_at != $role->created_at)
                            | 最後更新：{{ $role->formatted_updated_at }}
                        @endif
                    </p>
                @endif
            </div>
            
            <!-- 自動儲存狀態 -->
            @if($isEditing)
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2">
                        <div class="flex items-center">
                            @if($hasUnsavedChanges)
                                <div class="h-2 w-2 bg-yellow-400 rounded-full animate-pulse"></div>
                            @elseif($lastAutoSaveTime)
                                <div class="h-2 w-2 bg-green-400 rounded-full"></div>
                            @else
                                <div class="h-2 w-2 bg-gray-300 rounded-full"></div>
                            @endif
                            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->autoSaveStatus }}
                            </span>
                        </div>
                        
                        <button 
                            type="button"
                            wire:click="toggleAutoSave"
                            @class([
                                'text-xs px-2 py-1 rounded border',
                                'bg-green-50 border-green-200 text-green-700 hover:bg-green-100 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300' => $autoSaveEnabled,
                                'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-900/20 dark:border-gray-600 dark:text-gray-400' => !$autoSaveEnabled
                            ])
                            title="{{ $autoSaveEnabled ? '點擊停用自動儲存' : '點擊啟用自動儲存' }}"
                        >
                            {{ $autoSaveEnabled ? '自動儲存' : '手動儲存' }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- 系統角色警告 -->
    @if($isSystemRole)
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        系統角色
                    </h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        這是系統預設角色，某些屬性受到保護無法修改。
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- 表單 -->
    <form wire:submit="save" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">基本資訊</h3>
            </div>
            
            <div class="px-6 py-4 space-y-6">
                <!-- 角色名稱 -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        角色名稱 <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="name"
                                wire:model.live.debounce.500ms="name"
                                wire:blur="checkNameAvailability"
                                @class([
                                    'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm',
                                    'opacity-50 cursor-not-allowed' => $isSystemRole,
                                    'border-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('name'),
                                    'border-green-300 focus:border-green-500 focus:ring-green-500' => !$errors->has('name') && !empty($name) && !$isSystemRole,
                                    'pr-10' => !$isSystemRole
                                ])
                                placeholder="例如：editor, manager"
                                @if($isSystemRole) readonly @endif
                            >
                            
                            @if(!$isSystemRole && !empty($name))
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    @if($errors->has('name'))
                                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @if(!$isSystemRole)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            只能包含小寫英文字母和底線，用於系統內部識別
                        </p>
                    @endif
                </div>

                <!-- 顯示名稱 -->
                <div>
                    <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        顯示名稱 <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="display_name"
                                wire:model.live.debounce.300ms="display_name"
                                @class([
                                    'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm pr-10',
                                    'border-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('display_name'),
                                    'border-green-300 focus:border-green-500 focus:ring-green-500' => !$errors->has('display_name') && !empty($display_name)
                                ])
                                placeholder="例如：編輯者、管理員"
                                required
                            >
                            
                            @if(!empty($display_name))
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    @if($errors->has('display_name'))
                                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @error('display_name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        使用者友善的角色名稱，會顯示在介面上
                    </p>
                </div>

                <!-- 角色描述 -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        角色描述
                    </label>
                    <div class="mt-1">
                        <textarea 
                            id="description"
                            wire:model.live.debounce.500ms="description"
                            rows="3"
                            @class([
                                'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm',
                                'border-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('description')
                            ])
                            placeholder="描述這個角色的職責和用途..."
                        ></textarea>
                    </div>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 父角色設定 -->
                @if($this->canModifyParent)
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                父角色設定
                            </label>
                            <button 
                                type="button"
                                wire:click="toggleParentSelector"
                                class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                {{ $showParentSelector ? '取消設定' : '設定父角色' }}
                            </button>
                        </div>
                        
                        @if($showParentSelector)
                            <div class="mt-2">
                                <select 
                                    wire:model.live="parent_id"
                                    @class([
                                        'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm',
                                        'border-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('parent_id')
                                    ])
                                >
                                    <option value="">選擇父角色</option>
                                    @foreach($availableParents as $parent)
                                        <option value="{{ $parent['id'] }}">{{ $parent['display_name'] }}</option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    子角色會繼承父角色的所有權限
                                </p>
                            </div>
                        @endif

                        @if($this->parentRoleInfo)
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md dark:bg-blue-900/20 dark:border-blue-800">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    <span class="font-medium">父角色：</span>{{ $this->parentRoleInfo['display_name'] }}
                                </p>
                            </div>
                        @endif

                        <!-- 權限繼承預覽 -->
                        @if($this->permissionInheritancePreview)
                            <div class="mt-3 p-4 bg-green-50 border border-green-200 rounded-md dark:bg-green-900/20 dark:border-green-800">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-green-800 dark:text-green-200">
                                            權限繼承預覽
                                        </h4>
                                        <div class="mt-1 text-sm text-green-700 dark:text-green-300">
                                            <p>將從父角色「{{ $this->permissionInheritancePreview['parent_name'] }}」繼承 {{ $this->permissionInheritancePreview['inherited_count'] }} 個權限</p>
                                            @if($isEditing)
                                                <p>加上目前的 {{ $this->permissionInheritancePreview['current_count'] }} 個直接權限，總共 {{ $this->permissionInheritancePreview['total_count'] }} 個權限</p>
                                            @endif
                                        </div>
                                        
                                        <!-- 繼承權限詳情 -->
                                        @if(!empty($this->permissionInheritancePreview['inherited_permissions']))
                                            <div class="mt-2" x-data="{ showDetails: false }">
                                                <button 
                                                    @click="showDetails = !showDetails"
                                                    class="text-xs text-green-600 dark:text-green-400 hover:text-green-500 underline"
                                                >
                                                    <span x-text="showDetails ? '隱藏詳情' : '顯示詳情'"></span>
                                                </button>
                                                
                                                <div x-show="showDetails" x-transition class="mt-2 space-y-2">
                                                    @foreach($this->permissionInheritancePreview['inherited_permissions'] as $module => $permissions)
                                                        <div class="text-xs">
                                                            <div class="font-medium text-green-800 dark:text-green-200">{{ $module }}：</div>
                                                            <div class="ml-2 text-green-700 dark:text-green-300">
                                                                {{ implode('、', $permissions) }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- 啟用狀態 -->
                <div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="is_active"
                            wire:model="is_active"
                            @class([
                                'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700',
                                'opacity-50 cursor-not-allowed' => !$this->canDeactivate
                            ])
                            @if(!$this->canDeactivate) disabled @endif
                        >
                        <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            啟用此角色
                        </label>
                    </div>
                    @if(!$this->canDeactivate)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            核心系統角色無法停用
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 驗證錯誤摘要 -->
        @if($this->validationSummary['hasErrors'] ?? false)
            <div class="bg-red-50 border border-red-200 rounded-md p-4 dark:bg-red-900/20 dark:border-red-800">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                            表單驗證失敗 ({{ $this->validationSummary['errorCount'] }} 個錯誤)
                        </h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($this->validationSummary['errorFields'] as $field)
                                    <li>{{ $this->getFieldError($field) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 儲存錯誤訊息 -->
        @if($errors->has('save'))
            <div class="bg-red-50 border border-red-200 rounded-md p-4 dark:bg-red-900/20 dark:border-red-800">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800 dark:text-red-200">{{ $errors->first('save') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- 操作按鈕 -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex space-x-3">
                <!-- 取消按鈕 -->
                <button 
                    type="button"
                    wire:click="cancel"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    取消
                </button>

                <!-- 重置按鈕 -->
                @if(!$isEditing || $hasUnsavedChanges)
                    <button 
                        type="button"
                        wire:click="confirmResetForm"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                        title="重置表單到初始狀態"
                    >
                        重置
                    </button>
                @endif

                <!-- 複製按鈕（僅編輯模式） -->
                @if($isEditing && $this->can('roles.create'))
                    <button 
                        type="button"
                        wire:click="duplicateRole"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        複製角色
                    </button>
                @endif
            </div>

            <div class="flex items-center space-x-3">
                <!-- 快捷鍵提示 -->
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Ctrl+S 快速儲存
                </span>
                
                <!-- 儲存按鈕 -->
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    @class([
                        'px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed',
                        'bg-indigo-600 hover:bg-indigo-700' => !$hasUnsavedChanges,
                        'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500' => $hasUnsavedChanges
                    ])
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $hasUnsavedChanges ? '儲存變更' : $this->saveButtonText }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        儲存中...
                    </span>
                </button>
            </div>
        </div>
    </form>

    <!-- 自動儲存 JavaScript -->
    @if($isEditing)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let autoSaveTimeout;
                let isAutoSaving = false;
                
                // 監聽自動儲存排程事件
                Livewire.on('schedule-auto-save', () => {
                    if (isAutoSaving) return;
                    
                    // 清除之前的計時器
                    if (autoSaveTimeout) {
                        clearTimeout(autoSaveTimeout);
                    }
                    
                    // 設定 3 秒後自動儲存
                    autoSaveTimeout = setTimeout(() => {
                        if (!isAutoSaving) {
                            isAutoSaving = true;
                            @this.call('autoSave').then(() => {
                                isAutoSaving = false;
                            }).catch(() => {
                                isAutoSaving = false;
                            });
                        }
                    }, 3000);
                });
                
                // 監聽自動儲存完成事件
                Livewire.on('auto-save-completed', (event) => {
                    // 顯示自動儲存成功提示（可選）
                    if (window.showToast) {
                        window.showToast(event.message, 'success', 2000);
                    }
                });
                
                // 監聽自動儲存切換事件
                Livewire.on('auto-save-toggled', (event) => {
                    if (window.showToast) {
                        window.showToast(event.message, 'info', 3000);
                    }
                });
                
                // 頁面離開前檢查未儲存變更
                window.addEventListener('beforeunload', function(e) {
                    if (@this.hasUnsavedChanges) {
                        e.preventDefault();
                        e.returnValue = '您有未儲存的變更，確定要離開嗎？';
                        return '您有未儲存的變更，確定要離開嗎？';
                    }
                });
                
                // Ctrl+S 快速儲存
                document.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                        e.preventDefault();
                        @this.call('save');
                    }
                });
                
                // 監聽表單重置確認事件
                Livewire.on('confirm-form-reset', () => {
                    if (confirm('您有未儲存的變更，確定要重置表單嗎？')) {
                        @this.call('resetForm');
                    }
                });
                
                // 監聽表單重置完成事件
                Livewire.on('form-reset', () => {
                    if (window.showToast) {
                        window.showToast('表單已重置', 'info', 2000);
                    }
                });
            });
        </script>
    @endif
</div>