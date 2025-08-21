{{-- 角色刪除確認模態 --}}
<div>
@if($showModal)
<div 
    x-data="{ show: @entangle('showModal') }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true"
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- 背景遮罩 --}}
        <div 
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="$wire.closeModal()"
        ></div>

        {{-- 模態內容 --}}
        <div 
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
        >
            @if($roleToDelete)
                <!-- 模態標題 -->
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.732L13.732 4.268c-.77-1.064-2.694-1.064-3.464 0L3.34 16.268C2.57 17.333 3.532 19 5.072 19z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                            刪除角色確認
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                您即將刪除角色「<span class="font-medium text-gray-900 dark:text-white">{{ $roleToDelete->display_name }}</span>」
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 刪除檢查結果 -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">刪除前檢查</h4>
                    <div class="space-y-3">
                        @foreach($deleteChecks as $checkType => $check)
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    @php
                                        $iconClass = $this->getCheckIcon($check['status']);
                                        $colorClass = $this->getCheckColor($check['status']);
                                    @endphp
                                    <svg class="h-5 w-5 {{ $colorClass }}" fill="currentColor" viewBox="0 0 20 20">
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
                                    
                                    <!-- 顯示詳細資訊 -->
                                    @if(isset($check['details']) && !empty($check['details']))
                                        <div class="mt-1">
                                            @if($checkType === 'users')
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    使用者：{{ implode('、', array_slice($check['details'], 0, 3)) }}
                                                    @if(count($check['details']) > 3)
                                                        等 {{ count($check['details']) }} 人
                                                    @endif
                                                </p>
                                            @elseif($checkType === 'children')
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    子角色：{{ implode('、', array_slice($check['details'], 0, 3)) }}
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

                <!-- 強制刪除選項（如果有阻塞性問題） -->
                @if($this->hasBlockingIssues)
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md dark:bg-yellow-900/20 dark:border-yellow-800">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    檢測到阻塞性問題
                                </h3>
                                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                    此角色存在阻塞性問題，建議先解決這些問題再進行刪除。
                                </p>
                                @if(!$roleToDelete->is_system_role)
                                    <div class="mt-3">
                                        <label class="flex items-center">
                                            <input 
                                                type="checkbox" 
                                                wire:model="forceDelete"
                                                class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                            >
                                            <span class="ml-2 text-sm text-yellow-700 dark:text-yellow-300">
                                                我了解風險，強制刪除此角色
                                            </span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- 確認輸入 -->
                @if($canDelete || $forceDelete)
                    <div class="mt-6">
                        <label for="confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            請輸入角色名稱「{{ $roleToDelete->display_name }}」以確認刪除
                        </label>
                        <div class="mt-1">
                            <input 
                                type="text" 
                                id="confirmation"
                                wire:model.live="confirmationText"
                                @class([
                                    'block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm',
                                    'border-red-300' => $errors->has('confirmation')
                                ])
                                placeholder="輸入角色名稱"
                            >
                        </div>
                        @error('confirmation')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <!-- 錯誤訊息 -->
                @if($errors->has('delete'))
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md dark:bg-red-900/20 dark:border-red-800">
                        <p class="text-sm text-red-800 dark:text-red-200">{{ $errors->first('delete') }}</p>
                    </div>
                @endif

                <!-- 操作按鈕 -->
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <button 
                        type="button"
                        wire:click="closeModal"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        取消
                    </button>
                    
                    @if($this->canConfirm)
                        <button 
                            type="button"
                            wire:click="confirmDelete"
                            wire:loading.attr="disabled"
                            wire:target="confirmDelete"
                            class="{{ $this->confirmButtonClass }}"
                        >
                            <span wire:loading.remove wire:target="confirmDelete">
                                {{ $this->confirmButtonText }}
                            </span>
                            <span wire:loading wire:target="confirmDelete" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                刪除中...
                            </span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endif
</div>