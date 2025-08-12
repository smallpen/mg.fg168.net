<div>
    {{-- 異常活動警告對話框 --}}
    @if($showSecurityAlert)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-red-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                {{-- 對話框內容 --}}
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                @if($requiresReauth)
                                    <span class="text-red-600">安全警報：需要重新驗證</span>
                                @else
                                    <span class="text-yellow-600">安全提醒：檢測到異常活動</span>
                                @endif
                            </h3>
                            
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    @if($requiresReauth)
                                        我們檢測到您的帳號存在高風險的異常活動，為了保護您的帳號安全，請重新驗證您的身份。
                                    @else
                                        我們檢測到您的帳號存在一些異常活動，請確認這些活動是由您本人執行的。
                                    @endif
                                </p>
                                
                                {{-- 異常活動列表 --}}
                                @if(!empty($detectedActivities))
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">檢測到的異常活動：</h4>
                                        <ul class="space-y-2">
                                            @foreach($detectedActivities as $activity)
                                                <li class="flex items-start">
                                                    <div class="flex-shrink-0 mt-0.5">
                                                        @switch($activity['risk_level'])
                                                            @case('high')
                                                                <div class="h-2 w-2 bg-red-500 rounded-full"></div>
                                                                @break
                                                            @case('medium')
                                                                <div class="h-2 w-2 bg-yellow-500 rounded-full"></div>
                                                                @break
                                                            @default
                                                                <div class="h-2 w-2 bg-blue-500 rounded-full"></div>
                                                        @endswitch
                                                    </div>
                                                    <div class="ml-3 flex-1">
                                                        <p class="text-sm text-gray-700">{{ $activity['message'] }}</p>
                                                        <p class="text-xs text-gray-500">
                                                            {{ $activity['timestamp']->format('Y-m-d H:i:s') }}
                                                            <span class="ml-2 px-2 py-1 text-xs rounded-full
                                                                @if($activity['risk_level'] === 'high') bg-red-100 text-red-800
                                                                @elseif($activity['risk_level'] === 'medium') bg-yellow-100 text-yellow-800
                                                                @else bg-blue-100 text-blue-800 @endif">
                                                                {{ ucfirst($activity['risk_level']) }} 風險
                                                            </span>
                                                        </p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                
                                {{-- 安全建議 --}}
                                <div class="mt-4 bg-blue-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-blue-900 mb-2">安全建議：</h4>
                                    <ul class="text-sm text-blue-800 space-y-1">
                                        <li>• 確認您目前使用的裝置和網路環境是安全的</li>
                                        <li>• 檢查是否有其他人使用您的帳號</li>
                                        <li>• 考慮更改您的密碼以提高安全性</li>
                                        @if($requiresReauth)
                                            <li>• 立即重新驗證您的身份</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 操作按鈕 --}}
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        @if($requiresReauth)
                            <button type="button" 
                                    wire:click="reAuthenticate"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                重新驗證
                            </button>
                            
                            <button type="button" 
                                    wire:click="logoutImmediately"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                                立即登出
                            </button>
                        @else
                            <button type="button" 
                                    wire:click="acknowledgeAlert"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                確認並繼續
                            </button>
                            
                            <button type="button" 
                                    wire:click="logoutImmediately"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-red-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:mr-3 sm:w-auto sm:text-sm">
                                立即登出
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 安全狀態指示器（在頁面右上角顯示） --}}
    @if(!empty($detectedActivities) && !$showSecurityAlert)
        <div class="fixed top-4 right-4 z-40">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded-r-lg shadow-lg max-w-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-800">
                            檢測到 {{ count($detectedActivities) }} 個安全事件
                        </p>
                        <button wire:click="$set('showSecurityAlert', true)" 
                                class="text-xs text-yellow-600 hover:text-yellow-800 underline">
                            查看詳情
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>