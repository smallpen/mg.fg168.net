<div class="space-y-6">
    <!-- 匯出區塊 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                <i class="fas fa-download mr-2 text-blue-500"></i>
                匯出權限
            </h3>
            
            <!-- 匯出篩選條件 -->
            <div class="space-y-4 mb-6">
                <!-- 模組篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        選擇模組
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableModules as $module)
                            <label class="inline-flex items-center">
                                <input type="checkbox" 
                                       wire:click="updateExportFilters('modules', '{{ $module }}')"
                                       @if(in_array($module, $exportFilters['modules'])) checked @endif
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $module }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- 權限類型篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        選擇權限類型
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableTypes as $type)
                            <label class="inline-flex items-center">
                                <input type="checkbox" 
                                       wire:click="updateExportFilters('types', '{{ $type }}')"
                                       @if(in_array($type, $exportFilters['types'])) checked @endif
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $type }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- 使用狀態篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        使用狀態
                    </label>
                    <select wire:model="exportFilters.usage_status" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach($usageStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 匯出按鈕 -->
            <div class="flex justify-between items-center">
                <button type="button" 
                        wire:click="resetExportFilters"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700">
                    <i class="fas fa-undo mr-2"></i>
                    重置篩選
                </button>
                
                <button type="button" 
                        wire:click="exportPermissions"
                        :disabled="$exportInProgress"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    @if($exportInProgress)
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        匯出中...
                    @else
                        <i class="fas fa-download mr-2"></i>
                        匯出權限
                    @endif
                </button>
            </div>
        </div>
    </div>

    <!-- 匯入區塊 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                <i class="fas fa-upload mr-2 text-green-500"></i>
                匯入權限
            </h3>

            <!-- 檔案上傳 -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    選擇匯入檔案 (JSON 格式)
                </label>
                <input type="file" 
                       wire:model="importFile"
                       accept=".json"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                @error('importFile')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 匯入選項 -->
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        衝突處理策略
                    </label>
                    <select wire:model="importOptions.conflict_resolution" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach($conflictResolutionOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center space-x-6">
                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               wire:model="importOptions.validate_dependencies"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">驗證依賴關係</span>
                    </label>

                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               wire:model="importOptions.create_missing_dependencies"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">建立缺失的依賴</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- 匯入預覽 -->
    @if($showImportPreview)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-eye mr-2 text-yellow-500"></i>
                    匯入預覽
                </h3>

                <!-- 匯入摘要 -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $importPreview['summary']['will_create'] }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">將建立</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $importPreview['summary']['will_update'] }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">將更新</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-600">{{ $importPreview['summary']['will_skip'] }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">將跳過</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ $importPreview['summary']['total_permissions'] }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">總計</div>
                        </div>
                    </div>
                </div>

                <!-- 衝突列表 -->
                @if(!empty($importPreview['conflicts']))
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i>
                            發現 {{ count($importPreview['conflicts']) }} 個衝突
                        </h4>
                        
                        <button type="button" 
                                wire:click="toggleConflictResolution"
                                class="mb-3 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700">
                            @if($showConflictResolution)
                                <i class="fas fa-chevron-up mr-2"></i>
                                隱藏衝突詳情
                            @else
                                <i class="fas fa-chevron-down mr-2"></i>
                                顯示衝突詳情
                            @endif
                        </button>

                        @if($showConflictResolution)
                            <div class="space-y-3">
                                <!-- 批量操作 -->
                                <div class="flex space-x-2 mb-4">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">批量設定：</span>
                                    @foreach($conflictResolutionOptions as $value => $label)
                                        <button type="button" 
                                                wire:click="setBulkConflictResolution('{{ $value }}')"
                                                class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded dark:bg-gray-600 dark:hover:bg-gray-700 dark:text-white">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                <!-- 衝突項目 -->
                                @foreach($importPreview['conflicts'] as $index => $conflict)
                                    <div class="border border-yellow-200 rounded-lg p-3 bg-yellow-50 dark:bg-yellow-900 dark:border-yellow-700">
                                        <div class="flex justify-between items-start mb-2">
                                            <h5 class="font-medium text-gray-900 dark:text-white">
                                                {{ $conflict['existing_permission']['name'] }}
                                            </h5>
                                            <select wire:model="conflictResolutions.{{ $index }}" 
                                                    class="text-sm rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                @foreach($conflictResolutionOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        @if(!empty($conflict['differences']))
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                <strong>差異：</strong>
                                                @foreach($conflict['differences'] as $field => $diff)
                                                    <div class="ml-2">
                                                        <span class="font-medium">{{ $field }}:</span>
                                                        <span class="text-red-600">{{ $diff['existing'] }}</span>
                                                        →
                                                        <span class="text-green-600">{{ $diff['import'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <!-- 錯誤和警告 -->
                @if(!empty($importPreview['errors']))
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-red-600 mb-2">
                            <i class="fas fa-times-circle mr-2"></i>
                            錯誤 ({{ count($importPreview['errors']) }})
                        </h4>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 dark:bg-red-900 dark:border-red-700">
                            @foreach($importPreview['errors'] as $error)
                                <div class="text-sm text-red-700 dark:text-red-300">
                                    <strong>{{ $error['permission'] ?? '一般錯誤' }}:</strong> {{ $error['error'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($importPreview['warnings']))
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-yellow-600 mb-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            警告 ({{ count($importPreview['warnings']) }})
                        </h4>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 dark:bg-yellow-900 dark:border-yellow-700">
                            @foreach($importPreview['warnings'] as $warning)
                                <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                    <strong>{{ $warning['permission'] ?? '一般警告' }}:</strong> {{ $warning['message'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- 操作按鈕 -->
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            wire:click="cancelImport"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700">
                        <i class="fas fa-times mr-2"></i>
                        取消
                    </button>
                    
                    @if(empty($importPreview['errors']))
                        <button type="button" 
                                wire:click="executeImport"
                                :disabled="$importInProgress"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            @if($importInProgress)
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                匯入中...
                            @else
                                <i class="fas fa-check mr-2"></i>
                                確認匯入
                            @endif
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- 匯入結果 -->
    @if($showImportResults)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        匯入結果
                    </h3>
                    <button type="button" 
                            wire:click="closeImportResults"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- 結果摘要 -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $importResults['summary']['total_processed'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">總處理</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $importResults['summary']['created'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">已建立</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $importResults['summary']['updated'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">已更新</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-600">{{ $importResults['summary']['skipped'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">已跳過</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">{{ $importResults['summary']['errors'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">錯誤</div>
                        </div>
                    </div>
                </div>

                <!-- 建議 -->
                @if(!empty($importResults['recommendations']))
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>
                            建議
                        </h4>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 dark:bg-blue-900 dark:border-blue-700">
                            @foreach($importResults['recommendations'] as $recommendation)
                                <div class="text-sm text-blue-700 dark:text-blue-300 mb-1">
                                    • {{ $recommendation['message'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- 詳細錯誤 -->
                @if(!empty($importResults['details']['errors']))
                    <div class="mb-4">
                        <h4 class="text-md font-medium text-red-600 mb-2">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            詳細錯誤
                        </h4>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 max-h-40 overflow-y-auto dark:bg-red-900 dark:border-red-700">
                            @foreach($importResults['details']['errors'] as $error)
                                <div class="text-sm text-red-700 dark:text-red-300 mb-1">
                                    <strong>{{ $error['permission'] ?? '一般錯誤' }}:</strong> {{ $error['error'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // 處理 JSON 檔案下載
    Livewire.on('download-json', (event) => {
        const data = event.data;
        const filename = event.filename;
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
</script>
@endpush