<?php

namespace App\Livewire\Admin\Channels;

use Livewire\Component;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 系統管理員通路管理總覽元件
 * 
 * 提供系統級的統計資訊和快速操作功能
 */
class SystemOverview extends Component
{
    public array $statistics = [];
    public string $selectedPeriod = '7days';
    public bool $autoRefresh = true;
    
    public array $periodOptions = [
        '1day' => '今日',
        '7days' => '近7天',
        '30days' => '近30天',
        '90days' => '近90天',
    ];

    public function mount(): void
    {
        $this->loadStatistics();
    }

    public function render()
    {
        return view('livewire.admin.channels.system-overview');
    }

    public function updatedSelectedPeriod(): void
    {
        $this->loadStatistics();
    }

    public function refreshData(): void
    {
        $this->loadStatistics();
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '資料已更新'
        ]);
    }

    private function loadStatistics(): void
    {
        $period = $this->getPeriodDate();
        
        $this->statistics = [
            // 代理統計
            'agents' => [
                'total' => Agent::count(),
                'active' => Agent::where('is_active', true)->count(),
                'inactive' => Agent::where('is_active', false)->count(),
                'new_period' => Agent::where('created_at', '>=', $period)->count(),
                'by_level' => Agent::select('level', DB::raw('count(*) as count'))
                    ->groupBy('level')
                    ->orderBy('level')
                    ->get()
                    ->pluck('count', 'level')
                    ->toArray(),
                'top_agents' => $this->getTopAgentsByPoints(),
            ],
            
            // 玩家統計
            'players' => [
                'total' => Player::count(),
                'active' => Player::where('is_active', true)->count(),
                'inactive' => Player::where('is_active', false)->count(),
                'new_period' => Player::where('created_at', '>=', $period)->count(),
                'by_agent' => $this->getPlayersByAgent(),
            ],
            
            // 點數統計
            'points' => [
                'total_system' => Agent::sum('total_points'),
                'total_allocated' => Agent::sum('allocated_points'),
                'total_remaining' => Agent::sum('remaining_points'),
                'player_points' => Player::sum('points'),
                'transactions_period' => PointTransaction::where('created_at', '>=', $period)->count(),
                'volume_period' => PointTransaction::where('created_at', '>=', $period)
                    ->sum(DB::raw('ABS(amount)')),
                'by_type' => $this->getTransactionsByType($period),
            ],
            
            // 系統健康度
            'health' => [
                'data_integrity' => $this->checkDataIntegrity(),
                'point_consistency' => $this->checkPointConsistency(),
                'recent_errors' => $this->getRecentErrors(),
            ],
            
            // 前置符號使用情況
            'prefixes' => [
                'used' => Agent::where('level', 1)->whereNotNull('prefix')->count(),
                'available' => 26 - Agent::where('level', 1)->whereNotNull('prefix')->count(),
                'usage_rate' => round((Agent::where('level', 1)->whereNotNull('prefix')->count() / 26) * 100, 1),
                'list' => Agent::where('level', 1)
                    ->whereNotNull('prefix')
                    ->orderBy('prefix')
                    ->pluck('prefix')
                    ->toArray(),
            ],
        ];
    }

    private function getPeriodDate(): Carbon
    {
        return match ($this->selectedPeriod) {
            '1day' => Carbon::now()->subDay(),
            '7days' => Carbon::now()->subDays(7),
            '30days' => Carbon::now()->subDays(30),
            '90days' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(7),
        };
    }

    private function getTopAgentsByPoints(): array
    {
        return Agent::select('id', 'name', 'account', 'total_points', 'level')
            ->orderBy('total_points', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getPlayersByAgent(): array
    {
        return Agent::select('agents.id', 'agents.name', 'agents.account')
            ->withCount('players')
            ->orderBy('players_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getTransactionsByType(Carbon $period): array
    {
        return PointTransaction::select('type', DB::raw('count(*) as count'), DB::raw('sum(ABS(amount)) as volume'))
            ->where('created_at', '>=', $period)
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->toArray();
    }

    private function checkDataIntegrity(): array
    {
        return [
            'orphaned_players' => Player::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('agents')
                    ->whereRaw('agents.id = players.agent_id');
            })->count(),
            
            'invalid_hierarchy' => Agent::where('level', '>', 1)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('agents as parent')
                        ->whereRaw('parent.id = agents.parent_id');
                })->count(),
        ];
    }

    private function checkPointConsistency(): array
    {
        return [
            'inconsistent_totals' => Agent::whereRaw('total_points != allocated_points + remaining_points')->count(),
            'negative_balances' => Agent::where('remaining_points', '<', 0)->count(),
            'zero_point_agents' => Agent::where('total_points', 0)->where('is_active', true)->count(),
        ];
    }

    private function getRecentErrors(): int
    {
        // 這裡可以根據實際的錯誤日誌系統來實作
        // 暫時返回 0
        return 0;
    }

    public function exportSystemReport(): void
    {
        // 觸發匯出功能
        $this->dispatch('export-system-report');
    }

    public function runSystemAudit(): void
    {
        // 觸發系統稽核
        $this->dispatch('run-system-audit');
    }
}