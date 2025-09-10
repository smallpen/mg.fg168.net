<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

/**
 * 點數管理控制器
 * 
 * 負責處理系統級點數管理相關的頁面路由和 API
 */
class PointController extends Controller
{
    /**
     * 系統級點數管理頁面
     * 
     * @return View
     */
    public function index(): View
    {
        // 權限檢查由路由中介軟體處理
        return view('admin.channels.points.index');
    }

    /**
     * 點數統計 API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        // 權限檢查
        $this->authorize('channels.points.view');
        
        $user = auth()->user();
        
        // 根據使用者角色決定統計範圍
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            // 系統管理員可以看到全域統計
            $stats = $this->getGlobalPointStatistics();
        } elseif ($user->agent) {
            // 代理只能看到自己管轄範圍的統計
            $stats = $this->getAgentPointStatistics($user->agent);
        } else {
            abort(403, '您沒有權限檢視點數統計');
        }
        
        return response()->json($stats);
    }

    /**
     * 點數交易歷史 API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function transactions(Request $request): JsonResponse
    {
        // 權限檢查
        $this->authorize('channels.points.view');
        
        $user = auth()->user();
        $query = PointTransaction::with(['agent', 'player', 'creator']);
        
        // 根據使用者角色過濾資料
        if (!$user->hasAnyRole(['super_admin', 'admin'])) {
            if ($user->agent) {
                // 代理只能看到自己管轄範圍的交易
                $agentIds = $this->getAgentHierarchyIds($user->agent);
                $query->where(function ($q) use ($agentIds, $user) {
                    $q->whereIn('agent_id', $agentIds)
                      ->orWhereHas('player', function ($pq) use ($agentIds) {
                          $pq->whereIn('agent_id', $agentIds);
                      });
                });
            } else {
                abort(403, '您沒有權限檢視點數交易記錄');
            }
        }
        
        // 應用篩選條件
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 25));
        
        return response()->json($transactions);
    }

    /**
     * 獲取全域點數統計
     * 
     * @return array
     */
    private function getGlobalPointStatistics(): array
    {
        $totalAgents = Agent::count();
        $totalPlayers = Player::count();
        $totalAgentPoints = Agent::sum('total_points');
        $totalPlayerPoints = Player::sum('points');
        $totalAllocatedPoints = Agent::sum('allocated_points');
        $totalRemainingPoints = Agent::sum('remaining_points');
        
        // 今日交易統計
        $todayTransactions = PointTransaction::whereDate('created_at', today())->count();
        $todayAmount = PointTransaction::whereDate('created_at', today())->sum('amount');
        
        // 本月交易統計
        $monthlyTransactions = PointTransaction::whereMonth('created_at', now()->month)
                                              ->whereYear('created_at', now()->year)
                                              ->count();
        $monthlyAmount = PointTransaction::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->sum('amount');
        
        return [
            'overview' => [
                'total_agents' => $totalAgents,
                'total_players' => $totalPlayers,
                'total_agent_points' => $totalAgentPoints,
                'total_player_points' => $totalPlayerPoints,
                'total_allocated_points' => $totalAllocatedPoints,
                'total_remaining_points' => $totalRemainingPoints,
                'total_system_points' => $totalAgentPoints + $totalPlayerPoints,
            ],
            'transactions' => [
                'today_count' => $todayTransactions,
                'today_amount' => $todayAmount,
                'monthly_count' => $monthlyTransactions,
                'monthly_amount' => $monthlyAmount,
            ],
            'distribution' => [
                'agent_percentage' => $totalAgentPoints + $totalPlayerPoints > 0 
                    ? round(($totalAgentPoints / ($totalAgentPoints + $totalPlayerPoints)) * 100, 2)
                    : 0,
                'player_percentage' => $totalAgentPoints + $totalPlayerPoints > 0
                    ? round(($totalPlayerPoints / ($totalAgentPoints + $totalPlayerPoints)) * 100, 2)
                    : 0,
            ]
        ];
    }

    /**
     * 獲取代理點數統計
     * 
     * @param Agent $agent
     * @return array
     */
    private function getAgentPointStatistics(Agent $agent): array
    {
        $hierarchyIds = $this->getAgentHierarchyIds($agent);
        
        $totalSubAgents = Agent::whereIn('id', $hierarchyIds)->where('id', '!=', $agent->id)->count();
        $totalPlayers = Player::whereIn('agent_id', $hierarchyIds)->count();
        $totalSubAgentPoints = Agent::whereIn('id', $hierarchyIds)->where('id', '!=', $agent->id)->sum('total_points');
        $totalPlayerPoints = Player::whereIn('agent_id', $hierarchyIds)->sum('points');
        
        // 代理自己的點數統計
        $agentStats = [
            'total_points' => $agent->total_points,
            'allocated_points' => $agent->allocated_points,
            'remaining_points' => $agent->remaining_points,
        ];
        
        // 管轄範圍統計
        $hierarchyStats = [
            'total_sub_agents' => $totalSubAgents,
            'total_players' => $totalPlayers,
            'total_sub_agent_points' => $totalSubAgentPoints,
            'total_player_points' => $totalPlayerPoints,
        ];
        
        return [
            'agent' => $agentStats,
            'hierarchy' => $hierarchyStats,
            'total_managed_points' => $totalSubAgentPoints + $totalPlayerPoints,
        ];
    }

    /**
     * 獲取代理層級結構中的所有代理 ID
     * 
     * @param Agent $agent
     * @return array
     */
    private function getAgentHierarchyIds(Agent $agent): array
    {
        $ids = [$agent->id];
        
        // 遞迴獲取所有下層代理 ID
        $this->collectDescendantIds($agent, $ids);
        
        return $ids;
    }

    /**
     * 遞迴收集下層代理 ID
     * 
     * @param Agent $agent
     * @param array &$ids
     * @return void
     */
    private function collectDescendantIds(Agent $agent, array &$ids): void
    {
        $children = Agent::where('parent_id', $agent->id)->get();
        
        foreach ($children as $child) {
            $ids[] = $child->id;
            $this->collectDescendantIds($child, $ids);
        }
    }
}