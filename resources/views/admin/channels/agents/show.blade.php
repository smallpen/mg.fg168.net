@extends('layouts.admin')

@section('title', '代理詳情')

@section('content')
    <div class="space-y-6">
        {{-- 頁面標題 --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    代理詳情
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    檢視 {{ $agent->name }} 的詳細資訊和統計數據
                </p>
            </div>
            
            <div class="flex space-x-3">
                @can('channels.agents.edit')
                    <a href="{{ route('admin.channels.agents.edit', $agent) }}" 
                       class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        編輯代理
                    </a>
                @endcan
                
                <a href="{{ route('admin.channels.agents.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    返回列表
                </a>
            </div>
        </div>

        {{-- 代理基本資訊 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-16 w-16">
                        <div class="h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <span class="text-xl font-medium text-primary-600 dark:text-primary-400">
                                {{ strtoupper(substr($agent->name, 0, 2)) }}
                            </span>
                        </div>
                    </div>
                    <div class="ml-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $agent->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $agent->account }}</p>
                        <div class="mt-2 flex items-center space-x-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                第 {{ $agent->level }} 層代理
                            </span>
                            @if($agent->prefix)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    前置符號：{{ strtoupper($agent->prefix) }}
                                </span>
                            @endif
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $agent->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full {{ $agent->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ $agent->is_active ? '啟用' : '停用' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">使用者名稱</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $agent->username }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">電子郵件</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $agent->email ?: '未設定' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">電話號碼</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $agent->phone ?: '未設定' }}</dd>
                    </div>
                    
                    @if($agent->parent)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">上層代理</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                <a href="{{ route('admin.channels.agents.show', $agent->parent) }}" 
                                   class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ $agent->parent->name }} ({{ $agent->parent->account }})
                                </a>
                            </dd>
                        </div>
                    @endif
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">建立時間</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $agent->created_at->format('Y年m月d日 H:i') }}</dd>
                    </div>
                    
                    @if($agent->creator)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">建立者</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $agent->creator->name }}</dd>
                        </div>
                    @endif
                </dl>
                
                @if($agent->notes)
                    <div class="mt-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">備註</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                            {{ $agent->notes }}
                        </dd>
                    </div>
                @endif
            </div>
        </div>

        {{-- 點數資訊 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">點數資訊</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">代理的點數分配和使用情況</p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-blue-600 dark:text-blue-400">總點數</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($agent->total_points, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-orange-600 dark:text-orange-400">已分配點數</p>
                                <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ number_format($agent->allocated_points, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-green-600 dark:text-green-400">剩餘點數</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ number_format($agent->remaining_points, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                @can('channels.points.allocate')
                    <div class="mt-6">
                        <a href="{{ route('admin.channels.agents.points', $agent) }}" 
                           class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            管理點數
                        </a>
                    </div>
                @endcan
            </div>
        </div>

        {{-- 下層統計 --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- 下層代理 --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">下層代理</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">直屬下層代理列表</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $agent->children->count() }} 個代理
                        </span>
                    </div>
                </div>
                
                <div class="px-4 py-5 sm:p-6">
                    @if($agent->children->count() > 0)
                        <div class="space-y-3">
                            @foreach($agent->children->take(5) as $child)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                                                    {{ strtoupper(substr($child->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $child->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $child->account }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $child->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            {{ $child->is_active ? '啟用' : '停用' }}
                                        </span>
                                        <a href="{{ route('admin.channels.agents.show', $child) }}" 
                                           class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                            
                            @if($agent->children->count() > 5)
                                <div class="text-center">
                                    <a href="{{ route('admin.channels.agents.index', ['currentAgentId' => $agent->id]) }}" 
                                       class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-sm font-medium">
                                        查看全部 {{ $agent->children->count() }} 個下層代理
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有下層代理</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">此代理目前沒有直屬的下層代理</p>
                            @can('channels.agents.create')
                                <div class="mt-6">
                                    <a href="{{ route('admin.channels.agents.create', ['parent_id' => $agent->id]) }}" 
                                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        建立下層代理
                                    </a>
                                </div>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>

            {{-- 直屬玩家 --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">直屬玩家</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">隸屬於此代理的玩家</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ $agent->players->count() }} 個玩家
                        </span>
                    </div>
                </div>
                
                <div class="px-4 py-5 sm:p-6">
                    @if($agent->players->count() > 0)
                        <div class="space-y-3">
                            @foreach($agent->players->take(5) as $player)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                                <span class="text-xs font-medium text-green-600 dark:text-green-400">
                                                    {{ strtoupper(substr($player->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $player->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $player->account }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $player->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            {{ $player->is_active ? '啟用' : '停用' }}
                                        </span>
                                        @can('channels.players.view')
                                            <a href="{{ route('admin.channels.players.show', $player) }}" 
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                            
                            @if($agent->players->count() > 5)
                                <div class="text-center">
                                    <a href="{{ route('admin.channels.agents.index', ['currentAgentId' => $agent->id, 'viewMode' => 'players']) }}" 
                                       class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-sm font-medium">
                                        查看全部 {{ $agent->players->count() }} 個玩家
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有直屬玩家</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">此代理目前沒有直屬的玩家</p>
                            @can('channels.players.create')
                                <div class="mt-6">
                                    <a href="{{ route('admin.channels.players.create', ['agent_id' => $agent->id]) }}" 
                                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        建立玩家
                                    </a>
                                </div>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection