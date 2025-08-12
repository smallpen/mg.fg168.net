<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">維護模式管理</h3>
                    <p class="mt-1 text-sm text-gray-500">管理系統的維護模式狀態和設定</p>
                </div>
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 mr-2">狀態：</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->maintenanceStatusColor }}">
                        {{ $this->maintenanceStatusText }}
                    </span>
                </div>
            </div>

            {{-- 維護模式狀態卡片 --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-{{ $isMaintenanceMode ? 'red' : 'green' }}-50 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($isMaintenanceMode)
                                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            @else
                                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-medium text-{{ $isMaintenanceMode ? 'red' : 'green' }}-900">
                                @if($isMaintenanceMode)
                                    系統維護中
                                @else
                                    系統正常運行
                                @endif
                            </h4>
                            <p class="text-sm text-{{ $isMaintenanceMode ? 'red' : 'green' }}-700">
                                @if($isMaintenanceMode)
                                    系統目前處於維護模式，一般使用者無法存取
                                @else
                                    系統正常運行，所有功能可正常使用
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-medium text-blue-900">快速操作</h4>
                            <div class="mt-2">
                                @if($isMaintenanceMode)
                                    <button wire:click="disableMaintenanceMode"
                                            wire:loading.attr="disabled"
                                            wire:confirm="確定要停用維護模式嗎？"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="disableMaintenanceMode">停用維護模式</span>
                                        <span wire:loading wire:target="disableMaintenanceMode">處理中...</span>
                                    </button>
                                @else
                                    <button wire:click="enableMaintenanceMode"
                                            wire:loading.attr="disabled"
                                            wire:confirm="確定要啟用維護模式嗎？"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="enableMaintenanceMode">啟用維護模式</span>
                                        <span wire:loading wire:target="enableMaintenanceMode">處理中...</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 維護模式設定 --}}
            <div class="space-y-6">
                {{-- 維護訊息設定 --}}
                <div>
                    <label for="maintenanceMessage" class="block text-sm font-medium text-gray-700">維護訊息</label>
                    <div class="mt-1">
                        <textarea wire:model="maintenanceMessage"
                                  id="maintenanceMessage"
                                  rows="3"
                                  class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                  placeholder="輸入維護期間顯示給使用者的訊息..."></textarea>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">此訊息將顯示在維護頁面上，告知使用者系統維護的原因。</p>
                </div>

                {{-- 預計恢復時間 --}}
                <div>
                    <label for="estimatedRecoveryTime" class="block text-sm font-medium text-gray-700">預計恢復時間</label>
                    <div class="mt-1">
                        <input type="datetime-local"
                               wire:model="estimatedRecoveryTime"
                               id="estimatedRecoveryTime"
                               class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <p class="mt-2 text-sm text-gray-500">設定系統預計恢復的時間，使用者將看到倒數計時。</p>
                </div>

                {{-- 允許的 IP 地址 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">允許存取的 IP 地址</label>
                    
                    {{-- 新增 IP 地址 --}}
                    <div class="flex items-end space-x-3 mb-4">
                        <div class="flex-1">
                            <input type="text"
                                   wire:model="newIpAddress"
                                   placeholder="輸入 IP 地址 (例如: 192.168.1.100)"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <button wire:click="addAllowedIp"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            新增
                        </button>
                        <button wire:click="addCurrentIp"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            新增當前 IP
                        </button>
                    </div>

                    {{-- IP 地址列表 --}}
                    @if(!empty($allowedIps))
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">允許的 IP 地址列表：</h4>
                            <div class="space-y-2">
                                @foreach($allowedIps as $ip)
                                    <div class="flex items-center justify-between bg-white rounded-md px-3 py-2">
                                        <span class="text-sm font-mono text-gray-900">{{ $ip }}</span>
                                        <button wire:click="removeAllowedIp('{{ $ip }}')"
                                                class="text-red-600 hover:text-red-800">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-800">
                                        沒有設定允許的 IP 地址。啟用維護模式後，只有管理員可以存取系統。
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- 維護模式進階設定 --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-4">進階設定</h4>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox"
                                   wire:model="maintenanceSettings.show_progress"
                                   id="show_progress"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="show_progress" class="ml-2 block text-sm text-gray-900">
                                顯示維護進度
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox"
                                   wire:model="maintenanceSettings.allow_admin_access"
                                   id="allow_admin_access"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="allow_admin_access" class="ml-2 block text-sm text-gray-900">
                                允許管理員存取
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox"
                                   wire:model="maintenanceSettings.custom_template"
                                   id="custom_template"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="custom_template" class="ml-2 block text-sm text-gray-900">
                                使用自訂維護頁面模板
                            </label>
                        </div>
                    </div>
                </div>

                {{-- 操作按鈕 --}}
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <div class="flex space-x-3">
                        <button wire:click="updateMaintenanceSettings"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            儲存設定
                        </button>
                        
                        <button wire:click="previewMaintenancePage"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            預覽維護頁面
                        </button>
                    </div>
                    
                    <div class="flex space-x-3">
                        @if($isMaintenanceMode)
                            <button wire:click="disableMaintenanceMode"
                                    wire:loading.attr="disabled"
                                    wire:confirm="確定要停用維護模式嗎？"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span wire:loading.remove wire:target="disableMaintenanceMode">停用維護模式</span>
                                <span wire:loading wire:target="disableMaintenanceMode">處理中...</span>
                            </button>
                        @else
                            <button wire:click="enableMaintenanceMode"
                                    wire:loading.attr="disabled"
                                    wire:confirm="確定要啟用維護模式嗎？這將阻止一般使用者存取系統。"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <span wire:loading.remove wire:target="enableMaintenanceMode">啟用維護模式</span>
                                <span wire:loading wire:target="enableMaintenanceMode">處理中...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>