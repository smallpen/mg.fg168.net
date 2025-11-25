<div class="space-y-6">
    <!-- 統計摘要 -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                總代理數
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $this->statsSummary['total_agents'] }}
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
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                直屬代理
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $this->statsSummary['direct_children'] }}
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
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                總玩家數
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $this->statsSummary['total_players'] }}
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
                        <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                直屬玩家
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $this->statsSummary['direct_players'] }}
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
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                最大層級
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                第 {{ $this->statsSummary['max_level'] }} 層
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 組織架構樹 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                組織架構圖
            </h3>
            
            <div class="organization-tree">
                @php
                    $organizationData = $this->organizationData;
                @endphp
                
                @include('livewire.agent.dashboard.partials.organization-node', ['node' => $organizationData, 'isRoot' => true])
            </div>
        </div>
    </div>
</div>

<style>
.organization-tree {
    font-family: Arial, sans-serif;
}

.org-node {
    margin: 10px 0;
}

.org-node-content {
    display: inline-block;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background-color: #f9fafb;
    min-width: 200px;
    text-align: center;
    position: relative;
}

.org-node-content.root {
    border-color: #3b82f6;
    background-color: #dbeafe;
}

.org-node-content.agent {
    border-color: #10b981;
    background-color: #d1fae5;
}

.org-node-content.player {
    border-color: #f59e0b;
    background-color: #fef3c7;
}

.org-children {
    margin-left: 30px;
    border-left: 2px solid #e5e7eb;
    padding-left: 20px;
    margin-top: 10px;
}

.org-players {
    margin-left: 30px;
    margin-top: 10px;
}

.dark .org-node-content {
    background-color: #374151;
    border-color: #4b5563;
    color: #f9fafb;
}

.dark .org-node-content.root {
    border-color: #3b82f6;
    background-color: #1e3a8a;
}

.dark .org-node-content.agent {
    border-color: #10b981;
    background-color: #064e3b;
}

.dark .org-node-content.player {
    border-color: #f59e0b;
    background-color: #92400e;
}

.dark .org-children {
    border-color: #4b5563;
}
</style>