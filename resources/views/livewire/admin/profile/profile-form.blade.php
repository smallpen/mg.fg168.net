<div class="space-y-6">
    <!-- 成功訊息 -->
    @if (session()->has('success'))
        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit="updateProfile" wire:key="profile-form" class="space-y-6">
        
        <!-- 頭像區域 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">個人頭像</h3>
                
                <div class="flex items-center space-x-6">
                    <div class="flex-shrink-0">
                        @if($current_avatar)
                            <img class="h-20 w-20 rounded-full object-cover" 
                                 src="{{ Storage::url($current_avatar) }}" 
                                 alt="目前頭像">
                        @else
                            <div class="h-20 w-20 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <svg class="h-10 w-10 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <label class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                上傳新頭像
                                <input type="file" wire:model="avatar" accept="image/*" class="hidden">
                            </label>
                            
                            @if($current_avatar)
                                <button type="button" wire:click="removeAvatar" 
                                        class="inline-flex items-center px-4 py-2 border border-red-300 dark:border-red-600 rounded-md shadow-sm text-sm font-medium text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900">
                                    移除頭像
                                </button>
                            @endif
                        </div>
                        
                        @if($avatar)
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    預覽新頭像：
                                </p>
                                <img class="mt-2 h-16 w-16 rounded-full object-cover" 
                                     src="{{ $avatar->temporaryUrl() }}" 
                                     alt="新頭像預覽">
                            </div>
                        @endif
                        
                        @error('avatar')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            支援 JPG、PNG 格式，檔案大小不超過 2MB
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 基本資料 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">基本資料</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 姓名 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            姓名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" wire:model.defer="name" 
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 使用者名稱 -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            使用者名稱 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="username" wire:model.defer="username" 
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 電子郵件 -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            電子郵件 <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" wire:model.defer="email" 
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 電話 -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            電話
                        </label>
                        <input type="tel" id="phone" wire:model.defer="phone" 
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 個人簡介 -->
                <div class="mt-6">
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        個人簡介
                    </label>
                    <textarea id="bio" wire:model.defer="bio" rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                              placeholder="簡單介紹一下自己..."></textarea>
                    @error('bio')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        最多 500 個字元
                    </p>
                </div>
            </div>
        </div>

        <!-- 偏好設定 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">偏好設定</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 時區 -->
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            時區 <span class="text-red-500">*</span>
                        </label>
                        <select id="timezone" wire:model.defer="timezone" 
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            @foreach($this->timezones as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 語言偏好 -->
                    <div>
                        <label for="language_preference" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            語言偏好 <span class="text-red-500">*</span>
                        </label>
                        <select id="language_preference" wire:model.defer="language_preference" 
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            @foreach($this->languages as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('language_preference')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 主題偏好 -->
                    <div>
                        <label for="theme_preference" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            主題偏好 <span class="text-red-500">*</span>
                        </label>
                        <select id="theme_preference" wire:model.defer="theme_preference" 
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <option value="light">亮色主題</option>
                            <option value="dark">深色主題</option>
                            <option value="system">跟隨系統</option>
                        </select>
                        @error('theme_preference')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 通知設定 -->
                <div class="mt-6">
                    <h4 class="text-base font-medium text-gray-900 dark:text-white mb-3">通知設定</h4>
                    
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="email_notifications" wire:model.defer="email_notifications" 
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded">
                            <label for="email_notifications" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                接收電子郵件通知
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="browser_notifications" wire:model.defer="browser_notifications" 
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded">
                            <label for="browser_notifications" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                接收瀏覽器通知
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 提交按鈕 -->
        <div class="flex justify-end space-x-3">
            <button type="button" wire:click="resetForm"
                    wire:key="profile-reset-btn"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    wire:loading.attr="disabled">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                重置表單
            </button>
            
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    wire:loading.attr="disabled">
                <svg wire:loading class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove>儲存變更</span>
                <span wire:loading>儲存中...</span>
            </button>
        </div>
    </form>
</div>

<script>
    // 監聽個人資料更新事件，重新載入頁面以應用主題變更
    document.addEventListener('livewire:init', () => {
        Livewire.on('profile-updated', () => {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
        
        // 監聽表單重置事件，手動更新前端表單元素
        Livewire.on('profile-form-reset', () => {
            console.log('🔄 收到 profile-form-reset 事件，手動更新前端表單...');
            
            setTimeout(() => {
                // 強制重新載入頁面以確保同步
                window.location.reload();
            }, 500);
        });
    });
</script>