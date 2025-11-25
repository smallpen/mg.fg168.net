<?php

namespace App\Livewire\Admin\Channels;

use App\Livewire\Admin\AdminComponent;
use App\Models\Agent;
use App\Models\Player;
use Illuminate\Support\Collection;

class OrganizationChart extends AdminComponent
{
    public ?Agent $rootAgent = null;
    public string $viewMode = 'tree'; // tree, points, compact
    public bool $showPlayers = true;
    public bool $showPoints = true;
    public string $selectedAgentId = '';
    public array $highlightPath = [];

    protected $queryString = [
        'rootAgent' => ['except' => null, 'as' => 'root'],
        'viewMode' => ['except' => 'tree'],
        'showPlayers' => ['except' => true],
        'showPoints' => ['except' => true],
    ];

    public function mount(?Agent $rootAgent = null): void
    {
        // 檢查權限
        $this->authorize('agents.view');
        
        $this->rootAgent = $rootAgent;
    }

    public function render()
    {
        $organizationData = $this->organizationData;
        $rootAgents = $this->getRootAgents();
        
        return view('livewire.admin.channels.organization-chart', [
            'organizationData' => $organizationData,
            'rootAgents' => $rootAgents,
            'totalAgents' => $this->getTotalAgents(),
            'totalPlayers' => $this->getTotalPlayers(),
            'totalPoints' => $this->getTotalPoints(),
        ]);
    }

    /**
     * 獲取組織架構資料
     */
    public function getOrganizationDataProperty(): array
    {
        if (!$this->rootAgent) {
            // 如果沒有指定根代理，獲取所有第一層代理
            $rootAgents = Agent::where('level', 1)
                ->where('is_active', true)
                ->with(['children', 'players'])
                ->get();
                
            return $rootAgents->map(function ($agent) {
                return $this->buildAgentNode($agent);
            })->toArray();
        }

        return [$this->buildAgentNode($this->rootAgent)];
    }

    /**
     * 建立代理節點資料
     */
    private function buildAgentNode(Agent $agent): array
    {
        $node = [
            'id' => $agent->id,
            'name' => $agent->name,
            'account' => $agent->account,
            'level' => $agent->level,
            'prefix' => $agent->prefix ?? $agent->parent?->full_prefix,
            'totalPoints' => (float) $agent->total_points,
            'allocatedPoints' => (float) $agent->allocated_points,
            'remainingPoints' => (float) $agent->remaining_points,
            'isActive' => $agent->is_active,
            'childrenCount' => $agent->children()->count(),
            'playersCount' => $agent->players()->count(),
            'type' => 'agent',
            'children' => [],
            'players' => [],
        ];

        // 添加下層代理
        if ($agent->children) {
            foreach ($agent->children as $child) {
                $node['children'][] = $this->buildAgentNode($child);
            }
        }

        // 添加隸屬玩家（如果啟用顯示）
        if ($this->showPlayers && $agent->players) {
            foreach ($agent->players as $player) {
                $node['players'][] = $this->buildPlayerNode($player);
            }
        }

        return $node;
    }

    /**
     * 建立玩家節點資料
     */
    private function buildPlayerNode(Player $player): array
    {
        return [
            'id' => $player->id,
            'name' => $player->name,
            'account' => $player->account,
            'points' => (float) $player->points,
            'isActive' => $player->is_active,
            'type' => 'player',
            'agentId' => $player->agent_id,
        ];
    }

    /**
     * 獲取所有第一層代理
     */
    private function getRootAgents(): Collection
    {
        return Agent::where('level', 1)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * 設定根代理
     */
    public function setRootAgent(?int $agentId = null): void
    {
        if ($agentId) {
            $this->rootAgent = Agent::findOrFail($agentId);
        } else {
            $this->rootAgent = null;
        }
        
        $this->selectedAgentId = '';
        $this->highlightPath = [];
        
        $this->dispatch('organization-chart-updated');
    }

    /**
     * 選擇代理節點
     */
    public function selectAgent(int $agentId): void
    {
        $this->selectedAgentId = (string) $agentId;
        
        // 計算高亮路徑
        $agent = Agent::find($agentId);
        if ($agent) {
            $this->highlightPath = $this->getAgentPath($agent);
        }
        
        $this->dispatch('agent-selected', [
            'agentId' => $agentId,
            'highlightPath' => $this->highlightPath,
        ]);
    }

    /**
     * 獲取代理的完整路徑
     */
    private function getAgentPath(Agent $agent): array
    {
        $path = [$agent->id];
        $current = $agent;
        
        while ($current->parent) {
            $current = $current->parent;
            array_unshift($path, $current->id);
        }
        
        return $path;
    }

    /**
     * 切換視圖模式
     */
    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        $this->dispatch('view-mode-changed', ['mode' => $mode]);
    }

    /**
     * 切換玩家顯示
     */
    public function togglePlayers(): void
    {
        $this->showPlayers = !$this->showPlayers;
        $this->dispatch('organization-chart-updated');
    }

    /**
     * 切換點數顯示
     */
    public function togglePoints(): void
    {
        $this->showPoints = !$this->showPoints;
        $this->dispatch('organization-chart-updated');
    }

    /**
     * 匯出圖表
     */
    public function exportChart(string $format = 'png'): void
    {
        $this->dispatch('export-chart', ['format' => $format]);
    }

    /**
     * 獲取統計資料
     */
    private function getTotalAgents(): int
    {
        if ($this->rootAgent) {
            return 1 + $this->rootAgent->getAllDescendants()->count();
        }
        
        return Agent::where('is_active', true)->count();
    }

    private function getTotalPlayers(): int
    {
        if ($this->rootAgent) {
            $agentIds = [$this->rootAgent->id];
            $agentIds = array_merge($agentIds, $this->rootAgent->getAllDescendants()->pluck('id')->toArray());
            
            return Player::whereIn('agent_id', $agentIds)
                ->where('is_active', true)
                ->count();
        }
        
        return Player::where('is_active', true)->count();
    }

    private function getTotalPoints(): float
    {
        if ($this->rootAgent) {
            $agentIds = [$this->rootAgent->id];
            $agentIds = array_merge($agentIds, $this->rootAgent->getAllDescendants()->pluck('id')->toArray());
            
            $agentPoints = Agent::whereIn('id', $agentIds)->sum('total_points');
            $playerPoints = Player::whereIn('agent_id', $agentIds)->sum('points');
            
            return (float) ($agentPoints + $playerPoints);
        }
        
        $agentPoints = Agent::where('is_active', true)->sum('total_points');
        $playerPoints = Player::where('is_active', true)->sum('points');
        
        return (float) ($agentPoints + $playerPoints);
    }

    /**
     * 獲取代理詳細資訊
     */
    public function getAgentDetails(int $agentId): array
    {
        $agent = Agent::with(['parent', 'children', 'players'])->find($agentId);
        
        if (!$agent) {
            return [];
        }
        
        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'account' => $agent->account,
            'email' => $agent->email,
            'phone' => $agent->phone,
            'level' => $agent->level,
            'prefix' => $agent->prefix ?? $agent->parent?->full_prefix,
            'totalPoints' => (float) $agent->total_points,
            'allocatedPoints' => (float) $agent->allocated_points,
            'remainingPoints' => (float) $agent->remaining_points,
            'isActive' => $agent->is_active,
            'parentName' => $agent->parent?->name,
            'childrenCount' => $agent->children->count(),
            'playersCount' => $agent->players->count(),
            'createdAt' => $agent->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 重新載入圖表
     */
    public function refreshChart(): void
    {
        $this->dispatch('organization-chart-updated');
    }
}