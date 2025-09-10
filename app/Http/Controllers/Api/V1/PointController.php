<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PointTransactionRequest;
use App\Http\Resources\V1\PointTransactionResource;
use App\Http\Resources\V1\PointTransactionCollection;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\PointService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 點數管理 API 控制器
 * 
 * 提供點數查詢、分配、回收和交易記錄的 API 介面
 */
class PointController extends Controller
{
    public function __construct(
        private PointService $pointService,
        private ActivityLogger $activityLogger
    ) {
        // API 認證和權限檢查
        $this->middleware('auth:sanctum');
        $this->middleware('can:points.view')->only(['index', 'show', 'agentPoints', 'playerPoints']);
        $this->middleware('can:points.manage')->only(['allocate', 'recover', 'transfer']);
    }

    /**
     * 取得點數交易記錄列表
     * 
     * @param Request $request
     * @return PointTransactionCollection
     */
    public function index(Request $request): PointTransactionCollection
    {
        $request->validate([
            'agent_id' => 'nullable|exists:agents,id',
            'player_id' => 'nullable|exists:players,id',
            'type' => 'nullable|string|in:' . implode(',', array_keys(PointTransaction::getTransactionTypes())),
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:created_at,amount,type',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        $this->activityLogger->logApiAccess('points.index', [
            'filters' => $request->all(),
            'ip' => $request->ip()
        ]);

        $perPage = min($request->get('per_page', 50), 100);

        $query = PointTransaction::query()
            ->with(['agent', 'player', 'creator']);

        // 應用篩選條件
        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->get('agent_id'));
        }

        if ($request->filled('player_id')) {
            $query->where('player_id', $request->get('player_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->get('date_from'),
                $request->get('date_to')
            ]);
        }

        // 排序
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->paginate($perPage);

        return new PointTransactionCollection($transactions);
    }

    /**
     * 取得特定交易記錄詳情
     * 
     * @param int $id
     * @return PointTransactionResource
     */
    public function show(int $id): PointTransactionResource
    {
        $transaction = PointTransaction::with(['agent', 'player', 'creator'])
            ->findOrFail($id);

        $this->activityLogger->logApiAccess('points.show', [
            'transaction_id' => $id,
            'ip' => request()->ip()
        ]);

        return new PointTransactionResource($transaction);
    }

    /**
     * 取得代理點數資訊
     * 
     * @param int $agentId
     * @return JsonResponse
     */
    public function agentPoints(int $agentId): JsonResponse
    {
        $agent = Agent::with(['children', 'players', 'pointTransactions'])
            ->findOrFail($agentId);

        $pointsInfo = [
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'account' => $agent->account,
                'level' => $agent->level,
            ],
            'points' => [
                'total_points' => $agent->total_points,
                'allocated_points' => $agent->allocated_points,
                'remaining_points' => $agent->remaining_points,
                'utilization_rate' => $agent->total_points > 0 
                    ? round(($agent->allocated_points / $agent->total_points) * 100, 2) 
                    : 0,
            ],
            'distribution' => [
                'children_count' => $agent->children->count(),
                'children_points' => $agent->children->sum('total_points'),
                'players_count' => $agent->players->count(),
                'players_points' => $agent->players->sum('points'),
            ],
            'recent_transactions' => $agent->pointTransactions()
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'type_name' => $transaction->type_name,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at->toISOString(),
                    ];
                }),
        ];

        $this->activityLogger->logApiAccess('points.agent_points', [
            'agent_id' => $agentId,
        ]);

        return response()->json([
            'data' => $pointsInfo,
            'meta' => [
                'agent_id' => $agentId,
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * 取得玩家點數資訊
     * 
     * @param int $playerId
     * @return JsonResponse
     */
    public function playerPoints(int $playerId): JsonResponse
    {
        $player = Player::with(['agent', 'pointTransactions'])
            ->findOrFail($playerId);

        $pointsInfo = [
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'account' => $player->account,
            ],
            'agent' => [
                'id' => $player->agent->id,
                'name' => $player->agent->name,
                'level' => $player->agent->level,
            ],
            'points' => [
                'current_points' => $player->points,
            ],
            'transactions_summary' => [
                'total_transactions' => $player->pointTransactions->count(),
                'total_allocated' => $player->pointTransactions()
                    ->where('amount', '>', 0)
                    ->sum('amount'),
                'total_consumed' => abs($player->pointTransactions()
                    ->where('amount', '<', 0)
                    ->sum('amount')),
                'last_transaction_at' => $player->pointTransactions()
                    ->latest()
                    ->first()?->created_at?->toISOString(),
            ],
            'recent_transactions' => $player->pointTransactions()
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'type_name' => $transaction->type_name,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at->toISOString(),
                    ];
                }),
        ];

        $this->activityLogger->logApiAccess('points.player_points', [
            'player_id' => $playerId,
        ]);

        return response()->json([
            'data' => $pointsInfo,
            'meta' => [
                'player_id' => $playerId,
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * 分配點數
     * 
     * @param PointTransactionRequest $request
     * @return JsonResponse
     */
    public function allocate(PointTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            if ($data['target_type'] === 'agent') {
                $targetAgent = Agent::findOrFail($data['target_id']);
                $fromAgent = isset($data['from_agent_id']) 
                    ? Agent::findOrFail($data['from_agent_id']) 
                    : null;

                $this->pointService->allocatePointsToAgent(
                    $targetAgent, 
                    $data['amount'], 
                    $fromAgent
                );

                $message = "成功分配 {$data['amount']} 點數給代理 {$targetAgent->name}";
                $targetInfo = ['type' => 'agent', 'id' => $targetAgent->id, 'name' => $targetAgent->name];

            } else {
                $player = Player::findOrFail($data['target_id']);
                $fromAgent = Agent::findOrFail($data['from_agent_id']);

                $this->pointService->allocatePointsToPlayer(
                    $player, 
                    $data['amount'], 
                    $fromAgent
                );

                $message = "成功分配 {$data['amount']} 點數給玩家 {$player->name}";
                $targetInfo = ['type' => 'player', 'id' => $player->id, 'name' => $player->name];
            }

            $this->activityLogger->logApiAccess('points.allocate', [
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
                'amount' => $data['amount'],
                'from_agent_id' => $data['from_agent_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'target' => $targetInfo,
                    'amount' => $data['amount'],
                    'timestamp' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('points.allocate_failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return response()->json([
                'error' => 'Point Allocation Failed',
                'message' => '點數分配失敗: ' . $e->getMessage(),
                'code' => 'POINT_ALLOCATION_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 回收點數
     * 
     * @param PointTransactionRequest $request
     * @return JsonResponse
     */
    public function recover(PointTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            if ($data['target_type'] === 'agent') {
                $fromAgent = Agent::findOrFail($data['target_id']);
                $toAgent = Agent::findOrFail($data['from_agent_id']);

                $this->pointService->recoverPointsFromAgent(
                    $fromAgent, 
                    $data['amount'], 
                    $toAgent
                );

                $message = "成功從代理 {$fromAgent->name} 回收 {$data['amount']} 點數";
                $targetInfo = ['type' => 'agent', 'id' => $fromAgent->id, 'name' => $fromAgent->name];

            } else {
                $player = Player::findOrFail($data['target_id']);
                $toAgent = Agent::findOrFail($data['from_agent_id']);

                $this->pointService->recoverPointsFromPlayer(
                    $player, 
                    $data['amount'], 
                    $toAgent
                );

                $message = "成功從玩家 {$player->name} 回收 {$data['amount']} 點數";
                $targetInfo = ['type' => 'player', 'id' => $player->id, 'name' => $player->name];
            }

            $this->activityLogger->logApiAccess('points.recover', [
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
                'amount' => $data['amount'],
                'to_agent_id' => $data['from_agent_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'target' => $targetInfo,
                    'amount' => $data['amount'],
                    'timestamp' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('points.recover_failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return response()->json([
                'error' => 'Point Recovery Failed',
                'message' => '點數回收失敗: ' . $e->getMessage(),
                'code' => 'POINT_RECOVERY_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 取得點數統計資料
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'time_range' => 'string|in:1d,7d,30d,90d',
            'agent_id' => 'nullable|exists:agents,id',
        ]);

        $timeRange = $request->get('time_range', '7d');
        $agentId = $request->get('agent_id');

        $startDate = match($timeRange) {
            '1d' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            '90d' => now()->subMonths(3),
            default => now()->subWeek(),
        };

        $query = PointTransaction::query()
            ->where('created_at', '>=', $startDate);

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        $stats = [
            'summary' => [
                'total_transactions' => $query->count(),
                'total_allocated' => $query->where('amount', '>', 0)->sum('amount'),
                'total_recovered' => abs($query->where('amount', '<', 0)->sum('amount')),
                'net_flow' => $query->sum('amount'),
            ],
            'by_type' => $query->selectRaw('type, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type => [
                        'count' => $item->count,
                        'total_amount' => $item->total_amount,
                        'type_name' => PointTransaction::getTransactionTypes()[$item->type] ?? $item->type,
                    ]];
                }),
            'daily_summary' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count,
                        'total_amount' => $item->total_amount,
                    ];
                }),
        ];

        $this->activityLogger->logApiAccess('points.stats', [
            'time_range' => $timeRange,
            'agent_id' => $agentId,
        ]);

        return response()->json([
            'data' => $stats,
            'meta' => [
                'time_range' => $timeRange,
                'start_date' => $startDate->toISOString(),
                'end_date' => now()->toISOString(),
                'agent_id' => $agentId,
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }
}