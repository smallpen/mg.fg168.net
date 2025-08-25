<div class="max-w-md w-full space-y-8">
    
    <!-- Logo 和標題 -->
    <div class="text-center">
        <div class="mx-auto h-16 w-16 bg-primary-600 rounded-full flex items-center justify-center">
            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h2 class="mt-6 text-3xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('auth.login.title') }}
        </h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('admin.welcome') }}
        </p>
    </div>
    
    <!-- 登入表單 -->
    <div class="card">
        <div class="card-body">
            
            <form wire:submit.prevent="login" class="space-y-6">
                
                <!-- 使用者名稱 -->
                <div>
                    <label for="username" class="form-label">{{ __('auth.login.username') }}</label>
                    <input id="username" 
                           wire:model.lazy="username"
                           type="text" 
                           required 
                           class="form-input @error('username') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror"
                           placeholder="{{ __('auth.validation.username_required') }}"
                           autocomplete="username">
                    @error('username')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- 密碼 -->
                <div>
                    <label for="password" class="form-label">{{ __('auth.login.password') }}</label>
                    <input id="password" 
                           wire:model.lazy="password"
                           type="password" 
                           required 
                           class="form-input @error('password') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror"
                           placeholder="{{ __('auth.validation.password_required') }}"
                           autocomplete="current-password">
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- 記住我 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" 
                               wire:model="remember"
                               type="checkbox" 
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                            {{ __('auth.login.remember') }}
                        </label>
                    </div>
                    
                    <div class="text-sm">
                        <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
                            {{ __('passwords.forgot') ?? '忘記密碼？' }}
                        </a>
                    </div>
                </div>
                
                <!-- 登入按鈕 -->
                <div>
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove>{{ __('auth.login.submit') }}</span>
                        <span wire:loading class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('auth.login.submit') }}...
                        </span>
                    </button>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <!-- 主題切換按鈕 -->
    <div class="text-center">
        <button data-theme-toggle 
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <svg class="w-4 h-4 mr-2 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <svg class="w-4 h-4 mr-2 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <span class="hidden dark:inline">{{ __('auth.theme.light') }}</span>
            <span class="inline dark:hidden">{{ __('auth.theme.dark') }}</span>
        </button>
    </div>
    
</div>