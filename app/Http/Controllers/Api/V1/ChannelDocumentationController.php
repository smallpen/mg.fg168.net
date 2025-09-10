<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 通路管理 API 文檔控制器
 */
class ChannelDocumentationController extends Controller
{
    /**
     * 顯示 API 文檔
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'title' => '通路管理 API 文檔',
            'version' => 'v1.0.0',
            'description' => '提供代理、玩家和點數管理的完整 RESTful API',
            'base_url' => url('/api/v1/channels'),
            
            'authentication' => [
                'type' => 'Bearer Token',
                'description' => '使用 Laravel Sanctum 進行認證',
                'header' => 'Authorization: Bearer {your-token}',
                'example' => 'Authorization: Bearer 1|abc123def456...',
            ],
            
            'rate_limiting' => [
                'description' => '所有端點都有速率限制',
                'headers' => [
                    'X-RateLimit-Limit' => '每分鐘最大請求數',
                    'X-RateLimit-Remaining' => '剩餘請求數',
                    'X-RateLimit-Reset' => '重置時間戳',
                ],
                'limits' => [
                    'GET requests' => '100-200 per minute',
                    'POST/PUT requests' => '20-50 per minute',
                    'DELETE requests' => '10 per minute',
                    'Bulk operations' => '5 per minute',
                ],
            ],
            
            'response_format' => [
                'success' => [
                    'single_resource' => [
                        'data' => '{ resource object }',
                        'meta' => '{ metadata }',
                        'links' => '{ related links }',
                    ],
                    'collection' => [
                        'data' => '[ array of resources ]',
                        'summary' => '{ collection statistics }',
                        'meta' => '{ pagination and metadata }',
                        'links' => '{ pagination links }',
                    ],
                ],
                'error' => [
                    'error' => 'Error Type',
                    'message' => 'Human readable message',
                    'code' => 'ERROR_CODE',
                    'details' => '{ additional error details }',
                ],
            ],
            
            'endpoints' => [
                'agents' => [
                    'description' => '代理管理端點',
                    'base_path' => '/agents',
                    'operations' => [
                        [
                            'method' => 'GET',
                            'path' => '/',
                            'description' => '取得代理列表',
                            'parameters' => [
                                'search' => 'string - 搜尋關鍵字',
                                'level' => 'integer - 代理層級',
                                'prefix' => 'string - 前置符號',
                                'is_active' => 'boolean - 是否啟用',
                                'parent_id' => 'integer - 上層代理ID',
                                'per_page' => 'integer - 每頁筆數 (1-100)',
                                'sort_by' => 'string - 排序欄位',
                                'sort_order' => 'string - 排序方向 (asc/desc)',
                            ],
                            'permissions' => ['agents.view'],
                        ],
                        [
                            'method' => 'POST',
                            'path' => '/',
                            'description' => '建立新代理',
                            'body' => [
                                'name' => 'string* - 代理姓名',
                                'username' => 'string* - 使用者名稱',
                                'email' => 'string* - 電子郵件',
                                'phone' => 'string - 電話號碼',
                                'prefix' => 'string - 前置符號 (第一層代理必填)',
                                'parent_id' => 'integer - 上層代理ID (下層代理必填)',
                                'initial_points' => 'number* - 初始點數',
                                'is_active' => 'boolean - 是否啟用',
                                'notes' => 'string - 備註',
                            ],
                            'permissions' => ['agents.create'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/{id}',
                            'description' => '取得特定代理詳情',
                            'parameters' => [
                                'include_path' => 'boolean - 包含代理路徑',
                                'include_stats' => 'boolean - 包含統計資訊',
                                'include_transactions' => 'boolean - 包含最近交易',
                            ],
                            'permissions' => ['agents.view'],
                        ],
                        [
                            'method' => 'PUT',
                            'path' => '/{id}',
                            'description' => '更新代理資料',
                            'body' => [
                                'name' => 'string - 代理姓名',
                                'username' => 'string - 使用者名稱',
                                'email' => 'string - 電子郵件',
                                'phone' => 'string - 電話號碼',
                                'is_active' => 'boolean - 是否啟用',
                                'notes' => 'string - 備註',
                            ],
                            'permissions' => ['agents.edit'],
                        ],
                        [
                            'method' => 'DELETE',
                            'path' => '/{id}',
                            'description' => '刪除代理',
                            'permissions' => ['agents.delete'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/{id}/hierarchy',
                            'description' => '取得代理組織架構',
                            'permissions' => ['agents.view'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/{id}/stats',
                            'description' => '取得代理統計資料',
                            'permissions' => ['agents.view'],
                        ],
                    ],
                ],
                
                'players' => [
                    'description' => '玩家管理端點',
                    'base_path' => '/players',
                    'operations' => [
                        [
                            'method' => 'GET',
                            'path' => '/',
                            'description' => '取得玩家列表',
                            'parameters' => [
                                'search' => 'string - 搜尋關鍵字',
                                'agent_id' => 'integer - 隸屬代理ID',
                                'is_active' => 'boolean - 是否啟用',
                                'min_points' => 'number - 最小點數',
                                'max_points' => 'number - 最大點數',
                                'per_page' => 'integer - 每頁筆數 (1-100)',
                                'sort_by' => 'string - 排序欄位',
                                'sort_order' => 'string - 排序方向 (asc/desc)',
                            ],
                            'permissions' => ['players.view'],
                        ],
                        [
                            'method' => 'POST',
                            'path' => '/',
                            'description' => '建立新玩家',
                            'body' => [
                                'name' => 'string* - 玩家姓名',
                                'username' => 'string* - 使用者名稱',
                                'email' => 'string* - 電子郵件',
                                'phone' => 'string - 電話號碼',
                                'agent_id' => 'integer* - 隸屬代理ID',
                                'initial_points' => 'number* - 初始點數',
                                'is_active' => 'boolean - 是否啟用',
                                'notes' => 'string - 備註',
                            ],
                            'permissions' => ['players.create'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/{id}',
                            'description' => '取得特定玩家詳情',
                            'parameters' => [
                                'include_path' => 'boolean - 包含代理路徑',
                                'include_stats' => 'boolean - 包含統計資訊',
                                'include_transactions' => 'boolean - 包含最近交易',
                            ],
                            'permissions' => ['players.view'],
                        ],
                        [
                            'method' => 'PUT',
                            'path' => '/{id}',
                            'description' => '更新玩家資料',
                            'body' => [
                                'name' => 'string - 玩家姓名',
                                'username' => 'string - 使用者名稱',
                                'email' => 'string - 電子郵件',
                                'phone' => 'string - 電話號碼',
                                'agent_id' => 'integer - 隸屬代理ID',
                                'is_active' => 'boolean - 是否啟用',
                                'notes' => 'string - 備註',
                            ],
                            'permissions' => ['players.edit'],
                        ],
                        [
                            'method' => 'DELETE',
                            'path' => '/{id}',
                            'description' => '刪除玩家',
                            'permissions' => ['players.delete'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/{id}/stats',
                            'description' => '取得玩家統計資料',
                            'permissions' => ['players.view'],
                        ],
                        [
                            'method' => 'POST',
                            'path' => '/bulk-action',
                            'description' => '批量操作玩家',
                            'body' => [
                                'action' => 'string* - 操作類型 (activate/deactivate/transfer/export)',
                                'player_ids' => 'array* - 玩家ID陣列',
                                'agent_id' => 'integer - 目標代理ID (transfer時必填)',
                            ],
                            'permissions' => ['players.edit'],
                        ],
                    ],
                ],
                
                'points' => [
                    'description' => '點數管理端點',
                    'base_path' => '/points',
                    'operations' => [
                        [
                            'method' => 'GET',
                            'path' => '/',
                            'description' => '取得點數交易記錄',
                            'parameters' => [
                                'agent_id' => 'integer - 代理ID',
                                'player_id' => 'integer - 玩家ID',
                                'type' => 'string - 交易類型',
                                'date_from' => 'date - 開始日期',
                                'date_to' => 'date - 結束日期',
                                'per_page' => 'integer - 每頁筆數 (1-100)',
                                'sort_by' => 'string - 排序欄位',
                                'sort_order' => 'string - 排序方向 (asc/desc)',
                            ],
                            'permissions' => ['points.view'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/{id}',
                            'description' => '取得特定交易記錄詳情',
                            'permissions' => ['points.view'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/agents/{id}',
                            'description' => '取得代理點數資訊',
                            'permissions' => ['points.view'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/players/{id}',
                            'description' => '取得玩家點數資訊',
                            'permissions' => ['points.view'],
                        ],
                        [
                            'method' => 'POST',
                            'path' => '/allocate',
                            'description' => '分配點數',
                            'body' => [
                                'target_type' => 'string* - 目標類型 (agent/player)',
                                'target_id' => 'integer* - 目標ID',
                                'amount' => 'number* - 點數金額',
                                'from_agent_id' => 'integer - 來源代理ID',
                                'description' => 'string - 操作描述',
                            ],
                            'permissions' => ['points.manage'],
                        ],
                        [
                            'method' => 'POST',
                            'path' => '/recover',
                            'description' => '回收點數',
                            'body' => [
                                'target_type' => 'string* - 目標類型 (agent/player)',
                                'target_id' => 'integer* - 目標ID',
                                'amount' => 'number* - 點數金額',
                                'from_agent_id' => 'integer* - 回收到的代理ID',
                                'description' => 'string - 操作描述',
                            ],
                            'permissions' => ['points.manage'],
                        ],
                        [
                            'method' => 'GET',
                            'path' => '/stats',
                            'description' => '取得點數統計資料',
                            'parameters' => [
                                'time_range' => 'string - 時間範圍 (1d/7d/30d/90d)',
                                'agent_id' => 'integer - 代理ID',
                            ],
                            'permissions' => ['points.view'],
                        ],
                    ],
                ],
            ],
            
            'examples' => [
                'create_agent' => [
                    'description' => '建立第一層代理範例',
                    'request' => [
                        'method' => 'POST',
                        'url' => '/api/v1/channels/agents',
                        'headers' => [
                            'Authorization' => 'Bearer {token}',
                            'Content-Type' => 'application/json',
                        ],
                        'body' => [
                            'name' => '代理商A',
                            'username' => 'agent_a',
                            'email' => 'agent_a@example.com',
                            'phone' => '0912345678',
                            'prefix' => 'a',
                            'initial_points' => 100000,
                            'is_active' => true,
                            'notes' => '第一層代理商',
                        ],
                    ],
                ],
                'create_player' => [
                    'description' => '建立玩家範例',
                    'request' => [
                        'method' => 'POST',
                        'url' => '/api/v1/channels/players',
                        'headers' => [
                            'Authorization' => 'Bearer {token}',
                            'Content-Type' => 'application/json',
                        ],
                        'body' => [
                            'name' => '玩家001',
                            'username' => 'player001',
                            'email' => 'player001@example.com',
                            'agent_id' => 1,
                            'initial_points' => 1000,
                            'is_active' => true,
                        ],
                    ],
                ],
                'allocate_points' => [
                    'description' => '分配點數給玩家範例',
                    'request' => [
                        'method' => 'POST',
                        'url' => '/api/v1/channels/points/allocate',
                        'headers' => [
                            'Authorization' => 'Bearer {token}',
                            'Content-Type' => 'application/json',
                        ],
                        'body' => [
                            'target_type' => 'player',
                            'target_id' => 1,
                            'amount' => 500,
                            'from_agent_id' => 1,
                            'description' => '初始點數分配',
                        ],
                    ],
                ],
            ],
            
            'error_codes' => [
                'AGENT_CREATE_ERROR' => '代理建立失敗',
                'AGENT_UPDATE_ERROR' => '代理更新失敗',
                'AGENT_DELETE_ERROR' => '代理刪除失敗',
                'PLAYER_CREATE_ERROR' => '玩家建立失敗',
                'PLAYER_UPDATE_ERROR' => '玩家更新失敗',
                'PLAYER_DELETE_ERROR' => '玩家刪除失敗',
                'POINT_ALLOCATION_ERROR' => '點數分配失敗',
                'POINT_RECOVERY_ERROR' => '點數回收失敗',
                'BULK_ACTION_ERROR' => '批量操作失敗',
                'INSUFFICIENT_POINTS' => '點數不足',
                'INVALID_PREFIX' => '無效的前置符號',
                'PREFIX_ALREADY_EXISTS' => '前置符號已存在',
                'AGENT_HAS_DEPENDENCIES' => '代理有下層關聯，無法刪除',
            ],
            
            'changelog' => [
                'v1.0.0' => [
                    'date' => '2024-01-01',
                    'changes' => [
                        '初始版本發布',
                        '完整的代理管理 API',
                        '完整的玩家管理 API',
                        '完整的點數管理 API',
                        'RESTful 設計原則',
                        '完整的權限控制',
                        '速率限制保護',
                    ],
                ],
            ],
        ]);
    }
}