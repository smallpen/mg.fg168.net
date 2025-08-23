<div>
    <form wire:submit.prevent="save" class="space-y-12">

        {{-- 備份設定 --}}
        <div class="space-y-6 border-b border-gray-200 dark:border-gray-700 pb-10">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">備份設定</h3>
                <button type="button" wire:click="testBackup" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    測試備份
                </button>
            </div>
            
            <div class="space-y-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model.defer="settings.maintenance.auto_backup_enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">啟用自動備份</span>
                    </label>
                </div>

                @if ($settings['maintenance.auto_backup_enabled'])
                    <div>
                        <label for="backup_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">備份頻率</label>
                        <select id="backup_frequency" wire:model.defer="settings.maintenance.backup_frequency" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="hourly">每小時</option>
                            <option value="daily">每日</option>
                            <option value="weekly">每週</option>
                            <option value="monthly">每月</option>
                        </select>
                    </div>
                @endif

                <div>
                    <label for="backup_retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">備份保留天數</label>
                    <input type="number" id="backup_retention_days" wire:model.defer="settings.maintenance.backup_retention_days" min="1" max="365" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                </div>

                <div>
                    <label for="backup_storage_path" class="block text-sm font-medium text-gray-700 dark:text-gray-300">備份儲存路徑</label>
                    <input type="text" id="backup_storage_path" wire:model.defer="settings.maintenance.backup_storage_path" placeholder="留空使用預設路徑 storage/backups" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">自訂備份檔案儲存路徑，留空使用預設路徑</p>
                </div>

                {{-- 儲存驗證結果 --}}
                @if (isset($storageValidation['backup_path']))
                    <div class="rounded-md p-4 {{ $storageValidation['backup_path']['status'] === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                @if ($storageValidation['backup_path']['status'] === 'success')
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium {{ $storageValidation['backup_path']['status'] === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    {{ $storageValidation['backup_path']['message'] }}
                                </p>
                                @if (isset($storageValidation['backup_path']['free_space']))
                                    <p class="text-sm text-green-600 dark:text-green-300">可用空間: {{ $storageValidation['backup_path']['free_space'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 日誌設定 --}}
        <div class="space-y-6 border-b border-gray-200 dark:border-gray-700 pb-10">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">日誌設定</h3>
            <div class="space-y-6">
                <div>
                    <label for="log_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">日誌等級</label>
                    <select id="log_level" wire:model.defer="settings.maintenance.log_level" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                        <option value="debug">DEBUG（除錯）</option>
                        <option value="info">INFO（資訊）</option>
                        <option value="notice">NOTICE（注意）</option>
                        <option value="warning">WARNING（警告）</option>
                        <option value="error">ERROR（錯誤）</option>
                        <option value="critical">CRITICAL（嚴重）</option>
                        <option value="alert">ALERT（警報）</option>
                        <option value="emergency">EMERGENCY（緊急）</option>
                    </select>
                </div>
                <div>
                    <label for="log_retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">日誌保留天數</label>
                    <input type="number" id="log_retention_days" wire:model.defer="settings.maintenance.log_retention_days" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                </div>
            </div>
        </div>

        {{-- 快取設定 --}}
        <div class="space-y-6 border-b border-gray-200 dark:border-gray-700 pb-10">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">快取設定</h3>
                <button type="button" wire:click="clearCache" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    清除快取
                </button>
            </div>
            
            <div class="space-y-6">
                <div>
                    <label for="cache_driver" class="block text-sm font-medium text-gray-700 dark:text-gray-300">快取驅動</label>
                    <select id="cache_driver" wire:model.defer="settings.maintenance.cache_driver" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                        <option value="file">檔案快取</option>
                        <option value="redis">Redis</option>
                        <option value="memcached">Memcached</option>
                        <option value="array">陣列快取（僅測試用）</option>
                    </select>
                </div>

                <div>
                    <label for="cache_ttl" class="block text-sm font-medium text-gray-700 dark:text-gray-300">快取存活時間（秒）</label>
                    <input type="number" id="cache_ttl" wire:model.defer="settings.maintenance.cache_ttl" min="60" max="86400" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">範圍: 60-86400 秒（1分鐘到24小時）</p>
                </div>

                {{-- 快取連線驗證結果 --}}
                @if (isset($storageValidation['cache_connection']))
                    <div class="rounded-md p-4 {{ $storageValidation['cache_connection']['status'] === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                @if ($storageValidation['cache_connection']['status'] === 'success')
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium {{ $storageValidation['cache_connection']['status'] === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    {{ $storageValidation['cache_connection']['message'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 維護模式設定 --}}
        <div class="space-y-6 border-b border-gray-200 dark:border-gray-700 pb-10">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">維護模式</h3>
            <div class="space-y-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model.defer="settings.maintenance.maintenance_mode" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">啟用維護模式</span>
                    </label>
                    <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">⚠️ 啟用維護模式將阻止一般使用者存取系統</p>
                </div>
                
                @if ($settings['maintenance.maintenance_mode'])
                    <div>
                        <label for="maintenance_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">維護模式訊息</label>
                        <textarea id="maintenance_message" wire:model.defer="settings.maintenance.maintenance_message" rows="3" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">此訊息將顯示給嘗試存取系統的使用者</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- 系統監控設定 --}}
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">系統監控</h3>
                <button type="button" wire:click="testMonitoring" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    測試監控
                </button>
            </div>
            
            <div class="space-y-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model.defer="settings.maintenance.monitoring_enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">啟用系統監控</span>
                    </label>
                </div>

                @if ($settings['maintenance.monitoring_enabled'])
                    <div>
                        <label for="monitoring_interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300">監控間隔（秒）</label>
                        <input type="number" id="monitoring_interval" wire:model.defer="settings.maintenance.monitoring_interval" min="60" max="3600" class="mt-1 block w-full max-w-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">範圍: 60-3600 秒（1分鐘到1小時）</p>
                    </div>
                @endif

                {{-- 監控測試結果 --}}
                @if (isset($testResults['monitoring']))
                    <div class="rounded-md p-4 {{ $testResults['monitoring']['status'] === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                @if ($testResults['monitoring']['status'] === 'success')
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium {{ $testResults['monitoring']['status'] === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    {{ $testResults['monitoring']['message'] }}
                                </p>
                                @if (isset($testResults['monitoring']['data']))
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        <p>CPU 負載: {{ $testResults['monitoring']['data']['cpu_usage'] }}</p>
                                        <p>記憶體使用: {{ number_format($testResults['monitoring']['data']['memory_usage'] / 1024 / 1024, 2) }} MB</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-5">
            <div class="flex justify-end">
                <button type="button" wire:click="loadSettings" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">取消</button>
                <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="save">儲存</span>
                    <span wire:loading wire:target="save">儲存中...</span>
                </button>
            </div>
        </div>
    </form>

    {{-- 維護模式警告對話框 --}}
    @if ($showMaintenanceWarning)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/20 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                確認啟用維護模式
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    啟用維護模式將阻止一般使用者存取系統，只有管理員能夠登入。請確認您要繼續此操作。
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="confirmMaintenanceMode" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                            確認啟用
                        </button>
                        <button type="button" wire:click="cancelMaintenanceMode" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                            取消
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 測試結果顯示 --}}
    @if ($showStorageTest && isset($testResults['backup']))
        <div class="mt-8 bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">備份測試結果</h3>
                
                <div class="space-y-4">
                    <div class="rounded-md p-4 {{ $testResults['backup']['status'] === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                @if ($testResults['backup']['status'] === 'success')
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium {{ $testResults['backup']['status'] === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    {{ $testResults['backup']['message'] }}
                                </p>
                                @if (isset($testResults['backup']['details']))
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        @foreach ($testResults['backup']['details'] as $key => $detail)
                                            @if (is_array($detail))
                                                <p><strong>{{ ucfirst($key) }}:</strong> {{ $detail['message'] ?? $detail['status'] ?? 'N/A' }}</p>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="button" wire:click="$set('showStorageTest', false)" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                        關閉
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
