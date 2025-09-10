<div class="space-y-6">
    {{-- 表單卡片 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form wire:submit="save">
                <div class="grid grid-cols-1 gap-6">
                    {{-- 基本資訊區塊 --}}
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">基本資訊</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- 玩家姓名 --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    玩家姓名 <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="name"
                                    wire:model.blur="name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入玩家姓名"
                                />
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 使用者名稱 --}}
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    使用者名稱 <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="username"
                                    wire:model.blur="username"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('username') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入使用者名稱（英文字母、數字、底線）"
                                />
                                @error('username')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                
                                {{-- 帳號預覽 --}}
                                @if($previewAccount)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        完整帳號：<span class="font-medium text-blue-600 dark:text-blue-400">{{ $previewAccount }}</span>
                                    </p>
                                @endif
                            </div>

                            {{-- 電子郵件 --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    電子郵件 <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    id="email"
                                    wire:model.blur="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入電子郵件"
                                />
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 電話號碼 --}}
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    電話號碼
                                </label>
                                <input 
                                    type="text" 
                                    id="phone"
                                    wire:model.blur="phone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('phone') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入電話號碼（選填）"
                                />
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- 代理選擇區塊 --}}
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">隸屬代理</h3>
                        
                        <div class="space-y-4">
                            {{-- 代理搜尋和選擇 --}}
                            <div class="relative" x-data="{ open: @entangle('showAgentDropdown') }">
                                <label for="agentSearch" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    選擇隸屬代理 <span class="text-red-500">*</span>
                                </label>
                                
                                <div class="mt-1 relative">
                                    <input 
                                        type="text" 
                                        id="agentSearch"
                                        wire:model.live="agentSearchTerm"
                                        @click="$wire.toggleAgentDropdown()"
                                        @keydown.escape="open = false"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('agent_id') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                        placeholder="搜尋代理名稱或帳號..."
                                        autocomplete="off"
                                    />
                                    
                                    <button 
                                        type="button"
                                        @click="$wire.toggleAgentDropdown()"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2"
                                    >
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- 代理下拉選單 --}}
                                <div 
                                    x-show="open" 
                                    x-transition
                                    @click.away="open = false"
                                    class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
                                >
                                    @if($filteredAgents && $filteredAgents->count() > 0)
                                        @foreach($filteredAgents as $agent)
                                            <button 
                                                type="button"
                                                wire:click="selectAgent({{ $agent->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-600 {{ $agent_id == $agent->id ? 'bg-blue-100 dark:bg-blue-900' : '' }}"
                                            >
                                                <div class="flex flex-col">
                                                    <span class="font-medium">{{ $agent->name }}</span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $this->getAgentDisplayText($agent) }}
                                                    </span>
                                                </div>
                                            </button>
                                        @endforeach
                                    @else
                                        <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            沒有找到符合的代理
                                        </div>
                                    @endif
                                </div>
                                
                                @error('agent_id')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 選中代理的路徑顯示 --}}
                            @if(!empty($selectedAgentPath))
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">代理層級路徑</h4>
                                    <div class="flex items-center space-x-2 text-sm">
                                        @foreach($selectedAgentPath as $index => $pathAgent)
                                            @if($index > 0)
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            @endif
                                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100 rounded">
                                                {{ $pathAgent['name'] }} (第{{ $pathAgent['level'] }}層)
                                            </span>
                                        @endforeach
                                    </div>
                                    
                                    @if(end($selectedAgentPath))
                                        <p class="mt-2 text-xs text-blue-700 dark:text-blue-300">
                                            剩餘點數：{{ number_format(end($selectedAgentPath)['remaining_points'], 2) }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- 點數設定區塊 --}}
                    @if(!$isEdit)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">點數設定</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="initial_points" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        初始點數 <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        id="initial_points"
                                        wire:model.blur="initial_points"
                                        step="0.01"
                                        min="0"
                                        max="999999999.99"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('initial_points') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                        placeholder="請輸入初始點數"
                                    />
                                    @error('initial_points')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        將從隸屬代理的剩餘點數中扣除
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 編輯模式：當前點數和點數管理 --}}
                    @if($isEdit && $player)
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">點數管理</h3>
                                <button 
                                    type="button"
                                    wire:click="togglePointsManagement"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                                >
                                    {{ $showPointsManagement ? '隱藏' : '顯示' }}點數操作
                                </button>
                            </div>
                            
                            {{-- 當前點數顯示 --}}
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ number_format($player->points, 2) }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">當前點數</div>
                                    </div>
                                    
                                    @php $stats = $this->getPlayerStatistics() @endphp
                                    @if(!empty($stats))
                                        <div class="text-center">
                                            <div class="text-lg font-semibold text-green-600 dark:text-green-400">
                                                {{ number_format($stats['total_received'] ?? 0, 2) }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">累計獲得</div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <div class="text-lg font-semibold text-red-600 dark:text-red-400">
                                                {{ number_format($stats['total_spent'] ?? 0, 2) }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">累計消費</div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- 點數操作面板 --}}
                            @if($showPointsManagement)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {{-- 增加點數 --}}
                                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-green-900 dark:text-green-100 mb-3">增加點數</h4>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <input 
                                                        type="number" 
                                                        wire:model.blur="pointsToAdd"
                                                        step="0.01"
                                                        min="0"
                                                        max="999999999.99"
                                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('pointsToAdd') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                                        placeholder="輸入要增加的點數"
                                                    />
                                                    @error('pointsToAdd')
                                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                
                                                <div>
                                                    <input 
                                                        type="text" 
                                                        wire:model.blur="pointsDescription"
                                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                        placeholder="操作說明（選填）"
                                                    />
                                                </div>
                                                
                                                <button 
                                                    type="button"
                                                    wire:click="addPoints"
                                                    :disabled="!$wire.pointsToAdd || $wire.pointsToAdd <= 0"
                                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    增加點數
                                                </button>
                                            </div>
                                        </div>

                                        {{-- 扣除點數 --}}
                                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-red-900 dark:text-red-100 mb-3">扣除點數</h4>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <input 
                                                        type="number" 
                                                        wire:model.blur="pointsToDeduct"
                                                        step="0.01"
                                                        min="0"
                                                        max="{{ $player->points }}"
                                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('pointsToDeduct') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                                        placeholder="輸入要扣除的點數"
                                                    />
                                                    @error('pointsToDeduct')
                                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                
                                                <div>
                                                    <input 
                                                        type="text" 
                                                        wire:model.blur="pointsDescription"
                                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                        placeholder="操作說明（選填）"
                                                    />
                                                </div>
                                                
                                                <button 
                                                    type="button"
                                                    wire:click="deductPoints"
                                                    :disabled="!$wire.pointsToDeduct || $wire.pointsToDeduct <= 0"
                                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    扣除點數
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- 其他設定區塊 --}}
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">其他設定</h3>
                        
                        <div class="space-y-4">
                            {{-- 狀態切換 --}}
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="is_active"
                                    wire:model="is_active"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                    啟用玩家帳號
                                </label>
                            </div>

                            {{-- 備註 --}}
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    備註
                                </label>
                                <textarea 
                                    id="notes"
                                    wire:model.blur="notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('notes') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入備註（選填）"
                                ></textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 表單按鈕 --}}
                <div class="mt-6 flex justify-end space-x-3">
                    <button 
                        type="button"
                        wire:click="cancel"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        取消
                    </button>
                    
                    <button 
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        @if($isEdit)
                            更新玩家
                        @else
                            建立玩家
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 編輯模式：玩家統計資訊 --}}
    @if($isEdit && $player)
        @php $stats = $this->getPlayerStatistics() @endphp
        @if(!empty($stats))
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">玩家統計資訊</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="text-sm font-medium text-blue-900 dark:text-blue-100">帳號天數</div>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $stats['account_age_days'] ?? 0 }}
                            </div>
                            <div class="text-xs text-blue-700 dark:text-blue-300">天</div>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <div class="text-sm font-medium text-green-900 dark:text-green-100">交易次數</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ $stats['total_transactions'] ?? 0 }}
                            </div>
                            <div class="text-xs text-green-700 dark:text-green-300">次</div>
                        </div>
                        
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                            <div class="text-sm font-medium text-purple-900 dark:text-purple-100">代理層級</div>
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                第{{ $stats['agent_level'] ?? 0 }}層
                            </div>
                            <div class="text-xs text-purple-700 dark:text-purple-300">{{ $stats['agent_path'] ?? '' }}</div>
                        </div>
                        
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                            <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">最後交易</div>
                            <div class="text-sm font-bold text-yellow-600 dark:text-yellow-400">
                                @if($stats['last_transaction_date'])
                                    {{ $stats['last_transaction_date']->format('Y-m-d') }}
                                @else
                                    無記錄
                                @endif
                            </div>
                            <div class="text-xs text-yellow-700 dark:text-yellow-300">
                                @if($stats['last_transaction_date'])
                                    {{ $stats['last_transaction_date']->format('H:i') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>