<div class="space-y-8">
    <form wire:submit.prevent="save" class="space-y-8">

        {{-- 備份設定卡片 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">備份設定</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">配置系統自動備份功能和儲存設定</p>
                        </div>
                    </div>
                    <button type="button" wire:click="testBackup" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        測試備份
                    </button>
                </div>
            </div>
            <div class="px-6 py-6">
            
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <label class="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                <input type="checkbox" wire:model.live="auto_backup_enabled" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">啟用自動備份</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">定期自動備份系統資料</p>
                                </div>
                            </label>
                        </div>

                        @if ($auto_backup_enabled)
                            <div>
                                <label for="backup_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">備份頻率</label>
                                <select id="backup_frequency" wire:model.live="backup_frequency" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                    <option value="hourly">每小時</option>
                                    <option value="daily">每日</option>
                                    <option value="weekly">每週</option>
                                    <option value="monthly">每月</option>
                                </select>
                            </div>
                        @endif

                        <div>
                            <label for="backup_retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">備份保留天數</label>
                            <input type="number" id="backup_retention_days" wire:model.live="backup_retention_days" min="1" max="365" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">範圍：1-365 天</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label for="backup_storage_path" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">備份儲存路徑</label>
                            <input type="text" id="backup_storage_path" wire:model.live="backup_storage_path" placeholder="留空使用預設路徑 storage/backups" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">自訂備份檔案儲存路徑，留空使用預設路徑</p>
                        </div>

                        {{-- 儲存驗證結果 --}}
                        @if (isset($storageValidation['backup_path']))
                            <div class="rounded-lg p-4 {{ $storageValidation['backup_path']['status'] === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
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
            </div>
        </div>

        {{-- 日誌設定卡片 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">日誌設定</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">配置系統日誌記錄等級和保留政策</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="log_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">日誌等級</label>
                        <select id="log_level" wire:model.live="log_level" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="debug">DEBUG（除錯）</option>
                            <option value="info">INFO（資訊）</option>
                            <option value="notice">NOTICE（注意）</option>
                            <option value="warning">WARNING（警告）</option>
                            <option value="error">ERROR（錯誤）</option>
                            <option value="critical">CRITICAL（嚴重）</option>
                            <option value="alert">ALERT（警報）</option>
                            <option value="emergency">EMERGENCY（緊急）</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">設定系統記錄的最低日誌等級</p>
                    </div>
                    <div>
                        <label for="log_retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">日誌保留天數</label>
                        <input type="number" id="log_retention_days" wire:model.live="log_retention_days" min="1" max="90" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">範圍：1-90 天</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 快取設定卡片 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">快取設定</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">配置系統快取驅動和存活時間</p>
                        </div>
                    </div>
                    <button type="button" wire:click="clearCache" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        清除快取
                    </button>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="cache_driver" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">快取驅動</label>
                        <select id="cache_driver" wire:model.live="cache_driver" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="file">檔案快取</option>
                            <option value="redis">Redis</option>
                            <option value="memcached">Memcached</option>
                            <option value="array">陣列快取（僅測試用）</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">選擇系統使用的快取驅動</p>
                    </div>

                    <div>
                        <label for="cache_ttl" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">快取存活時間（秒）</label>
                        <input type="number" id="cache_ttl" wire:model.live="cache_ttl" min="60" max="86400" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">範圍：60-86400 秒（1分鐘到24小時）</p>
                    </div>
                </div>

                @if (isset($storageValidation['cache_connection']))
                    <div class="lg:col-span-2">
                        <div class="rounded-lg p-4 {{ $storageValidation['cache_connection']['status'] === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
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
                    </div>
                @endif
            </div>
        </div>

        {{-- 維護模式設定卡片 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">維護模式</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">控制系統維護模式和使用者存取</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="space-y-6">
                    <div>
                        <label class="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <input type="checkbox" wire:model.live="maintenance_mode" class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500 dark:bg-gray-800 dark:border-gray-600">
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">啟用維護模式</span>
                                <p class="text-xs text-orange-600 dark:text-orange-400">⚠️ 啟用維護模式將阻止一般使用者存取系統</p>
                            </div>
                        </label>
                    </div>
                    
                    @if ($maintenance_mode)
                        <div>
                            <label for="maintenance_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">維護模式訊息</label>
                            <textarea id="maintenance_message" wire:model.live="maintenance_message" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="輸入維護模式時顯示給使用者的訊息..."></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">此訊息將顯示給嘗試存取系統的使用者</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 系統監控設定卡片 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">系統監控</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">配置系統效能和健康狀態監控</p>
                        </div>
                    </div>
                    <button type="button" wire:click="testMonitoring" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        測試監控
                    </button>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <input type="checkbox" wire:model.live="monitoring_enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">啟用系統監控</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">監控系統效能和健康狀態</p>
                            </div>
                        </label>
                    </div>

                    @if ($monitoring_enabled)
                        <div>
                            <label for="monitoring_interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">監控間隔（秒）</label>
                            <input type="number" id="monitoring_interval" wire:model.live="monitoring_interval" min="60" max="3600" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">範圍：60-3600 秒（1分鐘到1小時）</p>
                        </div>
                    @endif
                </div>

                @if (isset($testResults['monitoring']))
                    <div class="lg:col-span-2">
                        <div class="rounded-lg p-4 {{ $testResults['monitoring']['status'] === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
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
                    </div>
                @endif
            </div>
        </div>

        {{-- 表單操作按鈕 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>請確認所有設定正確後再儲存變更</p>
                        @if ($this->hasUnsavedChanges)
                            <p class="text-orange-600 dark:text-orange-400 font-medium">⚠️ 您有未儲存的變更</p>
                        @endif
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" wire:click="loadSettings" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            重置
                        </button>
                        <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200" wire:loading.attr="disabled">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="save">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" wire:loading wire:target="save">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">儲存設定</span>
                            <span wire:loading wire:target="save">儲存中...</span>
                        </button>
                    </div>
                </div>
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
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                確認啟用維護模式
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    啟用維護模式將阻止一般使用者存取系統，只有管理員可以繼續使用。請確認您要繼續此操作。
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="confirmMaintenanceMode" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                            確認啟用
                        </button>
                        <button type="button" wire:click="cancelMaintenanceMode" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            取消
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>