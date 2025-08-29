<div class="space-y-6">


    {{-- 表單 --}}
    <form wire:submit="save" class="space-y-6">
        {{-- 基本資訊卡片 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('admin.users.basic_info') }}
                </h3>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- 使用者名稱 --}}
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.username') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="username"
                               wire:model="username" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('username') border-red-500 @enderror"
                               placeholder="{{ __('admin.users.username_placeholder') }}">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 姓名 --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name"
                               wire:model="name" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('name') border-red-500 @enderror"
                               placeholder="{{ __('admin.users.name_placeholder') }}">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 電子郵件 --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.email') }}
                        </label>
                        <input type="email" 
                               id="email"
                               wire:model="email" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('email') border-red-500 @enderror"
                               placeholder="{{ __('admin.users.email_placeholder') }}">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 狀態 --}}
                    @if($this->canModifyStatus)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.status') }}
                        </label>
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="is_active"
                                   wire:model="is_active" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('admin.users.active') }}
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.users.status_help') }}
                        </p>
                    </div>
                    @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.status') }}
                        </label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                   {{ $is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            <span class="w-2 h-2 mr-1 rounded-full {{ $is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                            {{ $is_active ? __('admin.users.active') : __('admin.users.inactive') }}
                        </span>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.users.cannot_modify_status') }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 密碼設定卡片 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('admin.users.password_settings') }}
                </h3>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- 密碼 --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.password') }}
                            @if(!$isEditing) <span class="text-red-500">*</span> @endif
                        </label>
                        <div class="relative">
                            <input type="{{ $showPassword ? 'text' : 'password' }}" 
                                   id="password"
                                   wire:model="password" 
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('password') border-red-500 @enderror"
                                   placeholder="{{ $isEditing ? __('admin.users.password_optional') : __('admin.users.password_placeholder') }}">
                            <button type="button" 
                                    wire:click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                @if($showPassword)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                @endif
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if($isEditing)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('admin.users.password_edit_help') }}
                            </p>
                        @endif
                    </div>

                    {{-- 確認密碼 --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin.users.password_confirmation') }}
                            @if(!$isEditing) <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="{{ $showPassword ? 'text' : 'password' }}" 
                               id="password_confirmation"
                               wire:model="password_confirmation" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white @error('password_confirmation') border-red-500 @enderror"
                               placeholder="{{ __('admin.users.password_confirmation_placeholder') }}">
                        @error('password_confirmation')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 角色設定卡片 --}}
        @if($this->canModifyRoles)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('admin.users.role_assignment') }}
                </h3>
            </div>
            
            <div class="p-6">
                @if($availableRoles->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($availableRoles as $role)
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="role_{{ $role->id }}"
                                       wire:model="selectedRoles" 
                                       value="{{ $role->id }}"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <label for="role_{{ $role->id }}" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">{{ $role->display_name }}</span>
                                    @if($role->description)
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $role->description }}</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('selectedRoles')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @error('selectedRoles.*')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin.users.no_roles_available') }}
                    </p>
                @endif
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('admin.users.current_roles') }}
                </h3>
            </div>
            
            <div class="p-6">
                @if($user && $user->roles->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($user->roles as $role)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                       {{ $role->name === 'super_admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                          ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                           'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                                {{ $role->display_name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin.users.no_roles_assigned') }}
                    </p>
                @endif
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('admin.users.cannot_modify_roles') }}
                </p>
            </div>
        </div>
        @endif

        {{-- 操作按鈕 --}}
        <div class="flex justify-end space-x-3">
            <button type="button" 
                    wire:click="cancel"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                {{ __('admin.actions.cancel') }}
            </button>
            
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ ($user && $user->id) ? __('admin.actions.update') : __('admin.actions.create') }}
            </button>
        </div>
    </form>
</div>