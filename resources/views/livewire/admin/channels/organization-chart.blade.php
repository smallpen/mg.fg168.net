<div class="space-y-6">
    {{-- 控制面板 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            {{-- 左側控制項 --}}
            <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-4">
                {{-- 根代理選擇 --}}
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        根代理：
                    </label>
                    <select 
                        wire:model.live="rootAgent.id"
                        wire:change="setRootAgent($event.target.value)"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent min-w-[150px]"
                    >
                        <option value="">全部代理</option>
                        @foreach($rootAgents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->account }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- 視圖模式 --}}
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        視圖：
                    </label>
                    <div class="flex rounded-md shadow-sm">
                        <button 
                            wire:click="setViewMode('tree')"
                            class="px-3 py-1.5 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-l-md {{ $viewMode === 'tree' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}"
                        >
                            樹狀圖
                        </button>
                        <button 
                            wire:click="setViewMode('points')"
                            class="px-3 py-1.5 text-xs font-medium border-t border-b border-gray-300 dark:border-gray-600 {{ $viewMode === 'points' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}"
                        >
                            點數分佈
                        </button>
                        <button 
                            wire:click="setViewMode('compact')"
                            class="px-3 py-1.5 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-r-md {{ $viewMode === 'compact' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}"
                        >
                            緊湊模式
                        </button>
                    </div>
                </div>
            </div>

            {{-- 右側控制項 --}}
            <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-3">
                {{-- 顯示選項 --}}
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model.live="showPlayers"
                            wire:change="togglePlayers"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        >
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">顯示玩家</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model.live="showPoints"
                            wire:change="togglePoints"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        >
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">顯示點數</span>
                    </label>
                </div>

                {{-- 操作按鈕 --}}
                <div class="flex items-center space-x-2">
                    <button 
                        wire:click="refreshChart"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        重新載入
                    </button>
                    
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            匯出
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                        >
                            <div class="py-1">
                                <button 
                                    wire:click="exportChart('png')"
                                    @click="open = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    匯出為 PNG
                                </button>
                                <button 
                                    wire:click="exportChart('pdf')"
                                    @click="open = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    匯出為 PDF
                                </button>
                                <button 
                                    wire:click="exportChart('svg')"
                                    @click="open = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    匯出為 SVG
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 統計資訊 --}}
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">代理總數</p>
                        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($totalAgents) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">玩家總數</p>
                        <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ number_format($totalPlayers) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">總點數</p>
                        <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ number_format($totalPoints, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 圖表容器 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    組織架構圖
                    @if($rootAgent)
                        - {{ $rootAgent->name }}
                    @endif
                </h3>
                
                {{-- 載入指示器 --}}
                <div wire:loading class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    載入中...
                </div>
            </div>

            {{-- 圖表區域 --}}
            <div 
                id="organization-chart-container"
                class="w-full bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600"
                style="min-height: 600px;"
            >
                <div id="organization-chart" class="w-full h-full"></div>
                
                {{-- 空狀態 --}}
                <div id="chart-empty-state" class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400" style="min-height: 600px;">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="text-lg font-medium mb-2">組織架構圖</p>
                    <p class="text-sm text-center max-w-md">
                        選擇根代理或查看完整的組織架構。圖表將顯示代理層級關係和點數分佈。
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- 代理詳情側邊欄 --}}
    <div 
        id="agent-details-sidebar"
        class="fixed inset-y-0 right-0 w-96 bg-white dark:bg-gray-800 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out z-50"
        style="display: none;"
    >
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">代理詳情</h3>
                <button 
                    onclick="closeAgentDetails()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="agent-details-content" class="flex-1 overflow-y-auto p-6">
                {{-- 內容將由 JavaScript 動態填充 --}}
            </div>
        </div>
    </div>

    {{-- 遮罩層 --}}
    <div 
        id="sidebar-overlay"
        class="fixed inset-0 bg-black bg-opacity-50 z-40"
        style="display: none;"
        onclick="closeAgentDetails()"
    ></div>
