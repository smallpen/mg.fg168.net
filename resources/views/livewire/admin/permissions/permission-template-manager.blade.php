<div class="space-y-6">
    <!-- 頁面標題和操作按鈕 -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">權限模板管理</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理權限模板，快速建立標準權限集合
            </p>
        </div>
        <div class="flex space-x-3">
            <button wire:click="showCreateFromPermissions" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                從權限建立
            </button>
            <button wire:click="createTemplate" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                建立模板
            </button>
        </div>
    </div>

    <!-- 搜尋和篩選 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">搜尋</label>
                <input wire:model.live="search" 
                       type="text" 
                       placeholder="搜尋模板名稱、顯示名稱或描述..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">模組</label>
                <select wire:model.live="moduleFilter" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">所有模組</option>
                    @foreach($availableModules as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">類型</label>
                <select wire:model.live="typeFilter" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">所有類型</option>
                    <option value="system">系統模板</option>
                    <option value="custom">自定義模板</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 模板列表 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">模板列表</h3>
        </div>
        
        @if($templates->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($templates as $template)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ $template->display_name }}
                                    </h4>
                                    @if($template->is_system_template)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            系統模板
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            自定義模板
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $template->name }} • {{ $template->module }} • {{ $template->permission_count }} 個權限
                                </p>
                                @if($template->description)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                        {{ $template->description }}
                                    </p>
                                @endif
                                <div class="flex items-center space-x-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span>建立者：{{ $template->creator?->name ?? '系統' }}</span>
                                    <span>建立時間：{{ $template->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button wire:click="showApplyTemplate({{ $template->id }})" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    應用
                                </button>
                                @if(!$template->is_system_template)
                                    <button wire:click="editTemplate({{ $template->id }})" 
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        編輯
                                    </button>
                                    <button wire:click="deleteTemplate({{ $template->id }})" 
                                            wire:confirm="確定要刪除此模板嗎？"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        刪除
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- 分頁 -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $templates->links() }}
            </div>
        @else
            <div class="p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有找到模板</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">開始建立您的第一個權限模板</p>
            </div>
        @endif
    </div>

    <!-- 模板表單模態框 -->
    @if($showTemplateForm)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="resetTemplateForm"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form wire:submit="saveTemplate">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                        {{ $editingTemplate ? '編輯模板' : '建立模板' }}
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">模板名稱</label>
                                            <input wire:model="templateName" 
                                                   type="text" 
                                                   placeholder="例如：crud_basic"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                            @error('templateName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">顯示名稱</label>
                                            <input wire:model="templateDisplayName" 
                                                   type="text" 
                                                   placeholder="例如：基本 CRUD 權限"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                            @error('templateDisplayName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">適用模組</label>
                                            <select wire:model="templateModule" 
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                <option value="">請選擇模組</option>
                                                @foreach($availableModules as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('templateModule') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">描述</label>
                                            <input wire:model="templateDescription" 
                                                   type="text" 
                                                   placeholder="模板描述（選填）"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                            @error('templateDescription') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <!-- 權限配置 -->
                                    <div class="mb-6">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="text-md font-medium text-gray-900 dark:text-white">權限配置</h4>
                                            <button type="button" 
                                                    wire:click="addPermissionConfig"
                                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                新增權限
                                            </button>
                                        </div>
                                        
                                        @foreach($templatePermissions as $index => $permission)
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">動作</label>
                                                    <input wire:model="templatePermissions.{{ $index }}.action" 
                                                           type="text" 
                                                           placeholder="例如：view"
                                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                    @error("templatePermissions.{$index}.action") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">顯示名稱</label>
                                                    <input wire:model="templatePermissions.{{ $index }}.display_name" 
                                                           type="text" 
                                                           placeholder="例如：檢視"
                                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                    @error("templatePermissions.{$index}.display_name") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">類型</label>
                                                    <select wire:model="templatePermissions.{{ $index }}.type" 
                                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                        @foreach($availableTypes as $key => $label)
                                                            <option value="{{ $key }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("templatePermissions.{$index}.type") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>
                                                <div class="flex items-end">
                                                    @if(count($templatePermissions) > 1)
                                                        <button type="button" 
                                                                wire:click="removePermissionConfig({{ $index }})"
                                                                class="w-full px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                                            移除
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @error('templatePermissions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                {{ $editingTemplate ? '更新模板' : '建立模板' }}
                            </button>
                            <button type="button" 
                                    wire:click="resetTemplateForm"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                取消
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- 應用模板模態框 -->
    @if($showApplyModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="resetApplyForm"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form wire:submit="applyTemplate">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                        應用模板：{{ $applyingTemplate?->display_name }}
                                    </h3>
                                    
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">模組前綴</label>
                                        <input wire:model.live="applyModulePrefix" 
                                               type="text" 
                                               placeholder="例如：users"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                        @error('applyModulePrefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            權限名稱將以此前綴開頭，例如：users.view
                                        </p>
                                    </div>

                                    <!-- 預覽 -->
                                    @if(!empty($previewPermissions))
                                        <div class="mb-6">
                                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">預覽將建立的權限</h4>
                                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto">
                                                @foreach($previewPermissions as $preview)
                                                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600 last:border-b-0">
                                                        <div>
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ $preview['name'] }}</span>
                                                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ $preview['display_name'] }}</span>
                                                        </div>
                                                        @if($preview['exists'])
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                                已存在
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                                將建立
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                應用模板
                            </button>
                            <button type="button" 
                                    wire:click="resetApplyForm"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                取消
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- 從權限建立模板模態框 -->
    @if($showCreateFromPermissionsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="resetCreateFromPermissionsForm"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form wire:submit="createFromSelectedPermissions">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                        從現有權限建立模板
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">選擇模組</label>
                                            <select wire:model.live="createFromModule" 
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                <option value="">請選擇模組</option>
                                                @foreach($availableModules as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('createFromModule') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">模板名稱</label>
                                            <input wire:model="templateName" 
                                                   type="text" 
                                                   placeholder="例如：my_template"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                            @error('templateName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">顯示名稱</label>
                                            <input wire:model="templateDisplayName" 
                                                   type="text" 
                                                   placeholder="例如：我的模板"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                            @error('templateDisplayName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">描述</label>
                                        <input wire:model="templateDescription" 
                                               type="text" 
                                               placeholder="模板描述（選填）"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>

                                    <!-- 權限選擇 -->
                                    @if($modulePermissions->count() > 0)
                                        <div class="mb-6">
                                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">選擇權限</h4>
                                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto">
                                                @foreach($modulePermissions as $permission)
                                                    <label class="flex items-center py-2">
                                                        <input type="checkbox" 
                                                               wire:model="selectedPermissions" 
                                                               value="{{ $permission->id }}"
                                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                        <span class="ml-3 text-sm text-gray-900 dark:text-white">
                                                            {{ $permission->name }} - {{ $permission->display_name }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('selectedPermissions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    @elseif($createFromModule)
                                        <div class="mb-6 text-center py-8">
                                            <p class="text-gray-500 dark:text-gray-400">此模組沒有可用的權限</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                建立模板
                            </button>
                            <button type="button" 
                                    wire:click="resetCreateFromPermissionsForm"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                取消
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>