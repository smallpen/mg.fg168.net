{{-- 權限刪除確認對話框 --}}
<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- 背景遮罩 --}}
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     wire:click="closeModal" 
                     aria-hidden="true"></div>

                {{-- 對話框容器 --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    @if($permission)
                        {{-- 標題區域 --}}
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    {{ __('permissions.delete.title') }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        您即將刪除權限「<span class="font-medium text-gray-900 dark:text-white">{{ $permission->display_name }}</span>」
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        權限名稱：{{ $permission->name }}
                                    </p>
                                </div>
                            </div>
                            {{-- 關閉按鈕 --}}
                            <div class="absolute top-0 right-0 pt-4 pr-4">
                                <button type="button" 
                                        wire:click="closeModal"
                                        class="bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <span class="sr-only">關閉</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- 權限資訊 --}}
                        <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">權限資訊</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">模組：</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $permission->module }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">類型：</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $permission->type }}</span>
                                </div>
                                @if($permission->description)
                                    <div class="col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">描述：</span>
                                        <span class="text-gray-900 dark:text-white">{{ $permission->description }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- 刪除前檢查結果 --}}
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">刪除前檢查</h4>
                            <div class="space-y-3">
                                @foreach($deleteChecks as $checkType => $check)
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 {{ $this->getCheckColor($check['status']) }}" fill="currentColor" viewBox="0 0 20 20">
                                                @if($check['status'] === 'success')
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                @elseif($check['status'] === 'warning')
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                @elseif($check['status'] === 'error')
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                @else
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                @endif
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-900 dark:text-white">{{ $check['message'] }}</p>
                                            
                                            {{-- 顯示詳細資訊 --}}
                                            @if(isset($check['details']) && !empty($check['details']))
                                                <div class="mt-1">
                                                    @if($checkType === 'roles')
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            角色：{{ implode('、', array_slice($check['details'], 0, 3)) }}
                                                            @if(count($check['details']) > 3)
                                                                等 {{ count($check['details']) }} 個
                                                            @endif
                                                        </p>
                                                    @elseif($checkType === 'dependencies')
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            依賴權限：{{ implode('、', array_slice($check['details'], 0, 3)) }}
                                                            @if(count($check['details']) > 3)
                                                                等 {{ count($check['details']) }} 個
                                                            @endif
                                                        </p>
                                                    @elseif($checkType === 'dependents')
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            被依賴權限：{{ implode('、', array_slice($check['details'], 0, 3)) }}
                                                            @if(count($check['details']) > 3)
                                                                等 {{ count($check['details']) }} 個
                                                            @endif
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- 阻塞性問題警告 --}}
                        @if($hasBlockingIssues)
                            <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-md dark:bg-red-900/20 dark:border-red-800">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                            無法刪除此權限
                                        </h3>
                                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                            此權限存在阻塞性問題，必須先解決這些問題才能進行刪除。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- 確認輸入（僅在可以刪除時顯示） --}}
                        @if($canDelete)
                            <div class="mt-6">
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                                確認永久刪除
                                            </h3>
                                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                                <p>此操作無法復原，請謹慎操作。</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label for="confirmationText" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $this->confirmLabel }}
                                    </label>
                                    <input type="text" 
                                           id="confirmationText"
                                           wire:model.defer="confirmationText"
                                           wire:key="permission-delete-confirm-text"
                                           placeholder="{{ $permission->name }}"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"
                                           autocomplete="off">
                                    @error('confirmationText')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        {{-- 按鈕區域 --}}
                        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 space-y-reverse sm:space-y-0">
                            {{-- 取消按鈕 --}}
                            <button type="button" 
                                    wire:click="closeModal"
                                    class="w-full sm:w-auto inline-flex justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                取消
                            </button>

                            {{-- 確認刪除按鈕 --}}
                            @if($canDelete)
                                <button type="button" 
                                        wire:click="executeDelete"
                                        :disabled="!$this->canConfirm"
                                        class="{{ $this->confirmButtonClass }}">
                                    @if($processing)
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @endif
                                    {{ $this->confirmButtonText }}
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        // 監聽權限刪除模態重置事件
        Livewire.on('permission-delete-modal-reset', () => {
            console.log('🔄 收到 permission-delete-modal-reset 事件，手動更新前端...');
            
            setTimeout(() => {
                // 清除權限刪除模態表單欄位
                const deleteModal = document.querySelector('[wire\\:click="executeDelete"]');
                if (deleteModal) {
                    const modalContainer = deleteModal.closest('.fixed');
                    if (modalContainer) {
                        const inputs = modalContainer.querySelectorAll('input[type="text"]');
                        inputs.forEach(input => {
                            input.value = '';
                            // 觸發 blur 事件確保 Livewire 同步
                            input.dispatchEvent(new Event('blur', { bubbles: true }));
                        });
                    }
                }
            }, 100);
        });
    });
</script>