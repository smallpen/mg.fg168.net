@extends('layouts.admin')

@section('title', '權限依賴關係圖表')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- 頁面標題 --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">權限依賴關係圖表</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    視覺化顯示權限之間的依賴關係，支援樹狀圖、網路圖和列表檢視
                </p>
            </div>
            
            {{-- 導航按鈕 --}}
            <div class="flex space-x-3">
                <a href="{{ route('admin.permissions.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    權限列表
                </a>
                
                @if(auth()->user()->hasPermission('permissions.test'))
                    <a href="{{ route('admin.permissions.test') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        權限測試
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- 依賴關係圖表元件 --}}
    <livewire:admin.permissions.dependency-graph :permission-id="$selectedPermissionId" />
</div>
@endsection

@push('scripts')
{{-- D3.js for network visualization --}}
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 監聽 Livewire 事件來更新網路圖
    window.addEventListener('dependency-graph-updated', function(event) {
        if (event.detail && event.detail.graphData) {
            updateNetworkVisualization(event.detail.graphData);
        }
    });
    
    // 初始化網路圖視覺化
    function updateNetworkVisualization(graphData) {
        const container = document.getElementById('dependency-network');
        if (!container || !graphData.nodes || graphData.nodes.length === 0) {
            return;
        }
        
        // 清除現有內容
        d3.select(container).selectAll("*").remove();
        
        const width = container.clientWidth;
        const height = 400;
        
        // 建立 SVG
        const svg = d3.select(container)
            .append("svg")
            .attr("width", width)
            .attr("height", height);
            
        // 建立力導向模擬
        const simulation = d3.forceSimulation(graphData.nodes)
            .force("link", d3.forceLink(graphData.edges).id(d => d.id).distance(100))
            .force("charge", d3.forceManyBody().strength(-300))
            .force("center", d3.forceCenter(width / 2, height / 2));
        
        // 建立連線
        const link = svg.append("g")
            .attr("class", "links")
            .selectAll("line")
            .data(graphData.edges)
            .enter().append("line")
            .attr("stroke", "#999")
            .attr("stroke-opacity", 0.6)
            .attr("stroke-width", 2);
            
        // 建立節點
        const node = svg.append("g")
            .attr("class", "nodes")
            .selectAll("g")
            .data(graphData.nodes)
            .enter().append("g")
            .call(d3.drag()
                .on("start", dragstarted)
                .on("drag", dragged)
                .on("end", dragended));
        
        // 節點圓圈
        node.append("circle")
            .attr("r", d => d.is_center ? 12 : 8)
            .attr("fill", d => {
                if (d.is_center) return "#3B82F6"; // blue
                if (d.is_system) return "#EF4444"; // red
                if (d.node_type === 'dependency') return "#10B981"; // green
                if (d.node_type === 'dependent') return "#F59E0B"; // orange
                return "#6B7280"; // gray
            })
            .attr("stroke", "#fff")
            .attr("stroke-width", 2);
        
        // 節點標籤
        node.append("text")
            .text(d => d.display_name)
            .attr("x", 0)
            .attr("y", d => d.is_center ? 20 : 16)
            .attr("text-anchor", "middle")
            .attr("font-size", d => d.is_center ? "12px" : "10px")
            .attr("fill", "#374151");
        
        // 節點點擊事件
        node.on("click", function(event, d) {
            if (!d.is_center) {
                // 觸發 Livewire 方法選擇權限
                const livewireContainer = container.closest('[wire\\:id]');
                const componentId = livewireContainer ? livewireContainer.getAttribute('wire:id') : null;
                const component = componentId ? window.Livewire.find(componentId) : null;
                
                // 確保元件存在且有 selectPermission 方法
                if (component && typeof component.call === 'function') {
                    try {
                        component.call('selectPermission', d.id);
                    } catch (error) {
                        console.warn('無法調用 selectPermission 方法:', error);
                    }
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
});
</script>
@endpush