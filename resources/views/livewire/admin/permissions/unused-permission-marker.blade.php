<div>
    <!-- 標記按鈕 -->
    <button 
        wire:click="openModal"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
    >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
        </svg>
        標記未使用權限
    </button>

    <!-- 標記模態框 -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- 背景遮罩 -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <!-- 模態框內容 -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <!-- 標題 -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            標記未使用權限
                        </h3>
                        <button 
                            wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- 標記選項 -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">標記條件</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 天數閾值 -->
                            <div>
                                <label for="daysThreshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    未使用天數閾值
                                </label>
                                <select 
                                    wire:model.live="daysThreshold" 
                                    id="daysThreshold"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-600 dark:text-white sm:text-sm"
                                >
                                    <option value="30">30天</option>
                                    <option value="60">60天</option>
                                    <option value="90">90天</option>
                                    <option value="180">180天</option>
                                    <option value="365">365天</option>
                                </select>
                            </div>

                            <!-- 排除選項 -->
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="excludeSystemPermissions"
                                        class="rounded border-gray-300 dark:border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-600"
                                    >
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">排除系統權限</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="excludeWithDependents"
                                        class="rounded border-gray-300 dark:border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-600"
                                    >
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">排除被依賴的權限</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-4 flex space-x-3">
                            <button 
                                wire:click="generatePreview"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                            >
                                生成預覽
                            </button>
                            
                            @if($showPreview)
                            <button 
                                wire:click="refreshPreview"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                            >
                                重新生成
                            </button>
                            @endif
                        </div>
                    </div>

                    <!-- 預覽結果 -->
                    @if($showPreview && !empty($previewData))
                    <div class="mb-6">
                        <!-- 統計摘要 -->
                        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>預覽結果：</strong>
                                    找到 {{ $previewData['total_unused'] }} 個未使用權限，
                                    其中 {{ $previewData['marked_unused'] }} 個符合標記條件
                                    @if($previewData['excluded_system'] > 0)
                                    （已排除 {{ $previewData['excluded_system'] }} 個系統權限）
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(!empty($previewData['marked_permissions']))
                        <!-- 權限列表 -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                            <!-- 列表標題和全選 -->
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model.live="selectAll"
                                            class="rounded border-gray-300 dark:border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-600"
                                        >
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            全選 ({{ count($previewData['marked_permissions']) }} 個權限)
                                        </span>
                                    </label>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        已選擇 {{ count($selectedPermissions) }} 個
                                    </span>
                                </div>
                            </div>

                            <!-- 權限項目 -->
                            <div class="max-h-96 overflow-y-auto">
                                @foreach($previewData['marked_permissions'] as $permission)
                                @php
                                    $riskInfo = $this->getPermissionRiskLevel($permission);
                                @endphp
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center flex-1">
                                            <input 
                                                type="checkbox" 
                                                wire:model.live="selectedPermissions"
                                                value="{{ $permission['id'] }}"
                                                class="rounded border-gray-300 dark:border-gray-600 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-600"
                                            >
                                            <div class="ml-3 flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $permission['display_name'] }}
                                                    </h4>
                                                    <span class="{{ $riskInfo['badge_class'] }}">
                                                        {{ $riskInfo['badge_text'] }}
                                                    </span>
                                                    @if($permission['is_system_permission'])
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        系統
                                                    </span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $permission['module'] }} • {{ $permission['name'] }}
                                                </p>
                                                @if(!empty($riskInfo['reasons']))
                                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                                    風險因素: {{ implode(', ', $riskInfo['reasons']) }}
                                                </p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            創建於 {{ \Carbon\Carbon::parse($permission['created_at'])->format('Y-m-d') }}
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有符合條件的權限</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                根據目前的篩選條件，沒有找到需要標記的未使用權限
                            </p>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- 模態框底部按鈕 -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    @if($showPreview && !empty($previewData['marked_permissions']))
                    <button 
                        wire:click="executeMarking"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:ml-3 sm:w-auto sm:text-sm"
                        @if(empty($selectedPermissions) && !$selectAll) disabled @endif
                    >
                        執行標記 ({{ $selectAll ? count($previewData['marked_permissions']) : count($selectedPermissions) }})
                    </button>
                    @endif
                    
                    <button 
                        wire:click="closeModal"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        取消
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>