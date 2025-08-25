<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 文檔控制器
 */
class ApiDocumentationController extends Controller
{
    /**
     * 取得 API 文檔
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.0.0',
            'info' => [
                'title' => '活動記錄 API',
                'description' => '系統活動記錄管理 API，提供完整的審計追蹤功能',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'API Support',
                    'email' => 'api-support@example.com'
                ]
            ],
            'servers' => [
                [
                    'url' => url('/api/v1'),
                    'description' => '生產環境'
                ]
            ],
            'security' => [
                [
                    'bearerAuth' => []
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ],
                'schemas' => $this->getSchemas(),
                'responses' => $this->getResponses()
            ],
            'paths' => $this->getPaths()
        ]);
    }

    /**
     * 取得資料結構定義
     */
    private function getSchemas(): array
    {
        return [
            'Activity' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => '活動記錄 ID'],
                    'type' => ['type' => 'string', 'description' => '活動類型'],
                    'description' => ['type' => 'string', 'description' => '活動描述'],
                    'result' => ['type' => 'string', 'enum' => ['success', 'failure', 'warning']],
                    'risk_level' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10],
                    'risk_level_text' => ['type' => 'string', 'enum' => ['低', '中', '高', '極高']],
                    'ip_address' => ['type' => 'string', 'format' => 'ipv4'],
                    'user_agent' => ['type' => 'string', 'description' => '使用者代理'],
                    'properties' => ['type' => 'object', 'description' => '活動屬性'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'causer' => ['$ref' => '#/components/schemas/User'],
                    'subject' => ['$ref' => '#/components/schemas/Subject'],
                    'is_security_event' => ['type' => 'boolean'],
                    'links' => ['$ref' => '#/components/schemas/Links']
                ]
            ],
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'type' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'username' => ['type' => 'string']
                ]
            ],
            'Subject' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'type' => ['type' => 'string'],
                    'name' => ['type' => 'string']
                ]
            ],
            'Links' => [
                'type' => 'object',
                'properties' => [
                    'self' => ['type' => 'string', 'format' => 'uri'],
                    'related' => ['type' => 'string', 'format' => 'uri']
                ]
            ],
            'ActivityCollection' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Activity']
                    ],
                    'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                    'links' => ['$ref' => '#/components/schemas/PaginationLinks']
                ]
            ],
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'total' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'current_page' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                    'from' => ['type' => 'integer'],
                    'to' => ['type' => 'integer'],
                    'statistics' => ['$ref' => '#/components/schemas/Statistics']
                ]
            ],
            'Statistics' => [
                'type' => 'object',
                'properties' => [
                    'total_activities' => ['type' => 'integer'],
                    'security_events' => ['type' => 'integer'],
                    'success_rate' => ['type' => 'number', 'format' => 'float'],
                    'risk_distribution' => ['type' => 'object'],
                    'type_distribution' => ['type' => 'object']
                ]
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'error' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'code' => ['type' => 'string']
                ]
            ]
        ];
    }

    /**
     * 取得回應定義
     */
    private function getResponses(): array
    {
        return [
            'Unauthorized' => [
                'description' => '未授權',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ],
            'Forbidden' => [
                'description' => '權限不足',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ],
            'NotFound' => [
                'description' => '資源不存在',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ],
            'TooManyRequests' => [
                'description' => '請求過於頻繁',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ]
        ];
    }

    /**
     * 取得 API 路徑定義
     */
    private function getPaths(): array
    {
        return [
            '/activities' => [
                'get' => [
                    'summary' => '取得活動記錄列表',
                    'description' => '取得分頁的活動記錄列表，支援多種篩選和排序選項',
                    'parameters' => [
                        [
                            'name' => 'page',
                            'in' => 'query',
                            'description' => '頁碼',
                            'schema' => ['type' => 'integer', 'minimum' => 1]
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => '每頁筆數',
                            'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]
                        ],
                        [
                            'name' => 'search',
                            'in' => 'query',
                            'description' => '搜尋關鍵字',
                            'schema' => ['type' => 'string']
                        ],
                        [
                            'name' => 'date_from',
                            'in' => 'query',
                            'description' => '開始日期',
                            'schema' => ['type' => 'string', 'format' => 'date']
                        ],
                        [
                            'name' => 'date_to',
                            'in' => 'query',
                            'description' => '結束日期',
                            'schema' => ['type' => 'string', 'format' => 'date']
                        ],
                        [
                            'name' => 'type',
                            'in' => 'query',
                            'description' => '活動類型',
                            'schema' => ['type' => 'string']
                        ],
                        [
                            'name' => 'result',
                            'in' => 'query',
                            'description' => '操作結果',
                            'schema' => ['type' => 'string', 'enum' => ['success', 'failure', 'warning']]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => '成功',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/ActivityCollection']
                                ]
                            ]
                        ],
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        '403' => ['$ref' => '#/components/responses/Forbidden'],
                        '429' => ['$ref' => '#/components/responses/TooManyRequests']
                    ]
                ]
            ],
            '/activities/{id}' => [
                'get' => [
                    'summary' => '取得特定活動記錄',
                    'description' => '根據 ID 取得單一活動記錄的詳細資訊',
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'description' => '活動記錄 ID',
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => '成功',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Activity']
                                ]
                            ]
                        ],
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        '403' => ['$ref' => '#/components/responses/Forbidden'],
                        '404' => ['$ref' => '#/components/responses/NotFound']
                    ]
                ]
            ],
            '/activities/search' => [
                'get' => [
                    'summary' => '搜尋活動記錄',
                    'description' => '使用關鍵字搜尋活動記錄',
                    'parameters' => [
                        [
                            'name' => 'query',
                            'in' => 'query',
                            'required' => true,
                            'description' => '搜尋關鍵字',
                            'schema' => ['type' => 'string', 'minLength' => 2]
                        ],
                        [
                            'name' => 'limit',
                            'in' => 'query',
                            'description' => '結果數量限制',
                            'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => '成功',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Activity']
                                            ],
                                            'meta' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'query' => ['type' => 'string'],
                                                    'total' => ['type' => 'integer'],
                                                    'limit' => ['type' => 'integer']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}