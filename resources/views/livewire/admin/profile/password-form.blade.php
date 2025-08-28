<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">變更密碼</h3>
        
        <!-- 成功訊息 -->
        @if (session()->has('password_success'))
            <div class="mb-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ session('password_success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit="updatePassword" class="space-y-6">
            <!-- 目前密碼 -->
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    目前密碼 <span class="text-red-500">*</span>
                </label>
                <input type="password" id="current_password" wire:model.lazy="current_password" 
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                       autocomplete="current-password">
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- 新密碼 -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    新密碼 <span class="text-red-500">*</span>
                </label>
                <input type="password" id="password" wire:model.lazy="password" 
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                       autocomplete="new-password">
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    密碼至少需要 8 個字元，建議包含大小寫字母、數字和特殊符號
                </p>
            </div>

            <!-- 確認新密碼 -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    確認新密碼 <span class="text-red-500">*</span>
                </label>
                <input type="password" id="password_confirmation" wire:model.lazy="password_confirmation" 
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                       autocomplete="new-password">
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- 密碼強度指示器 -->
            <div x-data="{ 
                password: @entangle('password'),
                strength: 0,
                strengthText: '很弱',
                strengthColor: 'bg-red-500'
            }" x-init="
                $watch('password', value => {
                    let score = 0;
                    if (value.length >= 8) score++;
                    if (/[a-z]/.test(value)) score++;
                    if (/[A-Z]/.test(value)) score++;
                    if (/[0-9]/.test(value)) score++;
                    if (/[^A-Za-z0-9]/.test(value)) score++;
                    
                    strength = score;
                    
                    switch(score) {
                        case 0:
                        case 1:
                            strengthText = '很弱';
                            strengthColor = 'bg-red-500';
                            break;
                        case 2:
                            strengthText = '弱';
                            strengthColor = 'bg-orange-500';
                            break;
                        case 3:
                            strengthText = '中等';
                            strengthColor = 'bg-yellow-500';
                            break;
                        case 4:
                            strengthText = '強';
                            strengthColor = 'bg-blue-500';
                            break;
                        case 5:
                            strengthText = '很強';
                            strengthColor = 'bg-green-500';
                            break;
                    }
                })
            " x-show="password.length > 0">
                <div class="mt-2">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-gray-600 dark:text-gray-400">密碼強度：</span>
                        <span class="text-sm font-medium" :class="{
                            'text-red-600': strength <= 1,
                            'text-orange-600': strength === 2,
                            'text-yellow-600': strength === 3,
                            'text-blue-600': strength === 4,
                            'text-green-600': strength === 5
                        }" x-text="strengthText"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-300" 
                             :class="strengthColor" 
                             :style="`width: ${(strength / 5) * 100}%`"></div>
                    </div>
                </div>
            </div>

            <!-- 安全提示 -->
            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            密碼安全建議
                        </h4>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <ul class="list-disc list-inside space-y-1">
                                <li>使用至少 8 個字元</li>
                                <li>包含大寫和小寫字母</li>
                                <li>包含數字和特殊符號</li>
                                <li>避免使用個人資訊</li>
                                <li>定期更換密碼</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 提交按鈕 -->
            <div class="flex justify-end">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        wire:loading.attr="disabled">
                    <svg wire:loading class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove>更新密碼</span>
                    <span wire:loading>更新中...</span>
                </button>
            </div>
        </form>
    </div>
</div>