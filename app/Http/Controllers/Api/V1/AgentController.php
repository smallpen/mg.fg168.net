<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AgentIndexRequest;
use App\Http\Requests\Api\V1\AgentStoreRequest;
use App\Http\Requests\Api\V1\AgentUpdateRequest;
use App\Http\Resources\V1\AgentResource;
use App\Http\Resources\V1\AgentCollection;
use App\Models\Agent;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 代理管理 API 控制器
 * 
 * 提供代理的 RESTful API 介面，支援 CRUD 操作、層級管理和點數操作
 */
class AgentController extends Controller
{
    public function __construct(
        private AgentService $agentService,
        private ActivityLogger $activityLogger
    ) {
        // API 認證和權限檢查
        $this->middleware('auth:sanctum');
        $this->middleware('can:agents.view')->only(['index', 'show']);
        $this->middleware('can:agents.create')->only(['store']);
        $this->middleware('can:agents.edit')->only(['update']);
        $this->middleware('can:agents.delete')->only(['destroy']);
    }

    /**
     * 取得代理列表
     * 
     * @param AgentIndexRequest $request
     * @return AgentCollection
     */
    public function index(AgentIndexRequest $request): AgentCollection
    {
        // 記錄 API 存取
        $this->activityLogger->logApiAccess('agents.index', [
            'filters' => $request->validated(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        $filters = $request->validated();
        $perPage = min($request->get('per_page', 50), 100);

        $query = Agent::query()
            ->with(['parent', 'children', 'players'])
            ->withCount(['children', 'players']);

        // 應用篩選條件
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('account', 'like', "%{$filters['search']}%")
                  ->orWhere('username', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['prefix'])) {
            $query->where('prefix', $filters['prefix']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        // 排序
        $sortBy = $filters['sort_by'] ?? 'level';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $agents = $query->paginate($perPage);

        return new AgentCollection($agents);
    }

    /**
     * 建立新代理
     * 
     * @param AgentStoreRequest $request
     * @return AgentResource
     */
    public function store(AgentStoreRequest $request): AgentResource
    {
        try {
            $agent = $this->agentService->createAgent($request->validated());

            $this->activityLogger->logApiAccess('agents.store', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'level' => $agent->level,
            ]);

            return new AgentResource($agent->load(['parent', 'children', 'players']));

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('agents.store_failed', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'Agent Creation Failed',
                'message' => '代理建立失敗: ' . $e->getMessage(),
                'code' => 'AGENT_CREATE_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 取得特定代理詳情
     * 
     * @param int $id
     * @return AgentResource
     */
    public function show(int $id): AgentResource
    {
        $agent = Agent::with(['parent', 'children', 'players', 'pointTransactions'])
            ->withCount(['children', 'players'])
            ->findOrFail($id);

        $this->activityLogger->logApiAccess('agents.show', [
            'agent_id' => $id,
            'ip' => request()->ip()
        ]);

        return new AgentResource($agent);
    }

    /**
     * 更新代理資料
     * 
     * @param AgentUpdateRequest $request
     * @param int $id
     * @return AgentResource
     */
    public function update(AgentUpdateRequest $request, int $id): AgentResource
    {
        $agent = Agent::findOrFail($id);

        try {
            $updatedAgent = $this->agentService->updateAgent($agent, $request->validated());

            $this->activityLogger->logApiAccess('agents.update', [
                'agent_id' => $id,
                'changes' => $request->validated(),
            ]);

            return new AgentResource($updatedAgent->load(['parent', 'children', 'players']));

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('agents.update_failed', [
                'agent_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'Agent Update Failed',
                'message' => '代理更新失敗: ' . $e->getMessage(),
                'code' => 'AGENT_UPDATE_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 刪除代理
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);

        try {
            $this->agentService->deleteAgent($agent);

            $this->activityLogger->logApiAccess('agents.destroy', [
                'agent_id' => $id,
                'agent_name' => $agent->name,
            ]);

            return response()->json([
                'message' => '代理刪除成功',
                'data' => [
                    'id' => $id,
                    'name' => $agent->name,
                    'deleted_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            $this->activityLogger->logApiAccess('agents.destroy_failed', [
                'agent_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Agent Deletion Failed',
                'message' => '代理刪除失敗: ' . $e->getMessage(),
                'code' => 'AGENT_DELETE_ERROR'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 取得代理的組織架構
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function hierarchy(int $id): JsonResponse
    {
        $agent = Agent::with(['parent', 'children.children', 'players'])
            ->findOrFail($id);

        $hierarchy = $this->buildHierarchy($agent);

        $this->activityLogger->logApiAccess('agents.hierarchy', [
            'agent_id' => $id,
        ]);

        return response()->json([
            'data' => $hierarchy,
            'meta' => [
                'agent_id' => $id,
                'total_descendants' => $agent->getAllDescendants()->count(),
                'total_players' => $agent->players->count(),
            ]
        ]);
    }

    /**
     * 取得代理統計資料
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function stats(int $id): JsonResponse
    {
        $agent = Agent::with(['children', 'players', 'pointTransactions'])
            ->findOrFail($id);

        $stats = [
            'basic' => [
                'total_points' => $agent->total_points,
                'allocated_points' => $agent->allocated_points,
                'remaining_points' => $agent->remaining_points,
                'children_count' => $agent->children->count(),
                'players_count' => $agent->players->count(),
            ],
            'hierarchy' => [
                'level' => $agent->level,
                'descendants_count' => $agent->getAllDescendants()->count(),
                'total_players_in_network' => $this->getTotalPlayersInNetwork($agent),
            ],
            'points_distribution' => [
                'children_points' => $agent->children->sum('total_points'),
                'players_points' => $agent->players->sum('points'),
                'utilization_rate' => $agent->total_points > 0 
                    ? round(($agent->allocated_points / $agent->total_points) * 100, 2) 
                    : 0,
            ],
            'recent_transactions' => $agent->pointTransactions()
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

        $this->activityLogger->logApiAccess('agents.stats', [
            'agent_id' => $id,
        ]);

        return response()->json([
            'data' => $stats,
            'meta' => [
                'agent_id' => $id,
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * 建立代理層級結構
     */
    private function buildHierarchy(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'account' => $agent->account,
            'level' => $agent->level,
            'total_points' => $agent->total_points,
            'remaining_points' => $agent->remaining_points,
            'is_active' => $agent->is_active,
            'children' => $agent->children->map(function ($child) {
                return $this->buildHierarchy($child);
            })->toArray(),
            'players' => $agent->players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'account' => $player->account,
                    'points' => $player->points,
                    'is_active' => $player->is_active,
                ];
            })->toArray(),
        ];
    }

    /**
     * 取得網絡中的總玩家數
     */
    private function getTotalPlayersInNetwork(Agent $agent): int
    {
        $count = $agent->players->count();
        
        foreach ($agent->children as $child) {
            $count += $this->getTotalPlayersInNetwork($child);
        }
        
        return $count;
    }
}