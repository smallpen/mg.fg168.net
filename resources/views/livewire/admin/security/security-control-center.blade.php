<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">安全監控</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">監控和管理系統安全性狀態</p>
        </div>
    </div>

    @if($loading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">載入安全性狀態...</span>
        </div>
    @elseif(isset($securityStatus['error']))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">載入錯誤</h3>
                    <p class="mt-2 text-sm text-red-700">{{ $securityStatus['error'] }}</p>
                </div>
            </div>
        </div>
    @else
        {{-- 安全性概覽 --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {{-- 整體安全等級 --}}
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 {{ $this->securityLevelColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">安全等級</dt>
                                <dd class="text-lg font-medium {{ $this->securityLevelColor }}">{{ $this->securityLevelText }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Session 狀態 --}}
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Session 狀態</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    @if($securityStatus['session']['needs_refresh'] ?? false)
                                        <span class="text-yellow-600">需要刷新</span>
                                    @elseif($securityStatus['session']['idle_timeout_warning'] ?? false)
                                        <span class="text-red-600">即將過期</span>
                                    @else
                                        <span class="text-green-600">正常</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 並發 Session --}}
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">活躍 Session</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ $securityStatus['concurrent_sessions']['count'] ?? 0 }} / 
                                    {{ $securityStatus['concurrent_sessions']['max_allowed'] ?? 5 }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 安全警告 --}}
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 {{ $this->hasSecurityWarningsProperty ? 'text-red-600' : 'text-green-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">安全警告</dt>
                                <dd class="text-lg font-medium {{ $this->hasSecurityWarningsProperty ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $this->securityWarningCountProperty }} 個
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 安全性詳細資訊 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Session 安全性 --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Session 安全性</h3>
                        <button wire:click="executeSecurityAction('refresh_session')"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            刷新 Session
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Session 狀態</span>
                            <span class="text-sm font-medium {{ ($securityStatus['session']['is_expired'] ?? false) ? 'text-red-600' : 'text-green-600' }}">
                                {{ ($securityStatus['session']['is_expired'] ?? false) ? '已過期' : '正常' }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">需要刷新</span>
                            <span class="text-sm font-medium {{ ($securityStatus['session']['needs_refresh'] ?? false) ? 'text-yellow-600' : 'text-green-600' }}">
                                {{ ($securityStatus['session']['needs_refresh'] ?? false) ? '是' : '否' }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">閒置警告</span>
                            <span class="text-sm font-medium {{ ($securityStatus['session']['idle_timeout_warning'] ?? false) ? 'text-red-600' : 'text-green-600' }}">
                                {{ ($securityStatus['session']['idle_timeout_warning'] ?? false) ? '是' : '否' }}
                            </span>
                        </div>
                        
                        @if(isset($securityStatus['session']['last_activity']))
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">最後活動</span>
                                <span class="text-sm text-gray-900">
                                    {{ \Carbon\Carbon::createFromTimestamp($securityStatus['session']['last_activity'])->diffForHumans() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 異常活動檢測 --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">異常活動檢測</h3>
                        <button wire:click="handleSecurityWarning('suspicious_activity')"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            查看詳情
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">異常活動</span>
                            <span class="text-sm font-medium {{ ($securityStatus['suspicious_activity']['detected'] ?? false) ? 'text-red-600' : 'text-green-600' }}">
                                {{ ($securityStatus['suspicious_activity']['detected'] ?? false) ? '檢測到' : '正常' }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">IP 地址變更</span>
                            <span class="text-sm font-medium {{ ($securityStatus['suspicious_activity']['ip_changed'] ?? false) ? 'text-yellow-600' : 'text-green-600' }}">
                                {{ ($securityStatus['suspicious_activity']['ip_changed'] ?? false) ? '是' : '否' }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">裝置變更</span>
                            <span class="text-sm font-medium {{ ($securityStatus['suspicious_activity']['user_agent_changed'] ?? false) ? 'text-red-600' : 'text-green-600' }}">
                                {{ ($securityStatus['suspicious_activity']['user_agent_changed'] ?? false) ? '是' : '否' }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">異常時間</span>
                            <span class="text-sm font-medium {{ ($securityStatus['suspicious_activity']['unusual_time'] ?? false) ? 'text-yellow-600' : 'text-green-600' }}">
                                {{ ($securityStatus['suspicious_activity']['unusual_time'] ?? false) ? '是' : '否' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 系統狀態和快速操作 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- 系統狀態 --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">系統狀態</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">維護模式</span>
                            <div class="flex items-center">
                                <span class="text-sm font-medium {{ ($securityStatus['system']['maintenance_mode'] ?? false) ? 'text-red-600' : 'text-green-600' }}">
                                    {{ ($securityStatus['system']['maintenance_mode'] ?? false) ? '啟用' : '停用' }}
                                </span>
                                <button wire:click="handleSecurityWarning('maintenance_mode')"
                                        class="ml-2 text-blue-600 hover:text-blue-800 text-sm">
                                    管理
                                </button>
                            </div>
                        </div>
                        
                        @if(isset($securityStatus['system']['session_stats']))
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Session 統計</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">總 Session：</span>
                                        <span class="font-medium">{{ $securityStatus['system']['session_stats']['total_sessions'] ?? 0 }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">活躍 Session：</span>
                                        <span class="font-medium">{{ $securityStatus['system']['session_stats']['active_sessions'] ?? 0 }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">已認證：</span>
                                        <span class="font-medium">{{ $securityStatus['system']['session_stats']['authenticated_sessions'] ?? 0 }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">訪客：</span>
                                        <span class="font-medium">{{ $securityStatus['system']['session_stats']['guest_sessions'] ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 快速操作 --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">快速操作</h3>
                    
                    <div class="space-y-3">
                        <button wire:click="refreshSecurityStatus"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            刷新安全狀態
                        </button>
                        
                        <button wire:click="handleSecurityWarning('session_expiry')"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Session 管理
                        </button>
                        
                        <button wire:click="handleSecurityWarning('concurrent_sessions')"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            多裝置管理
                        </button>
                        
                        <button wire:click="executeSecurityAction('cleanup_sessions')"
                                wire:confirm="確定要清理過期的 Session 嗎？"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            清理過期 Session
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>