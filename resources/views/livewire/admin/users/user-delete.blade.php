{{-- 使用者刪除確認對話框 --}}
@if($showConfirmDialog)
<div class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true"
     x-data="{ show: @entangle('showConfirmDialog') }"
     x-show="show"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$wire.closeDialog()">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- 背景遮罩 --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             wire:click="closeDialog"
             wire:loading.class="cursor-not-allowed"
             wire:target="confirmDelete"></div>

        {{-- 對話框定位 --}}
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- 對話框內容 --}}
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="sm:flex sm:items-start">
                {{-- 警告圖示 --}}
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full 
                           {{ $deleteAction === 'delete' ? 'bg-red-100 dark:bg-red-900' : 'bg-orange-100 dark:bg-orange-900' }} sm:mx-0 sm:h-10 sm:w-10">
                    @if($deleteAction === 'delete')
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    @else
                        <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                        </svg>
                    @endif
                </div>

                {{-- 對話框內容 --}}
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    {{-- 標題 --}}
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                        @if($deleteAction === 'delete')
                            {{ __('admin.users.confirm_delete_title') }}
                        @else
                            {{ __('admin.users.confirm_disable_title') }}
                        @endif
                    </h3>

                    {{-- 使用者資訊 --}}
                    @if($user)
                    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ strtoupper(substr($user->display_name, 0, 1)) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $user->display_name }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.users.username') }}: {{ $user->username }}
                                </p>
                                @if($user->email)
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.users.email') }}: {{ $user->email }}
                                </p>
                                @endif
                                @if($user->roles->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($user->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                   {{ $role->name === 'super_admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                      ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                            {{ $role->display_name }}
                                        </span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- 操作選擇 --}}
                    <div class="mt-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.select_action') }}
                        </label>
                        <div class="mt-2 space-y-2">
                            {{-- 停用選項 --}}
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" 
                                       wire:model="deleteAction" 
                                       value="disable"
                                       id="action-disable"
                                       name="deleteAction"
                                       class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('admin.users.disable_user') }}
                                    <span class="text-gray-500 dark:text-gray-400">
                                        ({{ __('admin.users.recommended') }})
                                    </span>
                                </span>
                            </label>

                            {{-- 永久刪除選項 --}}
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" 
                                       wire:model="deleteAction" 
                                       value="delete"
                                       id="action-delete"
                                       name="deleteAction"
                                       class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('admin.users.delete_permanently') }}
                                    <span class="text-red-500 dark:text-red-400">
                                        ({{ __('admin.users.irreversible') }})
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- 操作說明 --}}
                    <div class="mt-4 p-3 rounded-lg {{ $deleteAction === 'delete' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-orange-50 dark:bg-orange-900/20' }}">
                        <p class="text-sm {{ $deleteAction === 'delete' ? 'text-red-700 dark:text-red-300' : 'text-orange-700 dark:text-orange-300' }}">
                            {{ $deleteActionDescription }}
                        </p>
                    </div>

                    {{-- 確認輸入（僅在永久刪除時顯示） --}}
                    @if($deleteAction === 'delete' && $user)
                    <div class="mt-4">
                        <label for="confirmUsername" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.confirm_username_label', ['username' => $user->username]) }}
                        </label>
                        <input type="text" 
                               id="confirmUsername"
                               wire:model.live="confirmUsername"
                               placeholder="{{ $user->username }}"
                               autocomplete="off"
                               x-init="$watch('$wire.deleteAction', value => { if (value === 'delete') { setTimeout(() => $el.focus(), 100) } })"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('confirmUsername') border-red-500 @enderror">
                        @error('confirmUsername')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.users.type_username_to_confirm') }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- 按鈕區域 --}}
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                {{-- 確認按鈕 --}}
                <button type="button" 
                        wire:click="confirmDelete"
                        wire:loading.attr="disabled"
                        wire:target="confirmDelete"
                        {{ !$canConfirm ? 'disabled' : '' }}
                        class="{{ $confirmButtonClass }} {{ !$canConfirm ? 'opacity-50 cursor-not-allowed' : '' }} sm:ml-3 sm:w-auto sm:text-sm">
                    <span wire:loading.remove wire:target="confirmDelete">
                        {{ $confirmButtonText }}
                    </span>
                    <span wire:loading wire:target="confirmDelete" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('admin.users.processing') }}
                    </span>
                </button>

                {{-- 取消按鈕 --}}
                <button type="button" 
                        wire:click="closeDialog"
                        wire:loading.attr="disabled"
                        wire:target="confirmDelete"
                        class="mt-3 w-full inline-flex justify-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 sm:mt-0 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('admin.actions.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif