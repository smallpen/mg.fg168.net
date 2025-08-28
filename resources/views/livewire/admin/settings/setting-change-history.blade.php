<div class="px-4 py-5 sm:p-6 space-y-6">
    {{-- 頁面標題和操作按鈕 --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">設定變更歷史</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                檢視和管理系統設定的變更記錄
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <button 
                wire:click="openNotificationSettings"
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <x-heroicon-o-bell class="w-4 h-4 mr-2" />
                通知設定
            </button>
            <button 
                wire:click="exportChanges"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                匯出記錄
            </button>
        </div>
    </div>

    {{-- 統計資訊 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-document-text class="h-6 w-6 text-gray-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                總變更數
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($this->stats['total_changes']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                重要變更
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($this->stats['important_changes']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-cog-6-tooth class="h-6 w-6 text-blue-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                涉及設定
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($this->stats['unique_settings']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-users class="h-6 w-6 text-green-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                操作人員
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($this->stats['unique_users']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-funnel class="h-6 w-6 text-purple-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                篩選結果
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($this->stats['filtered_count']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 篩選和搜尋 --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                {{-- 搜尋框 --}}
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        搜尋
                    </label>
                    <div class="mt-1 relative">
                        <input 
                            type="text" 
                            id="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="搜尋設定鍵值、原因或使用者..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                        </div>
                    </div>
                </div>

                {{-- 分類篩選 --}}
                <div>
                    <label for="categoryFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        分類
                    </label>
                    <select 
                        id="categoryFilter"
                        wire:model.live="categoryFilter"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="all">所有分類</option>
                        @foreach($this->categories as $key => $category)
                            <option value="{{ $key }}">{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 使用者篩選 --}}
                <div>
                    <label for="userFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        操作人員
                    </label>
                    <select 
                        id="userFilter"
                        wire:model.live="userFilter"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="all">所有人員</option>
                        @foreach($this->users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->username }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- 重要變更篩選 --}}
                <div class="flex items-center">
                    <input 
                        id="importantOnly"
                        type="checkbox" 
                        wire:model.live="importantOnly"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                    >
                    <label for="importantOnly" class="ml-2 block text-sm text-gray-900 dark:text-white">
                        僅顯示重要變更
                    </label>
                </div>
            </div>

            {{-- 日期範圍篩選 --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        開始日期
                    </label>
                    <input 
                        type="date" 
                        id="dateFrom"
                        wire:model.live="dateFrom"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                </div>
                <div>
                    <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        結束日期
                    </label>
                    <input 
                        type="date" 
                        id="dateTo"
                        wire:model.live="dateTo"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                </div>
                <div class="flex items-end">
                    <div class="flex space-x-2">
                        <button 
                            wire:click="setDateRange('today')"
                            class="px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md"
                        >
                            今天
                        </button>
                        <button 
                            wire:click="setDateRange('week')"
                            class="px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md"
                        >
                            本週
                        </button>
                        <button 
                            wire:click="setDateRange('month')"
                            class="px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md"
                        >
                            本月
                        </button>
                    </div>
                </div>
            </div>

            {{-- 操作按鈕 --}}
            <div class="flex justify-between items-center">
                <button 
                    wire:click="clearFilters"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <x-heroicon-o-x-mark class="w-4 h-4 mr-2" />
                    清除篩選
                </button>

                <div class="flex items-center space-x-2">
                    <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300">每頁顯示：</label>
                    <select 
                        id="perPage"
                        wire:model.live="perPage"
                        class="text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- 變更記錄列表 --}}
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                變更記錄
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                共 {{ number_format($this->changes->total()) }} 筆記錄
            </p>
        </div>

        @if($this->changes->count() > 0)
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->changes as $change)
                    <li class="px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center min-w-0 flex-1">
                                {{-- 變更類型圖示 --}}
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                        @if($this->getChangeTypeText($change) === '新增') bg-green-100 dark:bg-green-900
                                        @elseif($this->getChangeTypeText($change) === '刪除') bg-red-100 dark:bg-red-900
                                        @elseif($this->getChangeTypeText($change) === '回復') bg-blue-100 dark:bg-blue-900
                                        @else bg-yellow-100 dark:bg-yellow-900
                                        @endif
                                    ">
                                        <x-dynamic-component 
                                            :component="'heroicon-o-' . $this->getChangeTypeIcon($change)"
                                            class="w-4 h-4
                                                @if($this->getChangeTypeText($change) === '新增') text-green-600 dark:text-green-400
                                                @elseif($this->getChangeTypeText($change) === '刪除') text-red-600 dark:text-red-400
                                                @elseif($this->getChangeTypeText($change) === '回復') text-blue-600 dark:text-blue-400
                                                @else text-yellow-600 dark:text-yellow-400
                                                @endif
                                            "
                                        />
                                    </div>
                                </div>

                                {{-- 變更資訊 --}}
                                <div class="ml-4 min-w-0 flex-1">
                                    <div class="flex items-center">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $change->setting_key }}
                                        </p>
                                        
                                        {{-- 重要性標籤 --}}
                                        @if($change->is_important_change)
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                重要
                                            </span>
                                        @endif

                                        {{-- 變更類型標籤 --}}
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($this->getChangeTypeText($change) === '新增') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                            @elseif($this->getChangeTypeText($change) === '刪除') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                            @elseif($this->getChangeTypeText($change) === '回復') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                            @else bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                            @endif
                                        ">
                                            {{ $this->getChangeTypeText($change) }}
                                        </span>
                                    </div>

                                    <div class="mt-1">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">舊值：</span>
                                            <span class="font-mono">{{ $this->formatDisplayValue($change->old_value) }}</span>
                                            <span class="mx-2">→</span>
                                            <span class="font-medium">新值：</span>
                                            <span class="font-mono">{{ $this->formatDisplayValue($change->new_value) }}</span>
                                        </p>
                                    </div>

                                    <div class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-user class="flex-shrink-0 mr-1.5 h-4 w-4" />
                                        <span>{{ $change->user?->name ?? '未知使用者' }}</span>
                                        <span class="mx-2">•</span>
                                        <x-heroicon-o-clock class="flex-shrink-0 mr-1.5 h-4 w-4" />
                                        <span>{{ $change->created_at->format('Y-m-d H:i:s') }}</span>
                                        @if($change->ip_address)
                                            <span class="mx-2">•</span>
                                            <x-heroicon-o-globe-alt class="flex-shrink-0 mr-1.5 h-4 w-4" />
                                            <span>{{ $change->ip_address }}</span>
                                        @endif
                                    </div>

                                    @if($change->reason)
                                        <div class="mt-1">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                <span class="font-medium">原因：</span>{{ $change->reason }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- 操作按鈕 --}}
                            <div class="flex items-center space-x-2 ml-4">
                                <button 
                                    wire:click="showDetails({{ $change->id }})"
                                    class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    title="檢視詳情"
                                >
                                    <x-heroicon-o-eye class="h-4 w-4" />
                                </button>

                                @if(!str_contains($change->reason ?? '', '回復'))
                                    <button 
                                        wire:click="confirmRestore({{ $change->id }})"
                                        class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        title="回復到此版本"
                                    >
                                        <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- 分頁 --}}
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->changes->links() }}
            </div>
        @else
            <div class="px-4 py-12 text-center">
                <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有變更記錄</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    在指定的篩選條件下找不到任何變更記錄。
                </p>
            </div>
        @endif
    </div>

    {{-- 變更詳情對話框 --}}
    @if($showDetailsModal && $selectedChange)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetailsModal"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    變更詳情
                                </h3>
                                
                                <div class="mt-4 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">設定鍵值</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $selectedChange->setting_key }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">分類</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedChange->setting?->category ?? '未知' }}</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">設定描述</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedChange->setting?->description ?? '無描述' }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">舊值</label>
                                            <div class="mt-1 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                                                <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap">{{ json_encode($selectedChange->old_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">新值</label>
                                            <div class="mt-1 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                                                <pre class="text-sm text-green-800 dark:text-green-200 whitespace-pre-wrap">{{ json_encode($selectedChange->new_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">操作人員</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedChange->user?->name ?? '未知使用者' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">變更時間</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedChange->created_at->format('Y-m-d H:i:s') }}</p>
                                        </div>
                                    </div>

                                    @if($selectedChange->ip_address)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">IP 位址</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $selectedChange->ip_address }}</p>
                                        </div>
                                    @endif

                                    @if($selectedChange->user_agent)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">使用者代理</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white break-all">{{ $selectedChange->user_agent }}</p>
                                        </div>
                                    @endif

                                    @if($selectedChange->reason)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">變更原因</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedChange->reason }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="closeDetailsModal"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 回復確認對話框 --}}
    @if($showRestoreModal && $selectedChange)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeRestoreModal"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    確認回復設定
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        您確定要將設定 <strong>{{ $selectedChange->setting_key }}</strong> 回復到以下值嗎？
                                    </p>
                                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                        <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ json_encode($selectedChange->old_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        此操作將會被記錄在變更歷史中。
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="executeRestore"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            確認回復
                        </button>
                        <button 
                            wire:click="closeRestoreModal"
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            取消
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 通知設定對話框 --}}
    @if($showNotificationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeNotificationModal"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    通知設定
                                </h3>
                                
                                <div class="mt-4 space-y-4">
                                    <div class="flex items-center">
                                        <input 
                                            id="emailEnabled"
                                            type="checkbox" 
                                            wire:model="notificationSettings.email_enabled"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                                        >
                                        <label for="emailEnabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                            啟用郵件通知
                                        </label>
                                    </div>

                                    <div class="flex items-center">
                                        <input 
                                            id="importantOnly"
                                            type="checkbox" 
                                            wire:model="notificationSettings.important_only"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                                        >
                                        <label for="importantOnly" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                            僅通知重要變更
                                        </label>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            通知分類
                                        </label>
                                        <div class="space-y-2">
                                            @foreach($this->categories as $key => $category)
                                                <div class="flex items-center">
                                                    <input 
                                                        id="category_{{ $key }}"
                                                        type="checkbox" 
                                                        wire:model="notificationSettings.categories"
                                                        value="{{ $key }}"
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                                                    >
                                                    <label for="category_{{ $key }}" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                                        {{ $category['name'] }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="saveNotificationSettings"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            儲存設定
                        </button>
                        <button 
                            wire:click="closeNotificationModal"
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            取消
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        // 監聽篩選清除事件
        Livewire.on('setting-change-history-filters-cleared', () => {
            console.log('🗑️ 設定變更歷史篩選已清除');
            
            // 重置所有篩選欄位的視覺狀態
            const filterInputs = document.querySelectorAll('input[wire\\:model], select[wire\\:model]');
            filterInputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else {
                    input.value = '';
                }
                
                // 添加視覺反饋
                input.style.backgroundColor = '#f0f9ff';
                setTimeout(() => {
                    input.style.backgroundColor = '';
                }, 1000);
            });
            
            // 顯示篩選狀態視覺指示器
            const filterIndicators = document.querySelectorAll('.inline-flex.items-center.px-2\\.5.py-0\\.5.rounded-full');
            filterIndicators.forEach(indicator => {
                indicator.style.opacity = '0.5';
                setTimeout(() => {
                    indicator.style.opacity = '1';
                }, 300);
            });
            
            showSuccessMessage('所有篩選條件已清除');
        });

        // 監聽設定更新事件
        Livewire.on('setting-updated', (event) => {
            console.log('⚙️ 設定已更新:', event.settingKey);
            showSuccessMessage(`設定 "${event.settingKey}" 已更新`);
        });

        // 監聽下載檔案事件
        Livewire.on('download-file', (event) => {
            console.log('📥 開始下載檔案:', event.filename);
            
            const blob = new Blob([event.content], { type: event.mimeType });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = event.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showSuccessMessage('檔案下載已開始');
        });
    });

    function showSuccessMessage(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg z-50';
        successDiv.innerHTML = `
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(successDiv);
        
        setTimeout(() => {
            successDiv.remove();
        }, 3000);
    }
</script>
@endpush