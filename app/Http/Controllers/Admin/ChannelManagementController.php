<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\AgentService;
use App\Services\PlayerService;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 通路管理系統管理員控制器
 * 
 * 提供系統管理員級別的通路管理功能，包括：
 * - 系統總覽和統計
 * - 全域代理和玩家管理
 * - 系統級點數調整
 * - 資料匯出和報表
 * - 系統稽核和監控
 */
class ChannelManagementController extends Controller
{
    public function __construct(
        private AgentService $agentService,
        private PlayerService $playerService,
        private PointService $pointService
    ) {
        // 確保只有有點數管理權限的使用者可以存取
        $this->middleware(['can:channels.points.view']);
    }

    /**
     * 系統管理員通路管理總覽
     * 
     * @return View
     */
    public function index(): View
    {
        // 獲取系統統計資料
        $statistics = $this->getSystemStatistics();
        
        return view('admin.channels.system.index', compact('statistics'));
    }

    /**
     * 全域代理管理介面
     * 
     * @return View
     */
    public function agents(): View
    {
        return view('admin.channels.system.agents');
    }

    /**
     * 全域玩家管理介面
     * 
     * @return View
     */
    public function players(): View
    {
        return view('admin.channels.system.players');
    }

    /**
     * 系統級點數管理介面
     * 
     * @return View
     */
    public function points(): View
    {
        // 獲取點數統計
        $pointsStatistics = $this->getPointsStatistics();
        
        return view('admin.channels.system.points', compact('pointsStatistics'));
    }

    /**
     * 系統稽核和監控介面
     * 
     * @return View
     */
    public function audit(): View
    {
        // 獲取稽核統計
        $auditStatistics = $this->getAuditStatistics();
        
        return view('admin.channels.system.audit', compact('auditStatistics'));
    }

    /**
     * 資料匯出和報表介面
     * 
     * @return View
     */
    public function reports(): View
    {
        return view('admin.channels.system.reports');
    }

    /**
     * 獲取系統統計資料
     * 
     * @return array
     */
    private function getSystemStatistics(): array
    {
        return [
            // 代理統計
            'agents' => [
                'total' => Agent::count(),
                'active' => Agent::where('is_active', true)->count(),
                'inactive' => Agent::where('is_active', false)->count(),
                'by_level' => Agent::select('level', DB::raw('count(*) as count'))
                    ->groupBy('level')
                    ->orderBy('level')
                    ->get()
                    ->pluck('count', 'level')
                    ->toArray(),
                'recent' => Agent::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            ],
            
            // 玩家統計
            'players' => [
                'total' => Player::count(),
                'active' => Player::where('is_active', true)->count(),
                'inactive' => Player::where('is_active', false)->count(),
                'recent' => Player::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            ],
            
            // 點數統計
            'points' => [
                'total_allocated' => Agent::sum('total_points'),
                'total_remaining' => Agent::sum('remaining_points'),
                'player_points' => Player::sum('points'),
                'transactions_today' => PointTransaction::whereDate('created_at', Carbon::today())->count(),
                'transactions_week' => PointTransaction::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            ],
            
            // 前置符號統計
            'prefixes' => [
                'used' => Agent::where('level', 1)->whereNotNull('prefix')->count(),
                'available' => 26 - Agent::where('level', 1)->whereNotNull('prefix')->count(),
                'list' => Agent::where('level', 1)
                    ->whereNotNull('prefix')
                    ->pluck('prefix')
                    ->sort()
                    ->values()
                    ->toArray(),
            ],
        ];
    }

    /**
     * 獲取點數統計資料
     * 
     * @return array
     */
    private function getPointsStatistics(): array
    {
        return [
            // 點數分佈
            'distribution' => [
                'agents_total' => Agent::sum('total_points'),
                'agents_allocated' => Agent::sum('allocated_points'),
                'agents_remaining' => Agent::sum('remaining_points'),
                'players_total' => Player::sum('points'),
            ],
            
            // 交易統計
            'transactions' => [
                'today' => PointTransaction::whereDate('created_at', Carbon::today())->count(),
                'week' => PointTransaction::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
                'month' => PointTransaction::where('created_at', '>=', Carbon::now()->subMonth())->count(),
                'by_type' => PointTransaction::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type')
                    ->toArray(),
            ],
            
            // 異常檢測
            'anomalies' => $this->detectPointsAnomalies(),
        ];
    }

    /**
     * 獲取稽核統計資料
     * 
     * @return array
     */
    private function getAuditStatistics(): array
    {
        return [
            // 資料完整性檢查
            'integrity' => [
                'orphaned_players' => Player::whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('agents')
                        ->whereRaw('agents.id = players.agent_id');
                })->count(),
                
                'invalid_accounts' => $this->checkInvalidAccounts(),
                'point_inconsistencies' => $this->checkPointConsistencies(),
            ],
            
