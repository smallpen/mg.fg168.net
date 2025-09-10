<div class="org-node">
    <!-- 代理節點 -->
    <div class="org-node-content {{ $isRoot ? 'root' : 'agent' }}">
        <div class="font-semibold text-sm">{{ $node['name'] }}</div>
        <div class="text-xs text-gray-600 dark:text-gray-300">{{ $node['account'] }}</div>
        <div class="text-xs mt-1">
            <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">
                第 {{ $node['level'] }} 層
            </span>
        </div>
        <div class="text-xs mt-2 space-y-1">
            <div>總點數: {{ number_format($node['total_points'], 2) }}</div>
            <div>剩餘: {{ number_format($node['remaining_points'], 2) }}</div>
        </div>
        <div class="text-xs mt-1 text-gray-500 dark:text-gray-400">
            代理: {{ $node['children_count'] }} | 玩家: {{ $node['players_count'] }}
        </div>
    </div>

    <!-- 直屬玩家 -->
    @if(count($node['players']) > 0)
        <div class="org-players">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium">直屬玩家:</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach($node['players'] as $player)
                    <div class="org-node-content player">
                        <div class="font-medium text-xs">{{ $player['name'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">{{ $player['account'] }}</div>
                        <div class="text-xs mt-1">
                            點數: {{ number_format($player['points'], 2) }}
                        </div>
                        <div class="text-xs mt-1">
                            <span class="inline-block {{ $player['is_active'] ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }} px-2 py-1 rounded">
                                {{ $player['is_active'] ? '啟用' : '停用' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 下層代理 -->
    @if(count($node['children']) > 0)
        <div class="org-children">
            @foreach($node['children'] as $child)
                @include('livewire.agent.dashboard.partials.organization-node', ['node' => $child, 'isRoot' => false])
            @endforeach
        </div>
    @endif
</div>