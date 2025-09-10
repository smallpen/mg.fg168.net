<div class="space-y-6">
    <!-- 稽核控制面板 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">系統稽核</h2>
            
            <div class="flex items-center space-x-4">
                <!-- 稽核類型選擇 -->
                <select wire:model.live="auditType" 
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @foreach($auditTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                
                <!-- 執行稽核按鈕 -->
                <button wire:click="runAudit" 
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                    <svg wire:loading wire:target="runAudit" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="runAudit" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span wire:loading.remove wire:target="runAudit">執行稽核</span>
                    <span wire:loading wire:target="runAudit">執行中...</span>
                </button>
            </div>
        </div>

        <!-- 上次稽核時間 -->
        @if($lastAuditTime)
            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                上次稽核時間：{{ $lastAuditTime }}
            </div>
        @endif

        <!-- 稽核結果 -->
        @if(!empty($auditResults))
            <div class="space-y-4">
                <!-- 稽核摘要 -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $auditResults['summary']['total_checks'] ?? 0 }}</p>
                        <p class="text-sm text-blue-600 dark:text-blue-400">總檢查項目</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $auditResults['summary']['passed'] ?? 0 }}</p>
                        <p class="text-sm text-green-600 dark:text-green-400">通過</p>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ $auditResults['summary']['warnings'] ?? 0 }}</p>
                        <p class="text-sm text-yellow-600 dark:text-yellow-400">警告</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $auditResults['summary']['failed'] ?? 0 }}</p>
                        <p class="text-sm text-red-600 dark:text-red-400">失敗</p>
                    </div>
                </div>

                <!-- 詳細檢查結果 -->
                <div class="space-y-3">
                    @foreach($auditResults['checks'] ?? [] as $check)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <!-- 狀態圖示 -->
                                    @if($check['status'] === 'passed')
                                        <div class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    @elseif($check['status'] === 'warning')
                                        <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                            </svg>
                                        </div>
                                    @elseif($check['status'] === 'failed')
                                        <div class="flex-shrink-0 w-6 h-6 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $check['name'] }}</h3>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $check['description'] }}</p>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    @if($check['count'] > 0)
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($check['count']) }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $check['status'] }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $check['details'] }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- 匯出稽核報表 -->
                <div class="flex justify-end">
                    <button wire:click="exportAuditReport" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        匯出稽核報表
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- 異常監控 -->
    @if(!empty($anomalies))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">異常監控</h2>
            
            <div class="space-y-4">
                @foreach($anomalies as $anomaly)
                    <div class="border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <!-- 嚴重程度圖示 -->
                                @if($anomaly['severity'] === 'high')
                                    <div class="flex-shrink-0 w-6 h-6 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                @endif
                                
                                <div>
                                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ $anomaly['message'] }}</h3>
                                    <p class="text-xs text-red-600 dark:text-red-400">
                                        嚴重程度：{{ $anomaly['severity'] === 'high' ? '高' : '中' }} | 
                                        影響項目：{{ number_format($anomaly['count']) }} 個
                                    </p>
                                </div>
                            </div>
                            
                            <!-- 修復按鈕 -->
                            @if($anomaly['count'] > 0 && isset($anomaly['action']))
                                <button wire:click="openRepairModal('{{ $anomaly['action'] }}')" 
                                        class="inline-flex items-center px-3 py-1.5 border border-red-300 dark:border-red-600 rounded text-xs font-medium text-red-700 dark:text-red-300 bg-white dark:bg-red-900/20 hover:bg-red-50 dark:hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    修復
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 修復模態框 -->
    @if($showRepairModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    確認修復操作
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        即將修復 {{ count($repairItems) }} 個問題項目。此操作無法撤銷，請確認是否繼續？
                                    </p>
                                    
                                    @if(!empty($repairItems))
                                        <div class="mt-3 max-h-32 overflow-y-auto">
                                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                @foreach(array_slice($repairItems, 0, 5) as $item)
                                                    <li>• {{ $item['name'] ?? $item['account'] ?? 'ID: ' . $item['id'] }}</li>
                                                @endforeach
                                                @if(count($repairItems) > 5)
                                                    <li>• ... 還有 {{ count($repairItems) - 5 }} 個項目</li>
                                                @endif
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="executeRepair" 
                                type="button" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            執行修復
                        </button>
                        <button wire:click="closeRepairModal" 
                                type="button" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            取消
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>