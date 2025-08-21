{{-- 網路圖依賴關係檢視 --}}
<div class="space-y-4">
    @if(!empty($graphData['nodes']))
        {{-- 圖表容器 --}}
        <div class="relative bg-gray-50 dark:bg-gray-900 rounded-lg p-6 min-h-96">
            <div id="dependency-network" class="w-full h-96"></div>
            
            {{-- 圖例 --}}
            <div class="absolute top-4 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 space-y-2">
                <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">圖例</div>
                
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">中心權限</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">依賴權限</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-orange-500 rounded-full"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">被依賴權限</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">系統權限</span>
                </div>
            </div>
        </div>

        {{-- 節點詳細資訊 --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($graphData['nodes'] as $node)
                <div class="bg-white dark:bg-gray-800 rounded-lg border 
                           @if($node['is_center']) 
                               border-blue-300 dark:border-blue-700
                           @elseif($node['node_type'] === 'dependency') 
                               border-green-300 dark:border-green-700
                           @elseif($node['node_type'] === 'dependent') 
                               border-orange-300 dark:border-orange-700
                           @else 
                               border-gray-300 dark:border-gray-700
                           @endif
                           p-4">
                    
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-2">
                                <div class="w-3 h-3 rounded-full 
                                           @if($node['is_center']) 
                                               bg-blue-500
                                           @elseif($node['is_system']) 
                                               bg-red-500
                                           @elseif($node['node_type'] === 'dependency') 
                                               bg-green-500
                                           @elseif($node['node_type'] === 'dependent') 
                                               bg-orange-500
                                           @else 
                                               bg-gray-500
                                           @endif"></div>
                                
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $node['display_name'] }}
                                </h4>
                            </div>
                            
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                {{ $node['name'] }}
                            </p>
                            
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                    {{ ucfirst($node['module']) }}
                                </span>
                                
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                             @if($node['type'] === 'view') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                             @elseif($node['type'] === 'create') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                             @elseif($node['type'] === 'edit') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                             @elseif($node['type'] === 'delete') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                             @elseif($node['type'] === 'manage') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                             @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                             @endif">
                                    {{ ucfirst($node['type']) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col space-y-1 ml-2">
                            @if(!$node['is_center'])
                                <button wire:click="selectPermission({{ $node['id'] }})" 
                                        class="p-1 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                                        title="選擇此權限">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 連接關係列表 --}}
        @if(!empty($graphData['edges']))
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">連接關係</h4>
                <div class="space-y-2">
                    @foreach($graphData['edges'] as $edge)
                        @php
                            $fromNode = collect($graphData['nodes'])->firstWhere('id', $edge['from']);
                            $toNode = collect($graphData['nodes'])->firstWhere('id', $edge['to']);
                        @endphp
                        
                        @if($fromNode && $toNode)
                            <div class="flex items-center space-x-3 p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $fromNode['display_name'] }}
                                </div>
                                
                                <div class="flex items-center space-x-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $edge['label'] }}</span>
                                </div>
                                
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $toNode['display_name'] }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- JavaScript for network visualization --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 初始化網路圖
                initializeNetworkGraph();
            });
            
            function initializeNetworkGraph() {
                const container = document.getElementById('dependency-network');
                if (!container) return;
                
                // 檢查是否有 D3.js
                if (typeof d3 === 'undefined') {
                    container.innerHTML = `
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">網路圖視覺化</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">載入 D3.js 中...</p>
                            </div>
                        </div>
                    `;
                    return;
                }
                
                // 如果有圖表資料，立即渲染
                @if(!empty($graphData['nodes']))
                    const graphData = @json($graphData);
                    renderNetworkGraph(container, graphData);
                @else
                    container.innerHTML = `
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">網路圖視覺化</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">請選擇權限以顯示依賴關係圖</p>
                            </div>
                        </div>
                    `;
                @endif
            }
            
            function renderNetworkGraph(container, graphData) {
                if (!graphData.nodes || graphData.nodes.length === 0) return;
                
                // 清除現有內容
                d3.select(container).selectAll("*").remove();
                
                const width = container.clientWidth;
                const height = 400;
                
                // 建立 SVG
                const svg = d3.select(container)
                    .append("svg")
                    .attr("width", width)
                    .attr("height", height)
                    .attr("viewBox", [0, 0, width, height]);
                
                // 建立箭頭標記
                svg.append("defs").selectAll("marker")
                    .data(["dependency", "dependent"])
                    .enter().append("marker")
                    .attr("id", d => `arrow-${d}`)
                    .attr("viewBox", "0 -5 10 10")
                    .attr("refX", 20)
                    .attr("refY", 0)
                    .attr("markerWidth", 6)
                    .attr("markerHeight", 6)
                    .attr("orient", "auto")
                    .append("path")
                    .attr("d", "M0,-5L10,0L0,5")
                    .attr("fill", d => d === 'dependency' ? "#10B981" : "#F59E0B");
                
                // 建立力導向模擬
                const simulation = d3.forceSimulation(graphData.nodes)
                    .force("link", d3.forceLink(graphData.edges).id(d => d.id).distance(120))
                    .force("charge", d3.forceManyBody().strength(-400))
                    .force("center", d3.forceCenter(width / 2, height / 2))
                    .force("collision", d3.forceCollide().radius(30));
                
                // 建立連線
                const link = svg.append("g")
                    .attr("class", "links")
                    .selectAll("line")
                    .data(graphData.edges)
                    .enter().append("line")
                    .attr("stroke", d => d.type === 'dependency' ? "#10B981" : "#F59E0B")
                    .attr("stroke-opacity", 0.8)
                    .attr("stroke-width", 2)
                    .attr("marker-end", d => `url(#arrow-${d.type})`);
                
                // 建立節點群組
                const node = svg.append("g")
                    .attr("class", "nodes")
                    .selectAll("g")
                    .data(graphData.nodes)
                    .enter().append("g")
                    .attr("class", "node")
                    .call(d3.drag()
                        .on("start", dragstarted)
                        .on("drag", dragged)
                        .on("end", dragended));
                
                // 節點圓圈
                node.append("circle")
                    .attr("r", d => d.is_center ? 15 : 10)
                    .attr("fill", d => {
                        if (d.is_center) return "#3B82F6";
                        if (d.is_system) return "#EF4444";
                        if (d.node_type === 'dependency') return "#10B981";
                        if (d.node_type === 'dependent') return "#F59E0B";
                        return "#6B7280";
                    })
                    .attr("stroke", "#fff")
                    .attr("stroke-width", 2)
                    .style("cursor", d => d.is_center ? "default" : "pointer");
                
                // 節點標籤背景
                node.append("rect")
                    .attr("x", d => -d.display_name.length * 3)
                    .attr("y", d => d.is_center ? 22 : 18)
                    .attr("width", d => d.display_name.length * 6)
                    .attr("height", 14)
                    .attr("fill", "rgba(255, 255, 255, 0.9)")
                    .attr("stroke", "#e5e7eb")
                    .attr("stroke-width", 1)
                    .attr("rx", 2);
                
                // 節點標籤
                node.append("text")
                    .text(d => d.display_name)
                    .attr("x", 0)
                    .attr("y", d => d.is_center ? 32 : 28)
                    .attr("text-anchor", "middle")
                    .attr("font-size", d => d.is_center ? "11px" : "9px")
                    .attr("font-weight", d => d.is_center ? "bold" : "normal")
                    .attr("fill", "#374151")
                    .style("pointer-events", "none");
                
                // 節點工具提示
                node.append("title")
                    .text(d => `${d.display_name}\n${d.name}\n模組: ${d.module}\n類型: ${d.type}`);
                
                // 節點點擊事件
                node.on("click", function(event, d) {
                    if (!d.is_center) {
                        // 觸發 Livewire 方法選擇權限
                        const livewireComponent = container.closest('[wire\\:id]');
                        if (livewireComponent) {
                            window.Livewire.find(livewireComponent.getAttribute('wire:id'))
                                .call('selectPermission', d.id);
                        }
                    }
                });
                
                // 更新模擬
                simulation.on("tick", () => {
                    link
                        .attr("x1", d => d.source.x)
                        .attr("y1", d => d.source.y)
                        .attr("x2", d => d.target.x)
                        .attr("y2", d => d.target.y);
                        
                    node
                        .attr("transform", d => `translate(${d.x},${d.y})`);
                });
                
                // 拖拽函數
                function dragstarted(event, d) {
                    if (!event.active) simulation.alphaTarget(0.3).restart();
                    d.fx = d.x;
                    d.fy = d.y;
                }
                
                function dragged(event, d) {
                    d.fx = event.x;
                    d.fy = event.y;
                }
                
                function dragended(event, d) {
                    if (!event.active) simulation.alphaTarget(0);
                    d.fx = null;
                    d.fy = null;
                }
            }
        </script>
    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有依賴關係資料</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">此權限沒有依賴關係或被依賴關係</p>
        </div>
    @endif
</div>