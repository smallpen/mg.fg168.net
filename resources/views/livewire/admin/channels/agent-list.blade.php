<div class="space-y-6" x-data="resetButtonController()" x-init="init()">
    {{-- 麵包屑導航 --}}
    @if(count($breadcrumbs) > 1 || $currentAgent)
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-3">
                <nav class="flex" aria-label="麵包屑">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        @foreach($breadcrumbs as $index => $breadcrumb)
                            <li class="inline-flex items-center">
                                @if($index > 0)
                                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                                
                                @if($breadcrumb['active'])
                                    <span class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ml-2">
                                        {{ $breadcrumb['name'] }}
                                    </span>
                                @else
                                    <button 
                                        wire:click="{{ $breadcrumb['action'] }}"
                                        class="ml-1 text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 md:ml-2"
                                    >
                                        {{ $breadcrumb['name'] }}
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
                
                {{-- 返回按鈕 --}}
                @if($currentAgent)
                    <div class="mt-3 flex space-x-2">
                        @if($currentAgent->parent)
                            <button 
                                wire:click="goBack"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                返回上層
                            </button>
                        @endif
                        
                        <button 
                            wire:click="goToRoot"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                            </svg>
                            返回根層級
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- 當前檢視範圍標題 --}}
    @if($currentAgent || $viewMode === 'players')
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($viewMode === 'players')
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            @if($viewMode === 'players')
                                {{ $currentAgent->name }} 的直屬玩家
                            @else
                                {{ $currentAgent->name }} 的下層代理
                            @endif
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if($viewMode === 'players')
                                檢視 {{ $currentAgent->name }} 代理的所有直屬玩家資料
                            @else
                                檢視 {{ $currentAgent->name }} 代理的所有下層代理資料
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 統計資訊卡片 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            @if($statistics['is_player_view'] ?? false)
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                @if($statistics['is_player_view'] ?? false)
                                    總玩家數
                                @elseif($currentAgent)
                                    下層代理數
                                @else
                                    第一層代理數
                                @endif
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($statistics['total_agents']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                @if($statistics['is_player_view'] ?? false)
                                    啟用玩家
                                @else
                                    啟用代理
                                @endif
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($statistics['active_agents']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                總點數
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($statistics['total_points'], 2) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                剩餘點數
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ number_format($statistics['remaining_points'], 2) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 搜尋和篩選區域 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                {{-- 搜尋框 --}}
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        搜尋代理
                    </label>
                    <input 
                        type="text" 
                        id="search"
                        wire:model.live="search"
                        placeholder="姓名、帳號、電子郵件..."
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                    />
                </div>

                {{-- 層級篩選 --}}
                <div>
                    <label for="levelFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        層級
                    </label>
                    <select 
                        id="levelFilter"
                        wire:model.live="levelFilter"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                    >
                        <option value="all">全部層級</option>
                        @foreach($levelOptions as $level)
                            <option value="{{ $level }}">第 {{ $level }} 層</option>
                        @endforeach
                    </select>
                </div>

                {{-- 狀態篩選 --}}
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        狀態
                    </label>
                    <select 
                        id="statusFilter"
                        wire:model.live="statusFilter"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                    >
                        <option value="all">全部狀態</option>
                        <option value="active">啟用</option>
                        <option value="inactive">停用</option>
                    </select>
                </div>

                {{-- 前置符號篩選 --}}
                <div>
                    <label for="prefixFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        前置符號
                    </label>
                    <select 
                        id="prefixFilter"
                        wire:model.live="prefixFilter"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                    >
                        <option value="all">全部符號</option>
                        @foreach($prefixOptions as $prefix)
                            <option value="{{ $prefix }}">{{ strtoupper($prefix) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 點數範圍篩選 --}}
                <div>
                    <label for="pointsRangeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        點數範圍
                    </label>
                    <select 
                        id="pointsRangeFilter"
                        wire:model.live="pointsRangeFilter"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                    >
                        <option value="all">全部範圍</option>
                        <option value="zero">零點數</option>
                        <option value="low">低於 1,000</option>
                        <option value="medium">1,000 - 10,000</option>
                        <option value="high">高於 10,000</option>
                    </select>
                </div>
            </div>

            {{-- 操作按鈕 --}}
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-3">
                    {{-- 建立代理按鈕 --}}
                    @can('channels.agents.create')
                        @if($viewMode === 'agents')
                            <a href="{{ route('admin.channels.agents.create', $currentAgent ? ['parent_id' => $currentAgent->id] : []) }}" 
                               class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                @if($currentAgent)
                                    建立下層代理
                                @else
                                    建立第一層代理
                                @endif
                            </a>
                        @endif
                    @endcan

                    {{-- 重置按鈕 --}}
                    @if($search || $levelFilter !== 'all' || $statusFilter !== 'all' || $prefixFilter !== 'all' || $pointsRangeFilter !== 'all')
                        <button 
                            wire:click="resetFilters"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            重置篩選
                        </button>
                    @endif

                    {{-- 匯出按鈕 --}}
                    @can('channels.agents.export')
                        <button 
                            wire:click="exportAgents"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            匯出資料
                        </button>
                    @endcan
                </div>


            </div>
        </div>
    </div>

    {{-- 代理/玩家列表 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        @if($viewMode === 'agents' && $agents->count() > 0)
            {{-- 代理列表 --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                代理資訊
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                層級/前置符號
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                點數資訊
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                下層統計
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                狀態
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                建立時間
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">操作</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($agents as $agent)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                {{-- 代理資訊 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                                    {{ strtoupper(substr($agent->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <button 
                                                    wire:click="viewAgentChildren({{ $agent->id }})"
                                                    class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200"
                                                    title="檢視下層代理"
                                                >
                                                    {{ $agent->name }}
                                                </button>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $agent->account }}
                                            </div>
                                            @if($agent->email)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                                    {{ $agent->email }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 層級/前置符號 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            第 {{ $agent->level }} 層
                                        </span>
                                        @if($agent->prefix)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                {{ strtoupper($agent->prefix) }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($agent->parent)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            上層：{{ $agent->parent->name }}
                                        </div>
                                    @endif
                                </td>

                                {{-- 點數資訊 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">總點數</span>
                                            <span class="font-medium">{{ number_format($agent->total_points, 2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">已分配</span>
                                            <span class="text-orange-600 dark:text-orange-400">{{ number_format($agent->allocated_points, 2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">剩餘</span>
                                            <span class="text-green-600 dark:text-green-400 font-medium">{{ number_format($agent->remaining_points, 2) }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 下層統計 --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div class="flex items-center space-x-4">
                                        @if($agent->children_count > 0)
                                            <button 
                                                wire:click="viewAgentChildren({{ $agent->id }})"
                                                class="flex items-center hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200"
                                                title="檢視下層代理"
                                            >
                                                <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                                <span>{{ $agent->children_count }}</span>
                                            </button>
                                        @else
                                            <div class="flex items-center text-gray-400">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                                <span>0</span>
                                            </div>
                                        @endif
                                        
                                        @if($agent->players_count > 0)
                                            <button 
                                                wire:click="viewAgentPlayers({{ $agent->id }})"
                                                class="flex items-center hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200"
                                                title="檢視直屬玩家"
                                            >
                                                <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span>{{ $agent->players_count }}</span>
                                            </button>
                                        @else
                                            <div class="flex items-center text-gray-400">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span>0</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- 狀態 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        wire:click="toggleAgentStatus({{ $agent->id }})"
                                        @can('channels.agents.edit')
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors duration-200 {{ $agent->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-200' }}"
                                        @else
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $agent->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}"
                                        @endcan
                                    >
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full {{ $agent->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                        {{ $agent->is_active ? '啟用' : '停用' }}
                                    </button>
                                </td>

                                {{-- 建立時間 --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ $agent->created_at->format('Y/m/d') }}</div>
                                    <div class="text-xs">{{ $agent->created_at->format('H:i') }}</div>
                                </td>

                                {{-- 操作 --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        @can('channels.agents.view')
                                            <a href="{{ route('admin.channels.agents.show', $agent) }}" 
                                               class="p-1 text-gray-400 hover:text-blue-600 transition-colors duration-200"
                                               title="檢視代理">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                        
                                        @can('channels.agents.edit')
                                            <a href="{{ route('admin.channels.agents.edit', $agent) }}" 
                                               class="p-1 text-gray-400 hover:text-green-600 transition-colors duration-200"
                                               title="編輯代理">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                        
                                        @can('channels.points.allocate')
                                            <a href="{{ route('admin.channels.agents.points', $agent) }}" 
                                               class="p-1 text-gray-400 hover:text-yellow-600 transition-colors duration-200"
                                               title="點數管理">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                </svg>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($viewMode === 'players' && $players->count() > 0)
            {{-- 玩家列表 --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                玩家資訊
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                隸屬代理
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                點數資訊
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                狀態
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                建立時間
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">操作</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($players as $player)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                {{-- 玩家資訊 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                                <span class="text-sm font-medium text-green-600 dark:text-green-400">
                                                    {{ strtoupper(substr($player->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $player->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $player->account }}
                                            </div>
                                            @if($player->email)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                                    {{ $player->email }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 隸屬代理 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $player->agent->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $player->agent->account }}
                                    </div>
                                </td>

                                {{-- 點數資訊 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">總點數</span>
                                            <span class="font-medium">{{ number_format($player->total_points ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">可用</span>
                                            <span class="text-green-600 dark:text-green-400 font-medium">{{ number_format($player->available_points ?? 0, 2) }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 狀態 --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $player->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full {{ $player->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                        {{ $player->is_active ? '啟用' : '停用' }}
                                    </span>
                                </td>

                                {{-- 建立時間 --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ $player->created_at->format('Y/m/d') }}</div>
                                    <div class="text-xs">{{ $player->created_at->format('H:i') }}</div>
                                </td>

                                {{-- 操作 --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        @can('channels.players.view')
                                            <a href="{{ route('admin.channels.players.show', $player) }}" 
                                               class="p-1 text-gray-400 hover:text-blue-600 transition-colors duration-200"
                                               title="檢視玩家">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                        
                                        @can('channels.players.edit')
                                            <a href="{{ route('admin.channels.players.edit', $player) }}" 
                                               class="p-1 text-gray-400 hover:text-green-600 transition-colors duration-200"
                                               title="編輯玩家">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            {{-- 空狀態 --}}
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    @if($viewMode === 'players')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    @endif
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                    @if($viewMode === 'players')
                        沒有找到玩家
                    @else
                        沒有找到代理
                    @endif
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($viewMode === 'players')
                        @if($currentAgent)
                            {{ $currentAgent->name }} 目前沒有直屬玩家
                        @else
                            目前沒有玩家資料
                        @endif
                    @else
                        @if($currentAgent)
                            {{ $currentAgent->name }} 目前沒有下層代理
                        @else
                            目前沒有第一層代理
                        @endif
                    @endif
                </p>
                @if($viewMode === 'agents')
                    @can('channels.agents.create')
                        <div class="mt-6">
                            <a href="{{ route('admin.channels.agents.create', $currentAgent ? ['parent_id' => $currentAgent->id] : []) }}" 
                               class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                @if($currentAgent)
                                    建立下層代理
                                @else
                                    建立第一層代理
                                @endif
                            </a>
                        </div>
                    @endcan
                @endif
            </div>
        @endif

        {{-- 分頁導航 --}}
        @php
            $paginatedData = $viewMode === 'players' ? $players : $agents;
        @endphp
        @if($paginatedData->hasPages())
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    @if ($paginatedData->onFirstPage())
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                            上一頁
                        </span>
                    @else
                        <button wire:click="previousPage" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:text-gray-400">
                            上一頁
                        </button>
                    @endif

                    @if ($paginatedData->hasMorePages())
                        <button wire:click="nextPage" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:text-gray-400">
                            下一頁
                        </button>
                    @else
                        <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                            下一頁
                        </span>
                    @endif
                </div>

                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div class="flex flex-col space-y-2">
                        <p class="text-sm text-gray-700 leading-5 dark:text-gray-400">
                            顯示第
                            <span class="font-medium">{{ $paginatedData->firstItem() ?? 0 }}</span>
                            到
                            <span class="font-medium">{{ $paginatedData->lastItem() ?? 0 }}</span>
                            筆，共
                            <span class="font-medium">{{ $paginatedData->total() }}</span>
                            筆結果
                        </p>
                        
                        {{-- 每頁顯示筆數選擇器 --}}
                        <div class="flex items-center space-x-3">
                            <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                每頁顯示：
                            </label>
                            <select 
                                id="perPage"
                                wire:model.live="perPage"
                                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent min-w-[80px]"
                            >
                                @foreach($perPageOptions as $option)
                                    <option value="{{ $option }}">{{ $option }} 筆</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <span class="relative z-0 inline-flex shadow-sm rounded-md">
                            {{-- 上一頁按鈕 --}}
                            @if ($paginatedData->onFirstPage())
                                <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            @else
                                <button wire:click="previousPage" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif

                            {{-- 頁碼按鈕 --}}
                            @for ($page = 1; $page <= $paginatedData->lastPage(); $page++)
                                @if ($page == $paginatedData->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-primary-600 border border-primary-600 cursor-default leading-5">{{ $page }}</span>
                                    </span>
                                @else
                                    <button wire:click="gotoPage({{ $page }})" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:text-gray-400">
                                        {{ $page }}
                                    </button>
                                @endif
                            @endfor

                            {{-- 下一頁按鈕 --}}
                            @if ($paginatedData->hasMorePages())
                                <button wire:click="nextPage" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @else
                                <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md leading-5 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        @else
            {{-- 沒有分頁時的每頁顯示筆數選擇器 --}}
            @if(($viewMode === 'agents' && $agents->count() > 0) || ($viewMode === 'players' && $players->count() > 0))
                <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-700 dark:text-gray-400">
                                共 {{ $viewMode === 'players' ? $players->count() : $agents->count() }} 筆結果
                            </span>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                每頁顯示：
                            </label>
                            <select 
                                id="perPage"
                                wire:model.live="perPage"
                                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent min-w-[80px]"
                            >
                                @foreach($perPageOptions as $option)
                                    <option value="{{ $option }}">{{ $option }} 筆</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<script>
function resetButtonController() {
    return {
        showResetButton: @js(!empty($search) || $levelFilter !== 'all' || $statusFilter !== 'all' || $prefixFilter !== 'all' || $pointsRangeFilter !== 'all'),
        
        init() {
            console.log('🔧 代理列表重置按鈕控制器初始化');
            
            // 監聽重置表單元素事件
            Livewire.on('reset-form-elements', () => {
                console.log('🔄 收到重置表單元素事件');
                this.resetFormElements();
            });
            
            this.checkFilters();
            
            // 監聽輸入變化
            document.addEventListener('input', () => {
                setTimeout(() => this.checkFilters(), 100);
            });
            
            document.addEventListener('change', () => {
                setTimeout(() => this.checkFilters(), 100);
            });
        },
        
        checkFilters() {
            const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
            const levelSelect = document.querySelector('select[wire\\:model\\.live="levelFilter"]');
            const statusSelect = document.querySelector('select[wire\\:model\\.live="statusFilter"]');
            const prefixSelect = document.querySelector('select[wire\\:model\\.live="prefixFilter"]');
            const pointsSelect = document.querySelector('select[wire\\:model\\.live="pointsRangeFilter"]');
            
            const hasSearch = searchInput && searchInput.value.trim() !== '';
            const hasLevelFilter = levelSelect && levelSelect.value !== 'all';
            const hasStatusFilter = statusSelect && statusSelect.value !== 'all';
            const hasPrefixFilter = prefixSelect && prefixSelect.value !== 'all';
            const hasPointsFilter = pointsSelect && pointsSelect.value !== 'all';
            
            this.showResetButton = hasSearch || hasLevelFilter || hasStatusFilter || hasPrefixFilter || hasPointsFilter;
            
            console.log('🔍 檢查代理列表篩選狀態:', {
                hasSearch,
                hasLevelFilter,
                hasStatusFilter,
                hasPrefixFilter,
                hasPointsFilter,
                showResetButton: this.showResetButton
            });
        },
        
        resetFormElements() {
            console.log('🔄 開始重置代理列表表單元素');
            
            // 重置搜尋框
            const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                searchInput.blur();
            }
            
            // 重置所有篩選下拉選單
            const selects = document.querySelectorAll('select[wire\\:model\\.live*="Filter"]');
            selects.forEach(select => {
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            // 更新重置按鈕狀態
            setTimeout(() => {
                this.checkFilters();
                console.log('✅ 代理列表表單元素重置完成');
            }, 100);
        }
    }
}
</script>