</div>

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 組織圖表類別
    class OrganizationChart {
        constructor(containerId, options = {}) {
            this.containerId = containerId;
            this.container = d3.select(`#${containerId}`);
            this.options = {
                width: 1200,
                height: 600,
                nodeWidth: 180,
                nodeHeight: 80,
                levelHeight: 120,
                ...options
            };
            
            this.svg = null;
            this.g = null;
            this.data = null;
            this.viewMode = 'tree';
            this.showPlayers = true;
            this.showPoints = true;
            
            this.init();
        }
        
        init() {
            // 清除容器
            this.container.selectAll('*').remove();
            
            // 創建 SVG
            this.svg = this.container
                .append('svg')
                .attr('width', '100%')
                .attr('height', this.options.height)
                .attr('viewBox', `0 0 ${this.options.width} ${this.options.height}`)
                .style('background', 'transparent');
            
            // 創建主群組
            this.g = this.svg.append('g');
            
            // 添加縮放和拖拽
            const zoom = d3.zoom()
                .scaleExtent([0.1, 3])
                .on('zoom', (event) => {
                    this.g.attr('transform', event.transform);
                });
            
            this.svg.call(zoom);
            
            // 添加重置按鈕
            this.addResetButton();
        }
        
        addResetButton() {
            const resetButton = this.svg
                .append('g')
                .attr('class', 'reset-button')
                .attr('transform', 'translate(20, 20)')
                .style('cursor', 'pointer')
                .on('click', () => {
                    this.svg.transition()
                        .duration(750)
                        .call(d3.zoom().transform, d3.zoomIdentity);
                });
            
            resetButton
                .append('rect')
                .attr('width', 80)
                .attr('height', 30)
                .attr('rx', 4)
                .attr('fill', '#374151')
                .attr('stroke', '#6B7280')
                .attr('opacity', 0.9);
            
            resetButton
                .append('text')
                .attr('x', 40)
                .attr('y', 20)
                .attr('text-anchor', 'middle')
                .attr('fill', 'white')
                .attr('font-size', '12px')
                .text('重置視圖');
        }
        
        render(data, options = {}) {
            this.data = data;
            this.viewMode = options.viewMode || this.viewMode;
            this.showPlayers = options.showPlayers !== undefined ? options.showPlayers : this.showPlayers;
            this.showPoints = options.showPoints !== undefined ? options.showPoints : this.showPoints;
            
            // 隱藏空狀態
            document.getElementById('chart-empty-state').style.display = 'none';
            
            if (!data || data.length === 0) {
                this.showEmptyState();
                return;
            }
            
            // 清除現有內容
            this.g.selectAll('*').remove();
            
            // 根據視圖模式渲染
            switch (this.viewMode) {
                case 'tree':
                    this.renderTreeView(data);
                    break;
                case 'points':
                    this.renderPointsView(data);
                    break;
                case 'compact':
                    this.renderCompactView(data);
                    break;
            }
        }
        
        renderTreeView(data) {
            // 創建樹狀佈局
            const root = d3.hierarchy({children: data}, d => d.children);
            const treeLayout = d3.tree()
                .size([this.options.width - 200, this.options.height - 100])
                .separation((a, b) => a.parent === b.parent ? 1 : 1.2);
            
            treeLayout(root);
            
            // 調整座標
            root.descendants().forEach(d => {
                d.x += 100;
                d.y += 50;
            });
            
            // 繪製連線
            this.drawLinks(root.links());
            
            // 繪製節點
            this.drawNodes(root.descendants().filter(d => d.data.type));
        }
        
        renderPointsView(data) {
            // 點數分佈視圖 - 使用圓形佈局
            const flatData = this.flattenData(data);
            const maxPoints = d3.max(flatData, d => d.totalPoints || d.points || 0);
            
            // 創建力導向佈局
            const simulation = d3.forceSimulation(flatData)
                .force('charge', d3.forceManyBody().strength(-300))
                .force('center', d3.forceCenter(this.options.width / 2, this.options.height / 2))
                .force('collision', d3.forceCollide().radius(d => this.getNodeRadius(d, maxPoints) + 5));
            
            // 繪製節點
            const nodes = this.g.selectAll('.node')
                .data(flatData)
                .enter()
                .append('g')
                .attr('class', 'node')
                .style('cursor', 'pointer')
                .on('click', (event, d) => this.onNodeClick(d));
            
            // 節點圓圈
            nodes.append('circle')
                .attr('r', d => this.getNodeRadius(d, maxPoints))
                .attr('fill', d => this.getNodeColor(d))
                .attr('stroke', '#fff')
                .attr('stroke-width', 2);
            
            // 節點標籤
            nodes.append('text')
                .attr('text-anchor', 'middle')
                .attr('dy', '.35em')
                .attr('font-size', '12px')
                .attr('fill', '#fff')
                .text(d => d.name);
            
            // 更新位置
            simulation.on('tick', () => {
                nodes.attr('transform', d => `translate(${d.x},${d.y})`);
            });
        }
        
        renderCompactView(data) {
            // 緊湊視圖 - 使用網格佈局
            const flatData = this.flattenData(data);
            const cols = Math.ceil(Math.sqrt(flatData.length));
            const cellWidth = this.options.width / cols;
            const cellHeight = this.options.height / Math.ceil(flatData.length / cols);
            
            const nodes = this.g.selectAll('.node')
                .data(flatData)
                .enter()
                .append('g')
                .attr('class', 'node')
                .attr('transform', (d, i) => {
                    const col = i % cols;
                    const row = Math.floor(i / cols);
                    return `translate(${col * cellWidth + cellWidth/2}, ${row * cellHeight + cellHeight/2})`;
                })
                .style('cursor', 'pointer')
                .on('click', (event, d) => this.onNodeClick(d));
            
            // 繪製緊湊節點
            this.drawCompactNodes(nodes);
        }
        
        drawLinks(links) {
            const linkGenerator = d3.linkVertical()
                .x(d => d.x)
                .y(d => d.y);
            
            this.g.selectAll('.link')
                .data(links)
                .enter()
                .append('path')
                .attr('class', 'link')
                .attr('d', linkGenerator)
                .attr('fill', 'none')
                .attr('stroke', '#6B7280')
                .attr('stroke-width', 2)
                .attr('opacity', 0.6);
        }
        
        drawNodes(nodes) {
            const nodeGroups = this.g.selectAll('.node')
                .data(nodes)
                .enter()
                .append('g')
                .attr('class', 'node')
                .attr('transform', d => `translate(${d.x - this.options.nodeWidth/2}, ${d.y - this.options.nodeHeight/2})`)
                .style('cursor', 'pointer')
                .on('click', (event, d) => this.onNodeClick(d.data));
            
            // 節點背景
            nodeGroups.append('rect')
                .attr('width', this.options.nodeWidth)
                .attr('height', this.options.nodeHeight)
                .attr('rx', 8)
                .attr('fill', d => this.getNodeColor(d.data))
                .attr('stroke', '#fff')
                .attr('stroke-width', 2)
                .attr('filter', 'drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1))');
            
            // 節點標題
            nodeGroups.append('text')
                .attr('x', this.options.nodeWidth / 2)
                .attr('y', 20)
                .attr('text-anchor', 'middle')
                .attr('font-size', '14px')
                .attr('font-weight', 'bold')
                .attr('fill', '#fff')
                .text(d => d.data.name);
            
            // 節點帳號
            nodeGroups.append('text')
                .attr('x', this.options.nodeWidth / 2)
                .attr('y', 35)
                .attr('text-anchor', 'middle')
                .attr('font-size', '11px')
                .attr('fill', '#fff')
                .attr('opacity', 0.9)
                .text(d => d.data.account);
            
            // 點數資訊
            if (this.showPoints) {
                nodeGroups.append('text')
                    .attr('x', this.options.nodeWidth / 2)
                    .attr('y', 55)
                    .attr('text-anchor', 'middle')
                    .attr('font-size', '10px')
                    .attr('fill', '#fff')
                    .attr('opacity', 0.8)
                    .text(d => {
                        if (d.data.type === 'agent') {
                            return `剩餘: ${d.data.remainingPoints}`;
                        } else {
                            return `點數: ${d.data.points}`;
                        }
                    });
            }
            
            // 統計資訊
            nodeGroups.append('text')
                .attr('x', this.options.nodeWidth / 2)
                .attr('y', 70)
                .attr('text-anchor', 'middle')
                .attr('font-size', '9px')
                .attr('fill', '#fff')
                .attr('opacity', 0.7)
                .text(d => {
                    if (d.data.type === 'agent') {
                        return `下層: ${d.data.childrenCount} | 玩家: ${d.data.playersCount}`;
                    } else {
                        return '玩家';
                    }
                });
        }
        
        drawCompactNodes(nodes) {
            // 緊湊節點背景
            nodes.append('rect')
                .attr('x', -60)
                .attr('y', -25)
                .attr('width', 120)
                .attr('height', 50)
                .attr('rx', 6)
                .attr('fill', d => this.getNodeColor(d))
                .attr('stroke', '#fff')
                .attr('stroke-width', 1);
            
            // 節點標籤
            nodes.append('text')
                .attr('text-anchor', 'middle')
                .attr('dy', '-5')
                .attr('font-size', '11px')
                .attr('font-weight', 'bold')
                .attr('fill', '#fff')
                .text(d => d.name);
            
            // 點數資訊
            if (this.showPoints) {
                nodes.append('text')
                    .attr('text-anchor', 'middle')
                    .attr('dy', '10')
                    .attr('font-size', '9px')
                    .attr('fill', '#fff')
                    .attr('opacity', 0.8)
                    .text(d => {
                        if (d.type === 'agent') {
                            return `剩餘: ${d.remainingPoints}`;
                        } else {
                            return `點數: ${d.points}`;
                        }
                    });
            }
        }
        
        getNodeColor(node) {
            if (node.type === 'player') {
                return node.isActive ? '#10B981' : '#6B7280';
            }
            
            // 代理顏色根據層級
            const colors = ['#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444', '#06B6D4'];
            const colorIndex = (node.level - 1) % colors.length;
            return node.isActive ? colors[colorIndex] : '#6B7280';
        }
        
        getNodeRadius(node, maxPoints) {
            const points = node.totalPoints || node.points || 0;
            const minRadius = 20;
            const maxRadius = 60;
            return minRadius + (points / maxPoints) * (maxRadius - minRadius);
        }
        
        flattenData(data) {
            const result = [];
            
            function traverse(nodes) {
                nodes.forEach(node => {
                    result.push(node);
                    if (node.children && node.children.length > 0) {
                        traverse(node.children);
                    }
                    if (node.players && node.players.length > 0 && this.showPlayers) {
                        result.push(...node.players);
                    }
                });
            }
            
            traverse.call(this, data);
            return result;
        }
        
        onNodeClick(node) {
            if (node.type === 'agent') {
                // 觸發 Livewire 事件
                @this.call('selectAgent', node.id);
                
                // 顯示代理詳情
                this.showAgentDetails(node.id);
            }
        }
        
        showAgentDetails(agentId) {
            // 獲取代理詳情
            @this.call('getAgentDetails', agentId).then(details => {
                if (details && Object.keys(details).length > 0) {
                    this.renderAgentDetails(details);
                    this.openSidebar();
                }
            });
        }
        
        renderAgentDetails(details) {
            const content = document.getElementById('agent-details-content');
            content.innerHTML = `
                <div class="space-y-6">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">基本資訊</h4>
                        <dl class="grid grid-cols-1 gap-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">姓名</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.name}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">帳號</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.account}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">層級</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">第 ${details.level} 層</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">前置符號</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.prefix || '-'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">狀態</dt>
                                <dd class="text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${details.isActive ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'}">
                                        ${details.isActive ? '啟用' : '停用'}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">點數資訊</h4>
                        <dl class="grid grid-cols-1 gap-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">總點數</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.totalPoints.toLocaleString()}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">已分配點數</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.allocatedPoints.toLocaleString()}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">剩餘點數</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.remainingPoints.toLocaleString()}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">組織資訊</h4>
                        <dl class="grid grid-cols-1 gap-3">
                            ${details.parentName ? `
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">上層代理</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.parentName}</dd>
                            </div>
                            ` : ''}
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">下層代理數量</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.childrenCount}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">隸屬玩家數量</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.playersCount}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">聯絡資訊</h4>
                        <dl class="grid grid-cols-1 gap-3">
                            ${details.email ? `
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">電子郵件</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.email}</dd>
                            </div>
                            ` : ''}
                            ${details.phone ? `
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">電話</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.phone}</dd>
                            </div>
                            ` : ''}
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">建立時間</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${details.createdAt}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            `;
        }
        
        openSidebar() {
            const sidebar = document.getElementById('agent-details-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.style.display = 'block';
            overlay.style.display = 'block';
            
            setTimeout(() => {
                sidebar.classList.remove('translate-x-full');
            }, 10);
        }
        
        showEmptyState() {
            document.getElementById('chart-empty-state').style.display = 'flex';
        }
        
        highlightPath(path) {
            // 高亮顯示路徑
            this.g.selectAll('.node')
                .style('opacity', d => path.includes(d.data.id) ? 1 : 0.3);
            
            this.g.selectAll('.link')
                .style('opacity', 0.1);
        }
        
        clearHighlight() {
            this.g.selectAll('.node').style('opacity', 1);
            this.g.selectAll('.link').style('opacity', 0.6);
        }
        
        exportChart(format) {
            const svgElement = this.svg.node();
            
            if (format === 'svg') {
                this.downloadSVG(svgElement);
            } else if (format === 'png') {
                this.downloadPNG(svgElement);
            } else if (format === 'pdf') {
                this.downloadPDF(svgElement);
            }
        }
        
        downloadSVG(svgElement) {
            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(svgElement);
            const blob = new Blob([svgString], { type: 'image/svg+xml' });
            this.downloadBlob(blob, 'organization-chart.svg');
        }
        
        downloadPNG(svgElement) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(svgElement);
            const svgBlob = new Blob([svgString], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(svgBlob);
            
            img.onload = () => {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                canvas.toBlob(blob => {
                    this.downloadBlob(blob, 'organization-chart.png');
                    URL.revokeObjectURL(url);
                });
            };
            
            img.src = url;
        }
        
        downloadPDF(svgElement) {
            // 簡化的 PDF 匯出 - 實際專案中可能需要使用 jsPDF 等庫
            this.downloadPNG(svgElement);
        }
        
        downloadBlob(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    }
    
    // 全域函數
    window.closeAgentDetails = function() {
        const sidebar = document.getElementById('agent-details-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        sidebar.classList.add('translate-x-full');
        
        setTimeout(() => {
            sidebar.style.display = 'none';
            overlay.style.display = 'none';
        }, 300);
    };
    
    // 初始化圖表
    let organizationChart = new OrganizationChart('organization-chart');
    
    // 渲染初始資料
    const initialData = @json($organizationData);
    organizationChart.render(initialData, {
        viewMode: @js($viewMode),
        showPlayers: @js($showPlayers),
        showPoints: @js($showPoints)
    });
    
    // Livewire 事件監聽
    Livewire.on('organization-chart-updated', () => {
        // 重新載入頁面來獲取最新資料
        window.location.reload();
    });
    
    Livewire.on('view-mode-changed', (event) => {
        organizationChart.viewMode = event.mode;
        organizationChart.render(initialData, {
            viewMode: event.mode,
            showPlayers: @js($showPlayers),
            showPoints: @js($showPoints)
        });
    });
    
    Livewire.on('agent-selected', (event) => {
        if (event.highlightPath && event.highlightPath.length > 0) {
            organizationChart.highlightPath(event.highlightPath);
        }
    });
    
    Livewire.on('export-chart', (event) => {
        organizationChart.exportChart(event.format);
    });
});
</script>
@endpush