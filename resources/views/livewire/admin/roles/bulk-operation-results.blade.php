{{-- 批量操作結果顯示 --}}
<div>
    @if($showResults)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="results-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="hideResults"></div>

                {{-- 模態內容 --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    {{-- 標題列 --}}
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full {{ $this->summaryStatus === 'success' ? 'bg-green-100 dark:bg-green-900/20' : ($this->summaryStatus === 'error' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-yellow-100 dark:bg-yellow-900/20') }} sm:mx-0 sm:h-10 sm:w-10">
                                    @if($this->summaryStatus === 'success')
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @elseif($this->summaryStatus === 'error')
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    @else
                                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="results-modal-title">
                                        {{ $this->operationTitle }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('admin.roles.bulk_results.completed_at', ['time' => now()->format('Y-m-d H:i:s')]) }}
                                    </p>
                                </div>
                            </div>
                            <button 
                                wire:click="hideResults"
                                class="bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <span class="sr-only">{{ __('admin.common.close') }}</span>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- 操作摘要 --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-4 sm:px-6">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $summary['total'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.roles.bulk_results.total_processed') }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">
                                    {{ $summary['successful'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.roles.bulk_results.successful') }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600">
                                    {{ $summary['failed'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.roles.bulk_results.failed') }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ $summary['success_rate'] }}%
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.roles.bulk_results.success_rate') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 詳細結果 --}}
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 sm:px-6 max-h-96 overflow-y-auto">
                        {{-- 成功結果 --}}
                        @if(!empty($this->successfulResults))
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-green-800 dark:text-green-200 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ __('admin.roles.bulk_results.successful_operations', ['count' => count($this->successfulResults)]) }}
                                </h4>
                                <div class="space-y-2">
                                    @foreach($this->successfulResults as $result)
                                        <div class="flex items-start p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                            <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                                    {{ $result['role']['display_name'] ?? $result['role']['name'] ?? __('admin.roles.bulk_results.unknown_role') }}
                                                </p>
                                                <p class="text-sm text-green-700 dark:text-green-300">
                                                    {{ $result['message'] ?? __('admin.roles.bulk_results.operation_completed') }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- 失敗結果 --}}
                        @if(!empty($this->failedResults))
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    {{ __('admin.roles.bulk_results.failed_operations', ['count' => count($this->failedResults)]) }}
                                </h4>
                                <div class="space-y-2">
                                    @foreach($this->failedResults as $result)
                                        <div class="flex items-start p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                            <svg class="w-5 h-5 text-red-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                                    {{ $result['role']['display_name'] ?? $result['role']['name'] ?? __('admin.roles.bulk_results.unknown_role') }}
                                                </p>
                                                <p class="text-sm text-red-700 dark:text-red-300">
                                                    {{ $result['message'] ?? __('admin.roles.bulk_results.operation_failed') }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- 操作按鈕 --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="hideResults"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ __('admin.common.close') }}
                        </button>
                        
                        @if(!empty($this->failedResults))
                            <button 
                                wire:click="retryFailedOperations"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ __('admin.roles.bulk_results.retry_failed') }}
                            </button>
                        @endif
                        
                        <button 
                            wire:click="exportResults"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('admin.roles.bulk_results.export_csv') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>