<div>
    {{-- Session 管理對話框 --}}
    @if($showSessionManager)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                {{-- 對話框內容 --}}
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            多裝置 Session 管理
                        </h3>
                        <button wire:click="closeSessionManager" 
                                class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Session 統計 --}}
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600">總 Session 數</p>
                                    <p class="text-2xl font-semibold text-blue-900">{{ $this->sessionStats['total'] }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600">當前 Session</p>
                                    <p class="text-2xl font-semibold text-green-900">{{ $this->sessionStats['current'] }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-yellow-600">其他 Session</p>
                                    <p class="text-2xl font-semibold text-yellow-900">{{ $this->sessionStats['others'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Session 列表 --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-md font-medium text-gray-900">活躍 Session</h4>
                            <button wire:click="refreshSessions" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                刷新
                            </button>
                        </div>

                        @if(empty($sessions))
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 20.5a7.962 7.962 0 01-5.291-2.209M6.228 6.228A10.018 10.018 0 0112 3a10.018 10.018 0 015.772 3.228m0 0A10.018 10.018 0 0121 12a10.018 10.018 0 01-3.228 5.772m0 0A10.018 10.018 0 0112 21a10.018 10.018 0 01-5.772-3.228" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">沒有找到活躍的 Session</p>
                            </div>
                        @else
                            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                <ul class="divide-y divide-gray-200">
                                    @foreach($sessions as $session)
                                        <li class="px-6 py-4 {{ $this->isCurrentSession($session['id']) ? 'bg-green-50' : '' }}">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0">
                                                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $this->getDeviceIcon($session['user_agent']) }}" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="flex items-center">
                                                            <p class="text-sm font-medium text-gray-900">
                                                                {{ $this->getBrowserName($session['user_agent']) }}
                                                                @if($this->isCurrentSession($session['id']))
                                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                        當前 Session
                                                                    </span>
                                                                @endif
                                                            </p>
                                                        </div>
                                                        <div class="mt-1">
                                                            <p class="text-sm text-gray-500">
                                                                IP: {{ $session['ip_address'] }} 
                                                                <span class="mx-2">•</span>
                                                                {{ $session['location'] }}
                                                            </p>
                                                            <p class="text-xs text-gray-400">
                                                                最後活動：{{ $this->formatLastActivity($session['last_activity']) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                @if(!$this->isCurrentSession($session['id']))
                                                    <div class="flex-shrink-0">
                                                        <button wire:click="terminateSession('{{ $session['id'] }}')"
                                                                wire:loading.attr="disabled"
                                                                wire:confirm="確定要終止此 Session 嗎？"
                                                                class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            終止
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- 密碼確認區域 --}}
                    @if($this->sessionStats['others'] > 0)
                        <div class="mb-6 bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">終止其他 Session</h4>
                            <p class="text-sm text-gray-600 mb-4">
                                為了安全起見，終止其他 Session 需要輸入您的密碼進行確認。
                            </p>
                            
                            <div class="flex items-end space-x-3">
                                <div class="flex-1">
                                    <label for="password" class="block text-sm font-medium text-gray-700">密碼</label>
                                    <input type="password" 
                                           wire:model="password"
                                           id="password"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           placeholder="請輸入您的密碼">
                                </div>
                                <button wire:click="terminateAllOtherSessions"
                                        wire:loading.attr="disabled"
                                        wire:confirm="確定要終止所有其他 Session 嗎？"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="terminateAllOtherSessions">終止所有其他 Session</span>
                                    <span wire:loading wire:target="terminateAllOtherSessions">處理中...</span>
                                </button>
                            </div>
                            
                            @if($errorMessage)
                                <p class="mt-2 text-sm text-red-600">{{ $errorMessage }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- 關閉按鈕 --}}
                    <div class="flex justify-end">
                        <button wire:click="closeSessionManager"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Session 管理按鈕（可以放在使用者選單中） --}}
    <button wire:click="openSessionManager" 
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
        <div class="flex items-center">
            <svg class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            管理 Session
            @if($this->sessionStats['others'] > 0)
                <span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    {{ $this->sessionStats['others'] }}
                </span>
            @endif
        </div>
    </button>
</div>