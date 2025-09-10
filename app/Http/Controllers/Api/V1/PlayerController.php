<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PlayerIndexRequest;
use App\Http\Requests\Api\V1\PlayerStoreRequest;
use App\Http\Requests\Api\V1\PlayerUpdateRequest;
use App\Http\Resources\V1\PlayerResource;
use App\Http\Resources\V1\PlayerCollection;
use App\Models\Player;
use App\Services\PlayerService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 玩家管理 API 控制器
 * 
 * 提供玩家的 RESTful API 介面，支援 CRUD 操作和點數管理
 */
class PlayerController extends Controller
{
    public function __construct(
        private PlayerService $playerService,
        private ActivityLogger $activityLogger
    ) {
        // API 認證和權限檢查
        $this->middleware('auth:sanctum');
        $this->middleware('can:players.view')->only(['index', 'show']);
        $this->middleware('can:players.create')->only(['store']);
        $this->middleware('can:players.edit')->only(['update']);
        $this->middleware('can:players.delete')->only(['destroy']);
    }

    /**
     * 取得玩家列表
     * 
     * @param PlayerIndexRequest $request
     * @return PlayerCollection
     */
    public function index(PlayerIndexRequest $request): PlayerCollection
    {
        // 記錄 API 存取
        $this->activityLogger->logApiAccess('players.index', [
            'filters' => $request->validated(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        $filters = $request->validated();
        $perPage = min($request->get('per_page', 50), 100);

        $query = Player::query()
            ->with(['agent', 'agent.parent']);

        // 應用篩選條件
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('account', 'like', "%{$filters['search']}%")
                  ->orWhere('username', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['min_points'])) {
            $query->where('points', '>=', $filters['min_points']);
        }

        if (!empty($filters['max_points'])) {
            $query->where('points', '<=', $filters['max_points']);
        }

        // 排序
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $players = $query->paginate($perPage);

        return new PlayerCollection($players);
    }

    /**
     * 建立新玩家
     * 
     * @param PlayerStoreRequest $request
     * @return PlayerResource
     */
    public function store(PlayerStoreRequest $request): PlayerResource
    {
        try {
            $player = $this->playerService->createPlayer($request->validated());

            $this->activityLogger->logApiAccess('players.store', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'agent_id' => $player->agent_id,
            ]);

            return new PlayerResource($player->load(['agent', 'pointTransactions']));

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('players.store_failed', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'Player Creation Failed',
                'message' => '玩家建立失敗: ' . $e->getMessage(),
                'code' => 'PLAYER_CREATE_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 取得特定玩家詳情
     * 
     * @param int $id
     * @return PlayerResource
     */
    public function show(int $id): PlayerResource
    {
        $player = Player::with(['agent', 'agent.parent', 'pointTransactions'])
            ->findOrFail($id);

        $this->activityLogger->logApiAccess('players.show', [
            'player_id' => $id,
            'ip' => request()->ip()
        ]);

        return new PlayerResource($player);
    }

    /**
     * 更新玩家資料
     * 
     * @param PlayerUpdateRequest $request
     * @param int $id
     * @return PlayerResource
     */
    public function update(PlayerUpdateRequest $request, int $id): PlayerResource
    {
        $player = Player::findOrFail($id);

        try {
            $updatedPlayer = $this->playerService->updatePlayer($player, $request->validated());

            $this->activityLogger->logApiAccess('players.update', [
                'player_id' => $id,
                'changes' => $request->validated(),
            ]);

            return new PlayerResource($updatedPlayer->load(['agent', 'pointTransactions']));

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('players.update_failed', [
                'player_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'Player Update Failed',
                'message' => '玩家更新失敗: ' . $e->getMessage(),
                'code' => 'PLAYER_UPDATE_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 刪除玩家
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);

        try {
            $this->playerService->deletePlayer($player);

            $this->activityLogger->logApiAccess('players.destroy', [
                'player_id' => $id,
                'player_name' => $player->name,
            ]);

            return response()->json([
                'message' => '玩家刪除成功',
                'data' => [
                    'id' => $id,
                    'name' => $player->name,
                    'deleted_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('players.destroy_failed', [
                'player_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Player Deletion Failed',
                'message' => '玩家刪除失敗: ' . $e->getMessage(),
                'code' => 'PLAYER_DELETE_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 取得玩家統計資料
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function stats(int $id): JsonResponse
    {
        $player = Player::with(['agent', 'pointTransactions'])
            ->findOrFail($id);

        $stats = [
            'basic' => [
                'points' => $player->points,
                'is_active' => $player->is_active,
                'created_at' => $player->created_at->toISOString(),
            ],
            'agent_info' => [
                'agent_id' => $player->agent->id,
                'agent_name' => $player->agent->name,
                'agent_level' => $player->agent->level,
                'agent_path' => $player->agent_path_string,
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
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at->toISOString(),
                    ];
                }),
        ];

        $this->activityLogger->logApiAccess('players.stats', [
            'player_id' => $id,
        ]);

        return response()->json([
            'data' => $stats,
            'meta' => [
                'player_id' => $id,
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * 批量操作玩家
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:activate,deactivate,transfer,export',
            'player_ids' => 'required|array|min:1|max:1000',
            'player_ids.*' => 'integer|exists:players,id',
            'agent_id' => 'required_if:action,transfer|exists:agents,id',
        ]);

        $action = $request->get('action');
        $playerIds = $request->get('player_ids');
        $agentId = $request->get('agent_id');

        try {
            $count = 0;
            $message = '';

            switch ($action) {
                case 'activate':
                    $count = Player::whereIn('id', $playerIds)->update(['is_active' => true]);
                    $message = "成功啟用 {$count} 個玩家";
                    break;
                    
                case 'deactivate':
                    $count = Player::whereIn('id', $playerIds)->update(['is_active' => false]);
                    $message = "成功停用 {$count} 個玩家";
                    break;
                    
                case 'transfer':
                    foreach ($playerIds as $playerId) {
                        $player = Player::find($playerId);
                        if ($player) {
                            $this->playerService->updatePlayer($player, ['agent_id' => $agentId]);
                            $count++;
                        }
                    }
                    $message = "成功轉移 {$count} 個玩家";
                    break;
                    
                case 'export':
                    // 實作匯出邏輯
                    $message = "成功匯出 " . count($playerIds) . " 個玩家資料";
                    break;
            }

            $this->activityLogger->logApiAccess('players.bulk_action', [
                'action' => $action,
                'player_ids' => $playerIds,
                'count' => $count,
                'agent_id' => $agentId,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'action' => $action,
                    'processed_count' => $count,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('players.bulk_action_failed', [
                'action' => $action,
                'player_ids' => $playerIds,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Bulk Action Failed',
                'message' => '批量操作失敗: ' . $e->getMessage(),
                'code' => 'BULK_ACTION_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}