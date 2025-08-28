{{-- 使用者刪除確認對話框 --}}
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

                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    {{-- 標題區域 --}}
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                {{ __('admin.users.select_action') }}
                            </h3>
                            @if($user)
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        使用者：<span class="font-medium text-gray-900 dark:text-white">{{ $user->username }}</span>
                                        @if($user->name)
                                            ({{ $user->name }})
                                        @endif
                                    </p>
                                </div>
                            @endif
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

                    {{-- 操作選項 --}}
                    <div class="mt-6 space-y-4">
                        {{-- 停用選項 --}}
                        <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 {{ $selectedAction === 'disable' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" 
                                   wire:model.defer="selectedAction" 
                                   wire:key="user-delete-action-disable"
                                   value="disable"
                                   class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <div class="ml-3 flex-1">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ __('admin.users.disable_user') }}
                                    </span>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ __('admin.users.recommended') }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.users.disable_action_description') }}
                                </p>
                            </div>
                        </label>

                        {{-- 刪除選項 --}}
                        <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 {{ $selectedAction === 'delete' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <input type="radio" 
                                   wire:model.defer="selectedAction" 
                                   wire:key="user-delete-action-delete"
                                   value="delete"
                                   class="h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500">
                            <div class="ml-3 flex-1">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ __('admin.users.delete_permanently') }}
                                    </span>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ __('admin.users.irreversible') }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.users.delete_action_description') }}
                                </p>
                            </div>
                        </label>
                    </div>

                    {{-- 確認輸入框（僅在選擇刪除時顯示） --}}
                    @if($this->showConfirmInput)
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
                                            <p>{{ __('admin.users.type_username_to_confirm') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="confirmText" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $this->confirmLabel }}
                                </label>
                                <input type="text" 
                                       id="confirmText"
                                       wire:model.defer="confirmText"
                                       wire:key="user-delete-confirm-text"
                                       placeholder="{{ $user?->username }}"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"
                                       autocomplete="off">
                                @error('confirmText')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif

                    {{-- 錯誤訊息 --}}
                    @error('selectedAction')
                        <div class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                                </div>
                            </div>
                        </div>
                    @enderror

                    {{-- 按鈕區域 --}}
                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 space-y-reverse sm:space-y-0">
                        {{-- 取消按鈕 --}}
                        <button type="button" 
                                wire:click="closeModal"
                                class="w-full sm:w-auto inline-flex justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            {{ __('admin.actions.cancel') }}
                        </button>

                        {{-- 確認按鈕 --}}
                        <button type="button" 
                                wire:click="executeAction"
                                :disabled="!$this->canConfirm"
                                class="w-full sm:w-auto inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white transition-colors duration-200
                                       {{ $selectedAction === 'delete' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500' }}
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            @if($processing)
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('admin.users.processing') }}
                            @else
                                @if($selectedAction === 'delete')
                                    {{ __('admin.users.confirm_delete') }}
                                @elseif($selectedAction === 'disable')
                                    {{ __('admin.users.confirm_disable') }}
                                @else
                                    {{ __('admin.actions.confirm') }}
                                @endif
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        // 監聽使用者刪除模態重置事件
        Livewire.on('user-delete-modal-reset', () => {
            console.log('🔄 收到 user-delete-modal-reset 事件，手動更新前端...');
            
            setTimeout(() => {
                // 清除使用者刪除模態表單欄位
                const deleteModal = document.querySelector('[wire\\:click="executeAction"]');
                if (deleteModal) {
                    const modalContainer = deleteModal.closest('.fixed');
                    if (modalContainer) {
                        const inputs = modalContainer.querySelectorAll('input[type="radio"], input[type="text"]');
                        inputs.forEach(input => {
                            if (input.type === 'radio') {
                                input.checked = false;
                            } else {
                                input.value = '';
                            }
                            // 觸發 blur 事件確保 Livewire 同步
                            input.dispatchEvent(new Event('blur', { bubbles: true }));
                        });
                    }
                }
            }, 100);
        });
    });
</script>