<div class="space-y-6">
    <!-- 成功訊息 -->
    @if (session()->has('security_success'))
        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('security_success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- 兩步驟驗證 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">兩步驟驗證</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        為您的帳號增加額外的安全保護層
                    </p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $two_factor_enabled ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $two_factor_enabled ? '已啟用' : '未啟用' }}
                        </span>
                    </div>
                </div>
                <div class="ml-4">
                    <button wire:click="toggleTwoFactor" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 {{ $two_factor_enabled ? 'bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:ring-red-500' : 'bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:ring-green-500' }}">
                        {{ $two_factor_enabled ? '停用' : '啟用' }}
                    </button>
                </div>
            </div>
            
            @if($two_factor_enabled)
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-green-800 dark:text-green-200">
                                兩步驟驗證已啟用
                            </h4>
                            <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                                您的帳號現在受到兩步驟驗證保護。登入時需要提供驗證碼。
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- 通知設定 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">安全通知</h3>
            
            <form wire:submit="updateNotificationSettings" class="space-y-4">
                <div class="space-y-4">
                    <!-- 登入通知 -->
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">登入通知</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                當有新裝置登入時發送通知
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.lazy="login_notifications" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- 安全警報 -->
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">安全警報</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                當偵測到可疑活動時發送警報
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.lazy="security_alerts" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- 會話逾時 -->
                    <div>
                        <label for="session_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            會話逾時時間（分鐘）
                        </label>
                        <select id="session_timeout" wire:model.lazy="session_timeout" 
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <option value="30">30 分鐘</option>
                            <option value="60">1 小時</option>
                            <option value="120">2 小時</option>
                            <option value="240">4 小時</option>
                            <option value="480">8 小時</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            閒置超過此時間後將自動登出
                        </p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        儲存設定
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 會話管理 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">會話管理</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        管理您在其他裝置上的登入會話
                    </p>
                </div>
                <div class="ml-4">
                    <button wire:click="terminateOtherSessions" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        終止其他會話
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 最近活動 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">最近安全活動</h3>
            
            @if(count($recent_activities) > 0)
                <div class="space-y-3">
                    @foreach($recent_activities as $activity)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $activity['description'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $activity['created_at']->diffForHumans() }} • 
                                        {{ $activity['user_agent'] }} • 
                                        IP: {{ $activity['ip_address'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        目前沒有安全活動記錄
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>