<div>
    {{-- Session 過期警告對話框 --}}
    @if($showWarning)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- 背景遮罩 --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                {{-- 對話框內容 --}}
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Session 即將過期
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    您的 Session 將在 <span class="font-semibold text-red-600">{{ $this->formattedRemainingTime }}</span> 後過期。
                                    如果您想繼續使用系統，請點擊「延長 Session」按鈕。
                                </p>
                                
                                {{-- 倒數計時器 --}}
                                <div class="mt-4 bg-gray-50 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">剩餘時間：</span>
                                        <span class="text-lg font-mono font-bold text-red-600" 
                                              x-data="{ time: {{ $remainingTime }} }"
                                              x-init="setInterval(() => { 
                                                  if (time > 0) time--; 
                                                  $el.textContent = Math.floor(time/60).toString().padStart(2,'0') + ':' + (time%60).toString().padStart(2,'0');
                                                  if (time <= 0) $wire.forceLogout('Session 已過期');
                                              }, 1000)">
                                            {{ $this->formattedRemainingTime }}
                                        </span>
                                    </div>
                                    
                                    {{-- 進度條 --}}
                                    <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-red-600 h-2 rounded-full transition-all duration-1000" 
                                             style="width: {{ ($remainingTime / (30 * 60)) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 操作按鈕 --}}
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                                wire:click="extendSession"
                                wire:loading.attr="disabled"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="extendSession">延長 Session</span>
                            <span wire:loading wire:target="extendSession" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                處理中...
                            </span>
                        </button>
                        
                        <button type="button" 
                                wire:click="logoutNow"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            立即登出
                        </button>
                        
                        <button type="button" 
                                wire:click="dismissWarning"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:mr-3 sm:w-auto sm:text-sm">
                            稍後提醒
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- JavaScript 用於監聽使用者活動 --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let activityTimer;
            
            // 監聽使用者活動事件
            const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
            
            const resetTimer = () => {
                clearTimeout(activityTimer);
                
                // 觸發 Livewire 事件
                @this.dispatch('user-activity');
                
                // 設定下次檢查時間（每 30 秒檢查一次）
                activityTimer = setTimeout(() => {
                    @this.dispatch('check-session-status');
                }, 30000);
            };
            
            // 綁定事件監聽器
            events.forEach(event => {
                document.addEventListener(event, resetTimer, true);
            });
            
            // 初始化計時器
            resetTimer();
        });
    </script>
</div>