<div class="relative">
    <!-- 使用者選單觸發按鈕 -->
    <button wire:click="toggle" 
            class="flex items-center p-1 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors duration-200">
        
        <!-- 使用者頭像 -->
        <div class="w-8 h-8 rounded-full overflow-hidden mr-2 border-2 border-gray-200 dark:border-gray-600">
            @if($this->currentUser && $this->currentUser->avatar)
                <img src="{{ $this->avatarUrl }}" 
                     alt="{{ $this->userDisplayName }}" 
                     class="w-full h-full object-cover">
            @else
                <div class="w-full h-full bg-primary-500 flex items-center justify-center">
                    <span class="text-sm font-medium text-white">
                        {{ $this->userInitials }}
                    </span>
                </div>
            @endif
        </div>
        
        <!-- 使用者名稱（桌面版顯示） -->
        <div class="hidden md:block text-left mr-2">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $this->userDisplayName }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-32">{{ $this->userEmail }}</p>
        </div>
        
        <!-- 下拉箭頭 -->
        <svg class="w-4 h-4 transition-transform duration-200 {{ $isOpen ? 'rotate-180' : '' }}" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    
    <!-- 使用者下拉選單 -->
    @if($isOpen)
        <div class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
             wire:click.away="close">
            
            <!-- 使用者資訊區域 -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <!-- 大頭像 -->
                    <div class="w-12 h-12 rounded-full overflow-hidden mr-3 border-2 border-gray-200 dark:border-gray-600 relative group cursor-pointer"
                         wire:click="showAvatarUploadDialog">
                        @if($this->currentUser && $this->currentUser->avatar)
                            <img src="{{ $this->avatarUrl }}" 
                                 alt="{{ $this->userDisplayName }}" 
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-primary-500 flex items-center justify-center">
                                <span class="text-lg font-medium text-white">
                                    {{ $this->userInitials }}
                                </span>
                            </div>
                        @endif
                        
                        <!-- 頭像上傳提示 -->
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- 使用者資訊 -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $this->userDisplayName }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $this->userEmail }}</p>
                        <p class="text-xs text-primary-600 dark:text-primary-400 truncate">{{ $this->userRoles }}</p>
                    </div>
                    
                    <!-- 編輯按鈕 -->
                    <button wire:click="showProfileEditDialog"
                            class="p-1 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- 選單項目 -->
            <div class="py-1">
                <!-- 個人資料 -->
                <button wire:click="goToProfile" 
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    個人資料
                </button>
                
                <!-- 帳號設定 -->
                <button wire:click="goToAccountSettings"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    帳號設定
                </button>
                
                <!-- 主題偏好 -->
                <div class="px-4 py-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-3 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">主題</span>
                        </div>
                        <div class="flex space-x-1">
                            <button wire:click="updateThemePreference('light')"
                                    class="p-1 rounded {{ $themePreference === 'light' ? 'bg-primary-100 text-primary-600' : 'text-gray-400 hover:text-gray-600' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </button>
                            <button wire:click="updateThemePreference('dark')"
                                    class="p-1 rounded {{ $themePreference === 'dark' ? 'bg-primary-100 text-primary-600' : 'text-gray-400 hover:text-gray-600' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                </svg>
                            </button>
                            <button wire:click="updateThemePreference('auto')"
                                    class="p-1 rounded {{ $themePreference === 'auto' ? 'bg-primary-100 text-primary-600' : 'text-gray-400 hover:text-gray-600' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 說明中心 -->
                <button wire:click="goToHelpCenter"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    說明中心
                </button>
                
                <!-- Session 資訊 -->
                @if($this->activeSessionsCount > 1)
                    <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span>活躍裝置：{{ $this->activeSessionsCount }}</span>
                            <button wire:click="logoutOtherDevices" 
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                登出其他裝置
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- 分隔線 -->
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
            
            <!-- 登出按鈕 -->
            <div class="py-1">
                <button wire:click="logout" 
                        class="flex items-center w-full px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    登出
                </button>
            </div>
        </div>
    @endif
    
    <!-- 頭像上傳對話框 -->
    @if($showAvatarUpload)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" 
             wire:click.self="close">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">更新頭像</h3>
                </div>
                
                <div class="px-6 py-4">
                    <!-- 目前頭像預覽 -->
                    <div class="flex justify-center mb-4">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-gray-200 dark:border-gray-600">
                            @if($avatar)
                                <img src="{{ $avatar->temporaryUrl() }}" 
                                     alt="預覽" 
                                     class="w-full h-full object-cover">
                            @elseif($this->currentUser && $this->currentUser->avatar)
                                <img src="{{ $this->avatarUrl }}" 
                                     alt="{{ $this->userDisplayName }}" 
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-primary-500 flex items-center justify-center">
                                    <span class="text-2xl font-medium text-white">
                                        {{ $this->userInitials }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- 檔案上傳 -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            選擇頭像檔案
                        </label>
                        <input type="file" 
                               wire:model="avatar" 
                               accept="image/*"
                               class="block w-full text-sm text-gray-500 dark:text-gray-400
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-lg file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-primary-50 file:text-primary-700
                                      hover:file:bg-primary-100
                                      dark:file:bg-primary-900 dark:file:text-primary-300
                                      dark:hover:file:bg-primary-800">
                        @error('avatar') 
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                        @enderror
                    </div>
                    
                    <!-- 上傳提示 -->
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        支援 JPG、PNG、GIF 格式，檔案大小不超過 2MB
                    </p>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                    <div>
                        @if($this->currentUser && $this->currentUser->avatar)
                            <button wire:click="removeAvatar" 
                                    class="px-4 py-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                移除頭像
                            </button>
                        @endif
                    </div>
                    
                    <div class="flex space-x-3">
                        <button wire:click="close" 
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            取消
                        </button>
                        <button wire:click="uploadAvatar" 
                                class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 disabled:opacity-50"
                                {{ !$avatar ? 'disabled' : '' }}>
                            上傳
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- 個人資料編輯對話框 -->
    @if($showProfileEdit)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" 
             wire:click.self="close">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">編輯個人資料</h3>
                </div>
                
                <form wire:submit.prevent="updateProfile">
                    <div class="px-6 py-4 space-y-4">
                        <!-- 姓名 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                姓名
                            </label>
                            <input type="text" 
                                   wire:model="name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100">
                            @error('name') 
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                            @enderror
                        </div>
                        
                        <!-- 電子郵件 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                電子郵件
                            </label>
                            <input type="email" 
                                   wire:model="email"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100">
                            @error('email') 
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                            @enderror
                        </div>
                        
                        <!-- 密碼更新區域 -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">更新密碼</h4>
                            
                            <!-- 目前密碼 -->
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    目前密碼
                                </label>
                                <input type="password" 
                                       wire:model="currentPassword"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100">
                                @error('currentPassword') 
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                                @enderror
                            </div>
                            
                            <!-- 新密碼 -->
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    新密碼
                                </label>
                                <input type="password" 
                                       wire:model="newPassword"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100">
                                @error('newPassword') 
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                                @enderror
                            </div>
                            
                            <!-- 確認新密碼 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    確認新密碼
                                </label>
                                <input type="password" 
                                       wire:model="newPasswordConfirmation"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <button type="button" 
                                wire:click="close" 
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            取消
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                            儲存變更
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>