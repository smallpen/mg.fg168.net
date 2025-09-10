/**
 * 組織架構圖表 JavaScript 模組
 * 使用 D3.js 實現互動式組織架構視覺化
 */

import * as d3 from 'd3';

export class OrganizationChart {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = d3.select(`#${containerId}`);
        this.options = {
            width: 1200,
            height: 600,
            nodeWidth: 180,
            nodeHeight: 80,
            levelHeight: 120,
            margin: { top: 20, right: 20, bottom: 20, left: 20 },
            ...options
        };
        
        this.svg = null;
        this.g = null;
        this.data = null;
        this.viewMode = 'tree';
        this.showPlayers = true;
        this.showPoints = true;
        this.selectedNodes = new Set();
        
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
            .style('background', 'transparent')
            .style('font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif');
        
        // 創建主群組
        this.g = this.svg.append('g')
            .attr('transform', `translate(${this.options.margin.left}, ${this.options.margin.top})`);
        
        // 添加縮放和拖拽
        const zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on('zoom', (event) => {
                this.g.attr('transform', 
                    `translate(${this.options.margin.left}, ${this.options.margin.top}) ${event.transform}`
                );
            });
        
        this.svg.call(zoom);
        
        // 添加工具列
        this.addToolbar();
        
        // 添加圖例
        this.addLegend();
    }
    
    addToolbar() {
        const toolbar = this.svg
            .append('g')
            .attr('class', 'toolbar')
            .attr('transform', 'translate(20, 20)');
        
        // 重置視圖按鈕
        const resetButton = toolbar
            .append('g')
            .attr('class', 'reset-button')
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
        
        // 全螢幕按鈕
        const fullscreenButton = toolbar
            .append('g')
            .attr('class', 'fullscreen-button')
            .attr('transform', 'translate(90, 0)')
            .style('cursor', 'pointer')
            .on('click', () => this.toggleFullscreen());
        
        fullscreenButton
            .append('rect')
            .attr('width', 80)
            .attr('height', 30)
            .attr('rx', 4)
            .attr('fill', '#374151')
            .attr('stroke', '#6B7280')
            .attr('opacity', 0.9);
        
        fullscreenButton
            .append('text')
            .attr('x', 40)
            .attr('y', 20)
            .attr('text-anchor', 'middle')
            .attr('fill', 'white')
            .attr('font-size', '12px')
            .text('全螢幕');
    }
    
    addLegend() {
        const legend = this.svg
            .append('g')
            .attr('class', 'legend')
            .attr('transform', `translate(${this.options.width - 200}, 20)`);
        
        const legendData = [
            { color: '#3B82F6', label: '第1層代理', type: 'agent', level: 1 },
            { color: '#8B5CF6', label: '第2層代理', type: 'agent', level: 2 },
            { color: '#F59E0B', label: '第3層代理', type: 'agent', level: 3 },
            { color: '#10B981', label: '玩家', type: 'player' },
            { color: '#6B7280', label: '停用', type: 'inactive' }
        ];
        
        const legendItems = legend.selectAll('.legend-item')
            .data(legendData)
            .enter()
            .append('g')
            .attr('class', 'legend-item')
            .attr('transform', (d, i) => `translate(0, ${i * 25})`);
        
        legendItems
            .append('rect')
            .attr('width', 15)
            .attr('height', 15)
            .attr('fill', d => d.color)
            .attr('rx', 2);
        
        legendItems
            .append('text')
            .attr('x', 20)
            .attr('y', 12)
            .attr('font-size', '12px')
            .attr('fill', '#374151')
            .text(d => d.label);
    }
    
    render(data, options = {}) {
        this.data = data;
        this.viewMode = options.viewMode || this.viewMode;
        this.showPlayers = options.showPlayers !== undefined ? options.showPlayers : this.showPlayers;
        this.showPoints = options.showPoints !== undefined ? options.showPoints : this.showPoints;
        
        if (!data || data.length === 0) {
            this.showEmptyState();
            return;
        }
        
        // 隱藏空狀態
        this.hideEmptyState();
        
        // 清除現有內容
        this.g.selectAll('.chart-content').remove();
        
        // 創建圖表內容群組
        const chartContent = this.g.append('g').attr('class', 'chart-content');
        
        // 根據視圖模式渲染
        switch (this.viewMode) {
            case 'tree':
                this.renderTreeView(chartContent, data);
                break;
            case 'points':
                this.renderPointsView(chartContent, data);
                break;
            case 'compact':
                this.renderCompactView(chartContent, data);
                break;
        }
    }
    
    renderTreeView(container, data) {
        // 創建樹狀佈局
        const root = d3.hierarchy({ children: data }, d => {
            const children = [];
            if (d.children && d.children.length > 0) {
                children.push(...d.children);
            }
            if (this.showPlayers && d.players && d.players.length > 0) {
                children.push(...d.players);
            }
            return children.length > 0 ? children : null;
        });
        
        const treeLayout = d3.tree()
            .size([
                this.options.width - this.options.margin.left - this.options.margin.right - 200,
                this.options.height - this.options.margin.top - this.options.margin.bottom - 100
            ])
            .separation((a, b) => {
                return a.parent === b.parent ? 1 : 1.2;
            });
        
        treeLayout(root);
        
        // 繪製連線
        this.drawLinks(container, root.links());
        
        // 繪製節點
        this.drawNodes(container, root.descendants().filter(d => d.data.type || d.data.children));
    }
    
    renderPointsView(container, data) {
        // 點數分佈視圖 - 使用氣泡圖
        const flatData = this.flattenData(data);
        const maxPoints = d3.max(flatData, d => d.totalPoints || d.points || 0);
        
        // 創建包裝佈局
        const pack = d3.pack()
            .size([
                this.options.width - this.options.margin.left - this.options.margin.right,
                this.options.height - this.options.margin.top - this.options.margin.bottom
            ])
            .padding(5);
        
        const root = d3.hierarchy({ children: flatData })
            .sum(d => d.totalPoints || d.points || 1)
            .sort((a, b) => b.value - a.value);
        
        pack(root);
        
        // 繪製氣泡
        const bubbles = container.selectAll('.bubble')
            .data(root.leaves())
            .enter()
            .append('g')
            .attr('class', 'bubble')
            .attr('transform', d => `translate(${d.x},${d.y})`)
            .style('cursor', 'pointer')
            .on('click', (event, d) => this.onNodeClick(d.data));
        
        bubbles
            .append('circle')
            .attr('r', d => d.r)
            .attr('fill', d => this.getNodeColor(d.data))
            .attr('stroke', '#fff')
            .attr('stroke-width', 2)
            .attr('opacity', 0.8);
        
        bubbles
            .append('text')
            .attr('text-anchor', 'middle')
            .attr('dy', '0.3em')
            .attr('font-size', d => Math.min(d.r / 3, 14) + 'px')
            .attr('fill', '#fff')
            .attr('font-weight', 'bold')
            .text(d => d.data.name);
        
        if (this.showPoints) {
            bubbles
                .append('text')
                .attr('text-anchor', 'middle')
                .attr('dy', '1.5em')
                .attr('font-size', d => Math.min(d.r / 4, 10) + 'px')
                .attr('fill', '#fff')
                .attr('opacity', 0.9)
                .text(d => {
                    const points = d.data.totalPoints || d.data.points || 0;
                    return points.toLocaleString();
                });
        }
    }
    
    renderCompactView(container, data) {
        // 緊湊視圖 - 使用網格佈局
        const flatData = this.flattenData(data);
        const cols = Math.ceil(Math.sqrt(flatData.length));
        const cellWidth = (this.options.width - this.options.margin.left - this.options.margin.right) / cols;
        const cellHeight = (this.options.height - this.options.margin.top - this.options.margin.bottom) / Math.ceil(flatData.length / cols);
        
        const nodes = container.selectAll('.compact-node')
            .data(flatData)
            .enter()
            .append('g')
            .attr('class', 'compact-node')
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
    
    drawLinks(container, links) {
        const linkGenerator = d3.linkVertical()
            .x(d => d.x)
            .y(d => d.y);
        
        container.selectAll('.link')
            .data(links)
            .enter()
            .append('path')
            .attr('class', 'link')
            .attr('d', linkGenerator)
            .attr('fill', 'none')
            .attr('stroke', '#6B7280')
            .attr('stroke-width', 2)
            .attr('opacity', 0.6)
            .style('transition', 'all 0.3s ease');
    }
    
    drawNodes(container, nodes) {
        const nodeGroups = container.selectAll('.node')
            .data(nodes)
            .enter()
            .append('g')
            .attr('class', 'node')
            .attr('transform', d => `translate(${d.x - this.options.nodeWidth/2}, ${d.y - this.options.nodeHeight/2})`)
            .style('cursor', 'pointer')
            .style('transition', 'all 0.3s ease')
            .on('click', (event, d) => this.onNodeClick(d.data))
            .on('mouseenter', (event, d) => this.onNodeHover(event, d))
            .on('mouseleave', () => this.onNodeLeave());
        
        // 節點背景
        nodeGroups.append('rect')
            .attr('width', this.options.nodeWidth)
            .attr('height', this.options.nodeHeight)
            .attr('rx', 8)
            .attr('fill', d => this.getNodeColor(d.data))
            .attr('stroke', '#fff')
            .attr('stroke-width', 2)
            .style('filter', 'drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1))');
        
        // 節點圖示
        nodeGroups.append('text')
            .attr('x', 15)
            .attr('y', 25)
            .attr('font-size', '16px')
            .attr('fill', '#fff')
            .text(d => d.data.type === 'player' ? '👤' : '🏢');
        
        // 節點標題
        nodeGroups.append('text')
            .attr('x', 35)
            .attr('y', 20)
            .attr('font-size', '14px')
            .attr('font-weight', 'bold')
            .attr('fill', '#fff')
            .text(d => this.truncateText(d.data.name, 15));
        
        // 節點帳號
        nodeGroups.append('text')
            .attr('x', 35)
            .attr('y', 35)
            .attr('font-size', '11px')
            .attr('fill', '#fff')
            .attr('opacity', 0.9)
            .text(d => this.truncateText(d.data.account, 18));
        
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
                        return `剩餘: ${(d.data.remainingPoints || 0).toLocaleString()}`;
                    } else {
                        return `點數: ${(d.data.points || 0).toLocaleString()}`;
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
                    return `下層: ${d.data.childrenCount || 0} | 玩家: ${d.data.playersCount || 0}`;
                } else {
                    return '玩家';
                }
            });
    }
    
    drawCompactNodes(nodes) {
        const nodeSize = 60;
        
        // 緊湊節點背景
        nodes.append('rect')
            .attr('x', -nodeSize/2)
            .attr('y', -25)
            .attr('width', nodeSize)
            .attr('height', 50)
            .attr('rx', 6)
            .attr('fill', d => this.getNodeColor(d))
            .attr('stroke', '#fff')
            .attr('stroke-width', 1);
        
        // 節點標籤
        nodes.append('text')
            .attr('text-anchor', 'middle')
            .attr('dy', '-5')
            .attr('font-size', '10px')
            .attr('font-weight', 'bold')
            .attr('fill', '#fff')
            .text(d => this.truncateText(d.name, 8));
        
        // 點數資訊
        if (this.showPoints) {
            nodes.append('text')
                .attr('text-anchor', 'middle')
                .attr('dy', '10')
                .attr('font-size', '8px')
                .attr('fill', '#fff')
                .attr('opacity', 0.8)
                .text(d => {
                    if (d.type === 'agent') {
                        return `${(d.remainingPoints || 0).toLocaleString()}`;
                    } else {
                        return `${(d.points || 0).toLocaleString()}`;
                    }
                });
        }
    }
    
    getNodeColor(node) {
        if (!node.isActive) {
            return '#6B7280'; // 灰色表示停用
        }
        
        if (node.type === 'player') {
            return '#10B981'; // 綠色表示玩家
        }
        
        // 代理顏色根據層級
        const colors = ['#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444', '#06B6D4'];
        const colorIndex = ((node.level || 1) - 1) % colors.length;
        return colors[colorIndex];
    }
    
    flattenData(data) {
        const result = [];
        
        const traverse = (nodes) => {
            nodes.forEach(node => {
                result.push(node);
                if (node.children && node.children.length > 0) {
                    traverse(node.children);
                }
                if (this.showPlayers && node.players && node.players.length > 0) {
                    result.push(...node.players);
                }
            });
        };
        
        traverse(data);
        return result;
    }
    
    onNodeClick(node) {
        if (node.type === 'agent') {
            // 觸發 Livewire 事件
            if (window.Livewire) {
                window.Livewire.dispatch('agent-selected', { agentId: node.id });
            }
            
            // 顯示代理詳情
            this.showAgentDetails(node.id);
        }
    }
    
    onNodeHover(event, node) {
        // 顯示工具提示
        this.showTooltip(event, node.data);
    }
    
    onNodeLeave() {
        // 隱藏工具提示
        this.hideTooltip();
    }
    
    showTooltip(event, data) {
        const tooltip = d3.select('body')
            .selectAll('.org-chart-tooltip')
            .data([data]);
        
        const tooltipEnter = tooltip.enter()
            .append('div')
            .attr('class', 'org-chart-tooltip')
            .style('position', 'absolute')
            .style('background', 'rgba(0, 0, 0, 0.8)')
            .style('color', 'white')
            .style('padding', '8px 12px')
            .style('border-radius', '4px')
            .style('font-size', '12px')
            .style('pointer-events', 'none')
            .style('z-index', '1000')
            .style('opacity', 0);
        
        const tooltipUpdate = tooltipEnter.merge(tooltip);
        
        tooltipUpdate
            .html(this.getTooltipContent(data))
            .style('left', (event.pageX + 10) + 'px')
            .style('top', (event.pageY - 10) + 'px')
            .transition()
            .duration(200)
            .style('opacity', 1);
    }
    
    hideTooltip() {
        d3.select('.org-chart-tooltip')
            .transition()
            .duration(200)
            .style('opacity', 0)
            .remove();
    }
    
    getTooltipContent(data) {
        if (data.type === 'agent') {
            return `
                <div><strong>${data.name}</strong></div>
                <div>帳號: ${data.account}</div>
                <div>層級: 第 ${data.level} 層</div>
                <div>總點數: ${(data.totalPoints || 0).toLocaleString()}</div>
                <div>剩餘點數: ${(data.remainingPoints || 0).toLocaleString()}</div>
                <div>下層代理: ${data.childrenCount || 0}</div>
                <div>隸屬玩家: ${data.playersCount || 0}</div>
            `;
        } else {
            return `
                <div><strong>${data.name}</strong></div>
                <div>帳號: ${data.account}</div>
                <div>點數: ${(data.points || 0).toLocaleString()}</div>
                <div>類型: 玩家</div>
            `;
        }
    }
    
    showAgentDetails(agentId) {
        // 這個方法會被 Livewire 組件覆寫
        console.log('Show agent details:', agentId);
    }
    
    highlightPath(path) {
        // 高亮顯示路徑
        this.g.selectAll('.node')
            .style('opacity', d => {
                return path.includes(d.data.id) ? 1 : 0.3;
            });
        
        this.g.selectAll('.link')
            .style('opacity', 0.1);
    }
    
    clearHighlight() {
        this.g.selectAll('.node').style('opacity', 1);
        this.g.selectAll('.link').style('opacity', 0.6);
    }
    
    showEmptyState() {
        const emptyState = document.getElementById('chart-empty-state');
        if (emptyState) {
            emptyState.style.display = 'flex';
        }
    }
    
    hideEmptyState() {
        const emptyState = document.getElementById('chart-empty-state');
        if (emptyState) {
            emptyState.style.display = 'none';
        }
    }
    
    toggleFullscreen() {
        const container = document.getElementById(this.containerId).parentElement;
        
        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                console.error('無法進入全螢幕模式:', err);
            });
        } else {
            document.exitFullscreen();
        }
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
            canvas.width = img.width * 2; // 提高解析度
            canvas.height = img.height * 2;
            ctx.scale(2, 2);
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
    
    truncateText(text, maxLength) {
        if (text.length <= maxLength) {
            return text;
        }
        return text.substring(0, maxLength - 3) + '...';
    }
    
    // 公開方法供外部調用
    updateViewMode(mode) {
        this.viewMode = mode;
        if (this.data) {
            this.render(this.data, {
                viewMode: mode,
                showPlayers: this.showPlayers,
                showPoints: this.showPoints
            });
        }
    }
    
    updateShowPlayers(show) {
        this.showPlayers = show;
        if (this.data) {
            this.render(this.data, {
                viewMode: this.viewMode,
                showPlayers: show,
                showPoints: this.showPoints
            });
        }
    }
    
    updateShowPoints(show) {
        this.showPoints = show;
        if (this.data) {
            this.render(this.data, {
                viewMode: this.viewMode,
                showPlayers: this.showPlayers,
                showPoints: show
            });
        }
    }
}

// 全域輔助函數
window.OrganizationChart = OrganizationChart;

export default OrganizationChart;