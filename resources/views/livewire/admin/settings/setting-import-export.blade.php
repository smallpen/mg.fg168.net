<div>
    {{-- 匯出對話框 --}}
    @if($showExportDialog)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     wire:click="closeExportDialog" 
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    {{-- 標題 --}}
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <x-heroicon-o-arrow-down-tray class="w-6 h-6 mr-2 text-blue-600" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">匯出設定</h3>
                        </div>
                        <button type="button" 
                                wire:click="closeExportDialog"
                                class="bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">關閉</span>
                            <x-heroicon-o-x-mark class="h-6 w-6" />
                        </button>
                    </div>

        <div class="space-y-6">
            {{-- 匯出選項 --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 分類選擇 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        匯出分類
                    </label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border rounded-lg p-3">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model.live="exportCategories" 
                                   value=""
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">全部分類</span>
                        </label>
                        @foreach($this->availableCategories as $key => $category)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.live="exportCategories" 
                                       value="{{ $key }}"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm">{{ $category['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- 匯出選項 --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            匯出選項
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.live="exportOnlyChanged"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm">僅匯出已變更的設定</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       wire:model.live="exportIncludeSystem"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm">包含系統設定</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            匯出格式
                        </label>
                        <select wire:model.live="exportFormat" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="json">JSON</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 匯出統計 --}}
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">匯出統計</h4>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">設定數量：</span>
                        <span class="font-medium">{{ $this->exportStats['total'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">分類數量：</span>
                        <span class="font-medium">{{ $this->exportStats['categories'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">預估大小：</span>
                        <span class="font-medium">{{ $this->exportStats['size_estimate'] }}</span>
                    </div>
                </div>
            </div>
        </div>

                    {{-- 按鈕區域 --}}
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" 
                                wire:click="closeExportDialog"
                                class="inline-flex justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            取消
                        </button>
                        <button type="button" 
                                wire:click="executeExport" 
                                @if($this->exportStats['total'] === 0) disabled @endif
                                class="inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1" />
                            匯出設定
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 匯入對話框 --}}
    @if($showImportDialog)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     wire:click="closeImportDialog" 
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    {{-- 標題 --}}
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <x-heroicon-o-arrow-up-tray class="w-6 h-6 mr-2 text-green-600" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">匯入設定</h3>
                        </div>
                        <button type="button" 
                                wire:click="closeImportDialog"
                                class="bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">關閉</span>
                            <x-heroicon-o-x-mark class="h-6 w-6" />
                        </button>
                    </div>

        <div class="space-y-6">
            {{-- 步驟指示器 --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center {{ $currentStep === 'upload' ? 'text-blue-600' : ($currentStep !== 'upload' ? 'text-green-600' : 'text-gray-400') }}">
                        <div class="w-8 h-8 rounded-full border-2 {{ $currentStep === 'upload' ? 'border-blue-600 bg-blue-50' : ($currentStep !== 'upload' ? 'border-green-600 bg-green-50' : 'border-gray-300') }} flex items-center justify-center">
                            @if($currentStep !== 'upload')
                                <x-heroicon-s-check class="w-4 h-4" />
                            @else
                                <span class="text-sm font-medium">1</span>
                            @endif
                        </div>
                        <span class="ml-2 text-sm font-medium">上傳檔案</span>
                    </div>
                    
                    <div class="w-8 h-px bg-gray-300"></div>
                    
                    <div class="flex items-center {{ in_array($currentStep, ['conflicts', 'preview']) ? 'text-blue-600' : ($currentStep === 'results' ? 'text-green-600' : 'text-gray-400') }}">
                        <div class="w-8 h-8 rounded-full border-2 {{ in_array($currentStep, ['conflicts', 'preview']) ? 'border-blue-600 bg-blue-50' : ($currentStep === 'results' ? 'border-green-600 bg-green-50' : 'border-gray-300') }} flex items-center justify-center">
                            @if($currentStep === 'results')
                                <x-heroicon-s-check class="w-4 h-4" />
                            @else
                                <span class="text-sm font-medium">2</span>
                            @endif
                        </div>
                        <span class="ml-2 text-sm font-medium">預覽與設定</span>
                    </div>
                    
                    <div class="w-8 h-px bg-gray-300"></div>
                    
                    <div class="flex items-center {{ $currentStep === 'results' ? 'text-blue-600' : 'text-gray-400' }}">
                        <div class="w-8 h-8 rounded-full border-2 {{ $currentStep === 'results' ? 'border-blue-600 bg-blue-50' : 'border-gray-300' }} flex items-center justify-center">
                            <span class="text-sm font-medium">3</span>
                        </div>
                        <span class="ml-2 text-sm font-medium">匯入結果</span>
                    </div>
                </div>
            </div>

            {{-- 步驟內容 --}}
            @if($currentStep === 'upload')
                {{-- 檔案上傳步驟 --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            選擇設定檔案
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <x-heroicon-o-document-arrow-up class="mx-auto h-12 w-12 text-gray-400" />
                                <div class="flex text-sm text-gray-600">
                                    <label for="import-file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>上傳檔案</span>
                                        <input id="import-file" 
                                               type="file" 
                                               wire:model="importFile" 
                                               accept=".json"
                                               class="sr-only">
                                    </label>
                                    <p class="pl-1">或拖放檔案到此處</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    僅支援 JSON 格式，最大 10MB
                                </p>
                            </div>
                        </div>
                        @if($importFile)
                            <div class="mt-2 text-sm text-gray-600">
                                已選擇：{{ $importFile->getClientOriginalName() }}
                            </div>
                        @endif
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex">
                            <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">
                                    匯入說明
                                </h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>請確保檔案是從本系統匯出的 JSON 格式</li>
                                        <li>匯入前會檢查設定衝突並提供解決選項</li>
                                        <li>可以選擇性匯入特定分類或設定項目</li>
                                        <li>系統設定需要管理員權限才能修改</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($currentStep === 'conflicts')
                {{-- 衝突處理步驟 --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">處理設定衝突</h3>
                        <span class="text-sm text-gray-500">發現 {{ count($importConflicts) }} 個衝突</span>
                    </div>

                    {{-- 衝突解決策略 --}}
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    選擇衝突解決策略
                                </h3>
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" 
                                               wire:model.live="conflictResolution" 
                                               value="skip"
                                               class="text-yellow-600 border-gray-300 focus:ring-yellow-500">
                                        <span class="ml-2 text-sm text-yellow-700">跳過衝突項目（保持現有設定）</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" 
                                               wire:model.live="conflictResolution" 
                                               value="update"
                                               class="text-yellow-600 border-gray-300 focus:ring-yellow-500">
                                        <span class="ml-2 text-sm text-yellow-700">覆蓋現有設定</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" 
                                               wire:model.live="conflictResolution" 
                                               value="merge"
                                               class="text-yellow-600 border-gray-300 focus:ring-yellow-500">
                                        <span class="ml-2 text-sm text-yellow-700">智慧合併（保留系統屬性，更新值和描述）</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 衝突列表 --}}
                    <div class="max-h-96 overflow-y-auto border rounded-lg">
                        @foreach($importConflicts as $conflict)
                            <div class="border-b border-gray-200 last:border-b-0 p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            {{ $conflict['key'] }}
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            分類：{{ $conflict['category'] }}
                                            @if($conflict['is_system'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 ml-2">
                                                    系統設定
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                
                                @if($conflict['has_value_conflict'])
                                    <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">現有值：</span>
                                            <div class="mt-1 p-2 bg-red-50 border border-red-200 rounded text-red-800 font-mono text-xs">
                                                {{ is_array($conflict['existing_value']) ? json_encode($conflict['existing_value']) : $conflict['existing_value'] }}
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">新值：</span>
                                            <div class="mt-1 p-2 bg-green-50 border border-green-200 rounded text-green-800 font-mono text-xs">
                                                {{ is_array($conflict['new_value']) ? json_encode($conflict['new_value']) : $conflict['new_value'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            @elseif($currentStep === 'preview')
                {{-- 預覽步驟 --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">匯入預覽</h3>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center text-sm">
                                <input type="checkbox" 
                                       wire:model.live="validateImportData"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2">驗證資料格式</span>
                            </label>
                        </div>
                    </div>

                    {{-- 匯入統計 --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-blue-600">{{ $importPreview['total'] ?? 0 }}</div>
                            <div class="text-sm text-blue-600">總設定數</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-green-600">{{ $importPreview['new_settings'] ?? 0 }}</div>
                            <div class="text-sm text-green-600">新設定</div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-yellow-600">{{ $importPreview['existing_settings'] ?? 0 }}</div>
                            <div class="text-sm text-yellow-600">現有設定</div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-purple-600">{{ count($importPreview['categories'] ?? []) }}</div>
                            <div class="text-sm text-purple-600">分類數</div>
                        </div>
                    </div>

                    {{-- 分類選擇 --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-700">選擇要匯入的項目</h4>
                            <button type="button" 
                                    wire:click="toggleSelectAll"
                                    class="text-sm text-blue-600 hover:text-blue-500">
                                {{ count($selectedSettings) === count($importData) ? '取消全選' : '全選' }}
                            </button>
                        </div>

                        <div class="max-h-96 overflow-y-auto border rounded-lg">
                            @foreach(collect($importData)->groupBy('category') as $category => $settings)
                                <div class="border-b border-gray-200 last:border-b-0">
                                    <div class="p-4 bg-gray-50">
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   wire:click="toggleCategorySelection('{{ $category }}')"
                                                   @checked(in_array($category, $selectedCategories))
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <span class="ml-2 font-medium text-gray-900">
                                                {{ $this->availableCategories[$category]['name'] ?? $category }}
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500">({{ $settings->count() }} 項)</span>
                                        </label>
                                    </div>
                                    
                                    @if(in_array($category, $selectedCategories))
                                        <div class="divide-y divide-gray-100">
                                            @foreach($settings as $setting)
                                                <div class="p-3 pl-8">
                                                    <label class="flex items-center">
                                                        <input type="checkbox" 
                                                               wire:click="toggleSettingSelection('{{ $setting['key'] }}')"
                                                               @checked(in_array($setting['key'], $selectedSettings))
                                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                        <div class="ml-2 flex-1">
                                                            <div class="text-sm font-medium text-gray-900">{{ $setting['key'] }}</div>
                                                            @if(!empty($setting['description']))
                                                                <div class="text-xs text-gray-500">{{ $setting['description'] }}</div>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-400">{{ $setting['type'] }}</div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            @elseif($currentStep === 'results')
                {{-- 結果步驟 --}}
                <div class="space-y-4">
                    <div class="flex items-center">
                        @if($importResults['success'] ?? false)
                            <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 mr-2" />
                            <h3 class="text-lg font-medium text-green-900">匯入完成</h3>
                        @else
                            <x-heroicon-o-exclamation-circle class="w-6 h-6 text-red-600 mr-2" />
                            <h3 class="text-lg font-medium text-red-900">匯入完成（有錯誤）</h3>
                        @endif
                    </div>

                    {{-- 結果統計 --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-green-600">{{ $importResults['created'] ?? 0 }}</div>
                            <div class="text-sm text-green-600">新建設定</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-blue-600">{{ $importResults['updated'] ?? 0 }}</div>
                            <div class="text-sm text-blue-600">更新設定</div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-yellow-600">{{ $importResults['skipped'] ?? 0 }}</div>
                            <div class="text-sm text-yellow-600">跳過設定</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-red-600">{{ count($importResults['errors'] ?? []) }}</div>
                            <div class="text-sm text-red-600">錯誤數量</div>
                        </div>
                    </div>

                    {{-- 錯誤列表 --}}
                    @if(!empty($importResults['errors']))
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <h4 class="text-sm font-medium text-red-800 mb-2">錯誤詳情</h4>
                            <ul class="text-sm text-red-700 space-y-1">
                                @foreach($importResults['errors'] as $error)
                                    <li>• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>

                    {{-- 按鈕區域 --}}
                    <div class="mt-6 flex justify-between">
                        <div>
                            @if($currentStep !== 'upload')
                                <button type="button" 
                                        wire:click="resetImportState"
                                        class="inline-flex justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    重新開始
                                </button>
                            @endif
                        </div>
                        
                        <div class="flex space-x-3">
                            <button type="button" 
                                    wire:click="closeImportDialog"
                                    class="inline-flex justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ $currentStep === 'results' ? '關閉' : '取消' }}
                            </button>
                            
                            @if($currentStep === 'conflicts')
                                <button type="button" 
                                        wire:click="previewImport"
                                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                    預覽匯入
                                </button>
                            @elseif($currentStep === 'preview')
                                <button type="button" 
                                        wire:click="executeImport" 
                                        @if(empty($selectedSettings)) disabled @endif
                                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <x-heroicon-o-arrow-up-tray class="w-4 h-4 mr-1" />
                                    執行匯入
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // 處理檔案下載
    window.addEventListener('download-file', event => {
        const { content, filename, contentType } = event.detail;
        
        const blob = new Blob([content], { type: contentType });
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    });
</script>
@endpush