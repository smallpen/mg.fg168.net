<div class="space-y-6">
    {{-- 頁面標題 --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEditMode ? __('admin.users.edit') : __('admin.users.create') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $isEditMode ? '編輯使用者資訊和角色設定' : '建立新的使用者帳號' }}
            </p>
        </div>
        
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('admin.actions.back') }}
            </a>
        </div>
    </div>

    {{-- 表單 --}}
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- 基本資訊 --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                        基本資訊
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- 使用者名稱 --}}
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                使用者名稱 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="username"
                                   wire:model.debounce.300ms="username" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('username') border-red-500 @enderror"
                                   placeholder="輸入使用者名稱">
                            @error('username')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                3-20個字元，只能包含字母、數字和底線
                            </p>
                        </div>

                        {{-- 姓名 --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                姓名
                            </label>
                            <input type="text" 
                                   id="name"
                                   wire:model="name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('name') border-red-500 @enderror"
                                   placeholder="輸入真實姓名">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 電子郵件 --}}
                        <div class="md:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                電子郵件
                            </label>
                            <input type="email" 
                                   id="email"
                                   wire:model.debounce.300ms="email" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('email') border-red-500 @enderror"
                                   placeholder="輸入電子郵件地址">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- 密碼設定 --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                        {{ $isEditMode ? '變更密碼' : '密碼設定' }}
                        @if($isEditMode)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">（留空則不變更）</span>
                        @endif
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- 密碼 --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                密碼 
                                @if(!$isEditMode)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="password" 
                                   id="password"
                                   wire:model.debounce.300ms="password" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('password') border-red-500 @enderror"
                                   placeholder="輸入密碼">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            
                            {{-- 密碼強度指示器 --}}
                            @if(!empty($password))
                                <div class="mt-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-300 
                                                        @if($passwordStrength['color'] === 'red') bg-red-500
                                                        @elseif($passwordStrength['color'] === 'orange') bg-orange-500
                                                        @elseif($passwordStrength['color'] === 'yellow') bg-yellow-500
                                                        @elseif($passwordStrength['color'] === 'blue') bg-blue-500
                                                        @elseif($passwordStrength['color'] === 'green') bg-green-500
                                                        @endif"
                                                 style="width: {{ ($passwordStrength['strength'] / 5) * 100 }}%"></div>
                                        </div>
                                        <span class="text-xs font-medium 
                                                   @if($passwordStrength['color'] === 'red') text-red-600 dark:text-red-400
                                                   @elseif($passwordStrength['color'] === 'orange') text-orange-600 dark:text-orange-400
                                                   @elseif($passwordStrength['color'] === 'yellow') text-yellow-600 dark:text-yellow-400
                                                   @elseif($passwordStrength['color'] === 'blue') text-blue-600 dark:text-blue-400
                                                   @elseif($passwordStrength['color'] === 'green') text-green-600 dark:text-green-400
                                                   @endif">
                                            {{ $passwordStrength['label'] }}
                                        </span>
                                    </div>
                                    
                                    {{-- 密碼要求檢查 --}}
                                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1 text-xs">
                                        <div class="flex items-center space-x-1">
                                            @if($passwordStrength['checks']['length'])
                                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                            <span class="{{ $passwordStrength['checks']['length'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                至少8個字元
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            @if($passwordStrength['checks']['uppercase'])
                                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                            <span class="{{ $passwordStrength['checks']['uppercase'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                包含大寫字母
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            @if($passwordStrength['checks']['lowercase'])
                                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                            <span class="{{ $passwordStrength['checks']['lowercase'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                包含小寫字母
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            @if($passwordStrength['checks']['numbers'])
                                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                            <span class="{{ $passwordStrength['checks']['numbers'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                包含數字
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- 確認密碼 --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                確認密碼
                                @if(!$isEditMode)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="password" 
                                   id="password_confirmation"
                                   wire:model.debounce.300ms="password_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('password') border-red-500 @enderror"
                                   placeholder="再次輸入密碼">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 側邊欄 --}}
            <div class="space-y-6">
                {{-- 狀態設定 --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        狀態設定
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="is_active"
                                   wire:model="is_active"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                啟用帳號
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            停用的帳號將無法登入系統
                        </p>
                    </div>
                </div>

                {{-- 角色指派 --}}
                @if($this->hasPermission('users.assign_roles'))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        角色指派
                    </h3>
                    
                    <div class="space-y-3">
                        @forelse($availableRoles as $role)
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" 
                                           id="role_{{ $role->id }}"
                                           wire:click="toggleRole({{ $role->id }})"
                                           {{ $this->isRoleSelected($role->id) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="role_{{ $role->id }}" class="font-medium text-gray-900 dark:text-white cursor-pointer">
                                        {{ $role->display_name }}
                                    </label>
                                    @if($role->description)
                                        <p class="text-gray-500 dark:text-gray-400">
                                            {{ $role->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                沒有可用的角色
                            </p>
                        @endforelse
                    </div>
                    
                    @if(count($selectedRoles) > 0)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                已選擇 {{ count($selectedRoles) }} 個角色
                            </p>
                        </div>
                    @endif
                </div>
                @endif

                {{-- 操作按鈕 --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="space-y-3">
                        <button type="submit" 
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $isEditMode ? __('admin.actions.update') : __('admin.actions.create') }}
                        </button>
                        
                        <button type="button" 
                                wire:click="resetForm"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ __('admin.actions.reset') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>