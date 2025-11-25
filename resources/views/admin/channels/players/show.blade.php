@extends('layouts.admin')

@section('title', '玩家詳情 - ' . $player->name)

@section('content')
    <div class="space-y-6">
        {{-- 頁面標題 --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    玩家詳情
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    檢視玩家「{{ $player->name }}」的詳細資訊
                </p>
            </div>
            
            <div class="flex space-x-3">
                @can('channels.players.edit')
                    <a 
                        href="{{ route('admin.channels.players.edit', $player) }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        編輯玩家
                    </a>
                @endcan
                
                <a 
                    href="{{ route('admin.channels.players.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    返回列表
                </a>
            </div>
        </div>

        {{-- 玩家基本資訊 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">基本資訊</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">玩家的基本資料和帳號資訊</p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">玩家姓名</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $player->name }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">完整帳號</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $player->account }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">使用者名稱</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $player->username }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">電子郵件</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $player->email }}</dd>
                    </div>
                    
                    @if($player->phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">電話號碼</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $player->phone }}</dd>
                        </div>
                    @endif
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">狀態</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $player->is_active 
                                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' 
                                : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' 
                            }}">
                                <span class="w-2 h-2 mr-1.5 rounded-full {{ $player->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ $player->is_active ? '啟用' : '停用' }}
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">建立時間</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $player->created_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                    
                    @if($player->creator)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">建立者</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $player->creator->name }}</dd>
                        </div>
                    @endif
                    
                    @if($player->notes)
                        <div class="md:col-span-2 lg:col-span-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">備註</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $player->notes }}</dd>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 隸屬代理資訊 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">隸屬代理</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">玩家所隸屬的代理資訊和層級結構</p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                @if($player->agent)
                    <div class="space-y-4">
                        {{-- 直屬代理 --}}
                        <div class="flex items-center space-x-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">直屬代理</h4>
                                <p class="text-lg font-semibold text-blue-800 dark:text-blue-200">{{ $player->agent->name }}</p>
                                <p class="text-sm text-blue-700 dark:text-blue-300">{{ $player->agent->account }} (第{{ $player->agent->level }}層)</p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-blue-700 dark:text-blue-300">剩餘點數</div>
                                <div class="text-lg font-semibold text-blue-800 dark:text-blue-200">{{ number_format($player->agent->remaining_points, 2) }}</div>
                            </div>
                        </div>

                        {{-- 代理層級路徑 --}}
                        @if($player->agent_path->count() > 1)
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">代理層級路徑</h4>
                                <div class="flex items-center space-x-2 text-sm">
                                    @foreach($player->agent_path as $index => $pathAgent)
                                        @if($index > 0)
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        @endif
                                        <span class="px-2 py-1 {{ $pathAgent->id === $player->agent_id ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }} rounded">
                                            {{ $pathAgent->name }} (第{{ $pathAgent->level }}層)
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有隸屬代理</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">此玩家尚未指派隸屬代理</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- 點數資訊 --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">點數資訊</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">玩家的點數狀況和交易統計</p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($player->points, 2) }}
                        </div>
                        <div class="text-sm text-blue-700 dark:text-blue-300 mt-1">當前點數</div>
                    </div>
                    
                    <div class="text-center p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ $player->pointTransactions()->where('amount', '>', 0)->count() }}
                        </div>
                        <div class="text-sm text-green-700 dark:text-green-300 mt-1">獲得次數</div>
                    </div>
                    
                    <div class="text-center p-6 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ $player->pointTransactions()->where('amount', '<', 0)->count() }}
                        </div>
                        <div class="text-sm text-red-700 dark:text-red-300 mt-1">消費次數</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 最近交易記錄 --}}
        @if($player->pointTransactions()->exists())
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">最近交易記錄</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">最近 10 筆點數交易記錄</p>
                </div>
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">時間</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">類型</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">金額</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">餘額</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">說明</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($player->pointTransactions()->latest()->limit(10)->get() as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $transaction->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $transaction->amount > 0 
                                                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' 
                                                : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' 
                                            }}">
                                            {{ $transaction->amount > 0 ? '獲得' : '消費' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium
                                        {{ $transaction->amount > 0 
                                            ? 'text-green-600 dark:text-green-400' 
                                            : 'text-red-600 dark:text-red-400' 
                                        }}">
                                        {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ number_format($transaction->balance_after, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $transaction->description ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection