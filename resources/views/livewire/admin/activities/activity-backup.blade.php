<div class="space-y-6">
    <!-- 頁面標題 -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">活動記錄備份管理</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理活動記錄的備份、還原和清理操作
            </p>
        </div>
    </div>

    <!-- 分頁導航 -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button 
                wire:click="setActiveTab('backup')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'backup' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                建立備份
            </button>
            <button 
                wire:click="setActiveTab('restore')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'restore' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                還原備份
            </button>
            <button 
                wire:click="setActiveTab('manage')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'manage' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                管理備份
            </button>
        </nav>
    </div>

    <!-- 建立備份分頁 -->
    @if($activeTab === 'backup')
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">建立活動記錄備份</h3>
            
            <form wire:submit="createBackup" class="space-y-4">
                <!-- 日期範圍選擇 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="backupDateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            開始日期
                        </label>
                        <input 
                            type="date" 
                            id="backupDateFrom"
                            wire:model="backupDateFrom"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                        @error('backupDateFrom')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="backupDateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            結束日期
                        </label>
                        <input 
                            type="date" 
                            id="backupDateTo"
                            wire:model="backupDateTo"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                        @error('backupDateTo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 備份選項 -->
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="includeDeleted"
                            wire:model="includeDeleted"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="includeDeleted" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            包含已刪除的記錄
                        </label>
                    </div>
                </div>

                <!-- 執行按鈕 -->
                <div class="flex justify-end">
                    <button 
                        type="submit"
                        :disabled="$wire.isBackingUp"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg wire:loading wire:target="createBackup" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ $isBackingUp ? '備份中...' : '開始備份' }}
                    </button>
                </div>
            </form>

            <!-- 備份結果 -->
            @if(!empty($backupResult))
                <div class="mt-6 p-4 rounded-md {{ $backupResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            @if($backupResult['success'])
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium {{ $backupResult['success'] ? 'text-green-800' : 'text-red-800' }}">
                                {{ $backupResult['success'] ? '備份成功' : '備份失敗' }}
                            </h3>
                            @if($backupResult['success'])
                                <div class="mt-2 text-sm {{ $backupResult['success'] ? 'text-green-700' : 'text-red-700' }}">
                                    <p>備份名稱: {{ $backupResult['backup_name'] ?? 'N/A' }}</p>
                                    @if(isset($backupResult['data_export']['record_count']))
                                        <p>記錄數量: {{ number_format($backupResult['data_export']['record_count']) }} 筆</p>
                                    @endif
                                    @if(isset($backupResult['compression']['compression_ratio']))
                                        <p>壓縮率: {{ $backupResult['compression']['compression_ratio'] }}%</p>
                                    @endif
                                </div>
                            @else
                                <p class="mt-2 text-sm text-red-700">{{ $backupResult['error'] ?? '未知錯誤' }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- 還原備份分頁 -->
    @if($activeTab === 'restore')
        <div class="space-y-6">
            <!-- 從現有備份還原 -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">從現有備份還原</h3>
                
                @if(count($this->backups) > 0)
                    <div class="space-y-4">
                        <!-- 還原選項 -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="replaceExisting"
                                    wire:model="replaceExisting"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label for="replaceExisting" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    替換現有記錄
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="validateIntegrity"
                                    wire:model="validateIntegrity"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label for="validateIntegrity" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    驗證資料完整性
                                </label>
                            </div>
                        </div>

                        <!-- 備份列表 -->
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            檔案名稱
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            大小
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            建立時間
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            操作
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->backups as $backup)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $backup['filename'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $backup['size_mb'] }} MB
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $this->formatDateTime($backup['created_at']) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <button 
                                                    wire:click="restoreBackup('{{ $backup['path'] }}')"
                                                    :disabled="$wire.isRestoring"
                                                    class="text-blue-600 hover:text-blue-900 disabled:opacity-50"
                                                >
                                                    還原
                                                </button>
                                                <button 
                                                    wire:click="verifyBackup('{{ $backup['filename'] }}')"
                                                    class="text-green-600 hover:text-green-900"
                                                >
                                                    驗證
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">沒有找到可用的備份檔案</p>
                @endif
            </div>

            <!-- 上傳備份檔案還原 -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">上傳備份檔案還原</h3>
                
                <form wire:submit="uploadAndRestore" class="space-y-4">
                    <div>
                        <label for="restoreFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            選擇備份檔案 (.encrypted)
                        </label>
                        <input 
                            type="file" 
                            id="restoreFile"
                            wire:model="restoreFile"
                            accept=".encrypted"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        >
                        @error('restoreFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            :disabled="$wire.isRestoring || !$wire.restoreFile"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg wire:loading wire:target="uploadAndRestore" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ $isRestoring ? '還原中...' : '上傳並還原' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- 還原結果 -->
            @if(!empty($restoreResult))
                <div class="p-4 rounded-md {{ $restoreResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            @if($restoreResult['success'])
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium {{ $restoreResult['success'] ? 'text-green-800' : 'text-red-800' }}">
                                {{ $restoreResult['success'] ? '還原成功' : '還原失敗' }}
                            </h3>
                            @if($restoreResult['success'] && isset($restoreResult['data_import']))
                                <div class="mt-2 text-sm text-green-700">
                                    <p>總記錄數: {{ number_format($restoreResult['data_import']['total_records']) }} 筆</p>
                                    <p>匯入成功: {{ number_format($restoreResult['data_import']['imported_count']) }} 筆</p>
                                    @if($restoreResult['data_import']['skipped_count'] > 0)
                                        <p>跳過記錄: {{ number_format($restoreResult['data_import']['skipped_count']) }} 筆</p>
                                    @endif
                                </div>
                            @else
                                <p class="mt-2 text-sm text-red-700">{{ $restoreResult['error'] ?? '未知錯誤' }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- 管理備份分頁 -->
    @if($activeTab === 'manage')
        <div class="space-y-6">
            <!-- 備份列表 -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">備份檔案管理</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        共 {{ count($this->backups) }} 個備份檔案
                    </span>
                </div>
                
                @if(count($this->backups) > 0)
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        檔案名稱
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        大小
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        建立時間
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        雜湊值
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->backups as $backup)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $backup['filename'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $backup['size_mb'] }} MB
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $this->formatDateTime($backup['created_at']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                            {{ substr($backup['checksum'], 0, 16) }}...
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button 
                                                wire:click="downloadBackup('{{ $backup['filename'] }}')"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                下載
                                            </button>
                                            <button 
                                                wire:click="verifyBackup('{{ $backup['filename'] }}')"
                                                class="text-green-600 hover:text-green-900"
                                            >
                                                驗證
                                            </button>
                                            <button 
                                                wire:click="deleteBackup('{{ $backup['filename'] }}')"
                                                wire:confirm="確定要刪除此備份檔案嗎？"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                刪除
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">沒有找到備份檔案</p>
                @endif
            </div>

            <!-- 清理舊備份 -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">清理舊備份</h3>
                
                <form wire:submit="cleanupOldBackups" class="space-y-4">
                    <div>
                        <label for="cleanupDays" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            保留天數
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input 
                                type="number" 
                                id="cleanupDays"
                                wire:model="cleanupDays"
                                min="1"
                                max="3650"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm dark:bg-gray-600 dark:border-gray-600 dark:text-gray-400">
                                天
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            將刪除超過指定天數的備份檔案
                        </p>
                        @error('cleanupDays')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            :disabled="$wire.isCleaning"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg wire:loading wire:target="cleanupOldBackups" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ $isCleaning ? '清理中...' : '開始清理' }}
                        </button>
                    </div>
                </form>

                <!-- 清理結果 -->
                @if(!empty($cleanupResult))
                    <div class="mt-4 p-4 rounded-md {{ $cleanupResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                @if($cleanupResult['success'])
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium {{ $cleanupResult['success'] ? 'text-green-800' : 'text-red-800' }}">
                                    {{ $cleanupResult['success'] ? '清理完成' : '清理失敗' }}
                                </h3>
                                @if($cleanupResult['success'])
                                    <div class="mt-2 text-sm text-green-700">
                                        <p>刪除檔案: {{ $cleanupResult['deleted_count'] }} 個</p>
                                        @if($cleanupResult['deleted_size_mb'] > 0)
                                            <p>釋放空間: {{ $cleanupResult['deleted_size_mb'] }} MB</p>
                                        @endif
                                    </div>
                                @else
                                    <div class="mt-2 text-sm text-red-700">
                                        @foreach($cleanupResult['errors'] as $error)
                                            <p>{{ $error }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // 監聽 Livewire 事件並顯示通知
    document.addEventListener('livewire:init', () => {
        Livewire.on('backup-completed', (event) => {
            // 顯示成功通知
            console.log('Backup completed:', event.message);
        });

        Livewire.on('backup-failed', (event) => {
            // 顯示錯誤通知
            console.error('Backup failed:', event.message);
        });

        Livewire.on('restore-completed', (event) => {
            // 顯示成功通知
            console.log('Restore completed:', event.message);
        });

        Livewire.on('restore-failed', (event) => {
            // 顯示錯誤通知
            console.error('Restore failed:', event.message);
        });

        Livewire.on('verify-completed', (event) => {
            // 顯示驗證成功通知
            console.log('Verification completed:', event.message);
        });

        Livewire.on('verify-failed', (event) => {
            // 顯示驗證失敗通知
            console.error('Verification failed:', event.message);
        });

        Livewire.on('cleanup-completed', (event) => {
            // 顯示清理完成通知
            console.log('Cleanup completed:', event.message);
        });

        Livewire.on('cleanup-failed', (event) => {
            // 顯示清理失敗通知
            console.error('Cleanup failed:', event.message);
        });

        Livewire.on('backup-deleted', (event) => {
            // 顯示刪除成功通知
            console.log('Backup deleted:', event.message);
        });
    });
</script>
@endpush