            // 系統健康度
            'health' => [
                'database_size' => $this->getDatabaseSize(),
                'transaction_volume' => PointTransaction::whereDate('created_at', Carbon::today())->count(),
                'error_rate' => $this->calculateErrorRate(),
            ],
        ];
    }

    /**
     * 檢測點數異常
     * 
     * @return array
     */
    private function detectPointsAnomalies(): array
    {
        $anomalies = [];
        
        // 檢查負點數
        $negativePoints = Agent::where('remaining_points', '<', 0)->count();
        if ($negativePoints > 0) {
            $anomalies[] = [
                'type' => 'negative_points',
                'count' => $negativePoints,
                'severity' => 'high',
                'message' => "發現 {$negativePoints} 個代理有負點數餘額"
            ];
        }
        
        // 檢查點數不一致
        $inconsistentAgents = Agent::whereRaw('total_points != allocated_points + remaining_points')->count();
        if ($inconsistentAgents > 0) {
            $anomalies[] = [
                'type' => 'inconsistent_points',
                'count' => $inconsistentAgents,
                'severity' => 'medium',
                'message' => "發現 {$inconsistentAgents} 個代理點數計算不一致"
            ];
        }
        
        return $anomalies;
    }

    /**
     * 檢查無效帳號
     * 
     * @return int
     */
    private function checkInvalidAccounts(): int
    {
        // 檢查代理帳號格式
        $invalidAgents = Agent::whereRaw("account != CONCAT(COALESCE(prefix, ''), username)")->count();
        
        // 檢查玩家帳號格式
        $invalidPlayers = Player::join('agents', 'players.agent_id', '=', 'agents.id')
            ->whereRaw("players.account != CONCAT(COALESCE(agents.prefix, ''), players.username)")
            ->count();
        
        return $invalidAgents + $invalidPlayers;
    }

    /**
     * 檢查點數一致性
     * 
     * @return int
     */
    private function checkPointConsistencies(): int
    {
        return Agent::whereRaw('total_points != allocated_points + remaining_points')->count();
    }

    /**
     * 獲取資料庫大小
     * 
     * @return string
     */
    private function getDatabaseSize(): string
    {
        $size = DB::select("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ")[0]->size_mb ?? 0;
        
        return $size . ' MB';
    }

    /**
     * 計算錯誤率
     * 
     * @return float
     */
    private function calculateErrorRate(): float
    {
        // 這裡可以根據實際需求實作錯誤率計算邏輯
        // 例如：失敗的交易數 / 總交易數
        return 0.0;
    }

    /**
     * 系統級點數調整 API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function adjustPoints(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:agent,player',
            'target_id' => 'required|integer',
            'amount' => 'required|numeric',
            'reason' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            if ($request->type === 'agent') {
                $agent = Agent::findOrFail($request->target_id);
                $this->pointService->systemAdjustAgentPoints(
                    $agent,
                    $request->amount,
                    $request->reason
                );
            } else {
                $player = Player::findOrFail($request->target_id);
                $this->pointService->systemAdjustPlayerPoints(
                    $player,
                    $request->amount,
                    $request->reason
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '點數調整成功'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => '點數調整失敗：' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 匯出系統報表
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:agents,players,points,transactions',
            'format' => 'required|in:csv,excel,pdf',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            // 這裡實作匯出邏輯
            // 可以使用 Laravel Excel 或其他匯出套件
            
            return response()->json([
                'success' => true,
                'message' => '報表匯出已開始，完成後將通知您',
                'download_url' => '#' // 實際的下載連結
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '匯出失敗：' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 執行系統稽核
     * 
     * @return JsonResponse
     */
    public function runAudit(): JsonResponse
    {
        try {
            $results = [
                'data_integrity' => $this->auditDataIntegrity(),
                'point_consistency' => $this->auditPointConsistency(),
                'account_validity' => $this->auditAccountValidity(),
            ];

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '稽核執行失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 稽核資料完整性
     * 
     * @return array
     */
    private function auditDataIntegrity(): array
    {
        return [
            'orphaned_players' => Player::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('agents')
                    ->whereRaw('agents.id = players.agent_id');
            })->count(),
            
            'missing_parent_agents' => Agent::where('level', '>', 1)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('agents as parent')
                        ->whereRaw('parent.id = agents.parent_id');
                })->count(),
        ];
    }

    /**
     * 稽核點數一致性
     * 
     * @return array
     */
    private function auditPointConsistency(): array
    {
        return [
            'inconsistent_agents' => Agent::whereRaw('total_points != allocated_points + remaining_points')->count(),
            'negative_balances' => Agent::where('remaining_points', '<', 0)->count(),
        ];
    }

    /**
     * 稽核帳號有效性
     * 
     * @return array
     */
    private function auditAccountValidity(): array
    {
        return [
            'invalid_agent_accounts' => Agent::whereRaw("account != CONCAT(COALESCE(prefix, ''), username)")->count(),
            'duplicate_accounts' => $this->checkDuplicateAccounts(),
        ];
    }

    /**
     * 檢查重複帳號
     * 
     * @return int
     */
    private function checkDuplicateAccounts(): int
    {
        $agentAccounts = Agent::pluck('account')->toArray();
        $playerAccounts = Player::pluck('account')->toArray();
        
        $allAccounts = array_merge($agentAccounts, $playerAccounts);
        $uniqueAccounts = array_unique($allAccounts);
        
        return count($allAccounts) - count($uniqueAccounts);
    }
}