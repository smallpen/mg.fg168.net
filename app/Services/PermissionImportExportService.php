<?php

namespace App\Services;

use App\Models\Permission;
use App\Repositories\PermissionRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * 權限匯入匯出服務
 * 
 * 處理權限資料的匯入匯出功能，包含衝突處理和驗證
 */
class PermissionImportExportService
{
    protected PermissionRepository $permissionRepository;
    protected AuditLogService $auditService;

    public function __construct(
        PermissionRepository $permissionRepository,
        AuditLogService $auditService
    ) {
        $this->permissionRepository = $permissionRepository;
        $this->auditService = $auditService;
    }

    /**
     * 匯出權限資料為 JSON 格式
     * 
     * @param array $filters 篩選條件
     * @return array
     */
    public function exportPermissions(array $filters = []): array
    {
        // 取得要匯出的權限
        $permissions = $this->getPermissionsForExport($filters);

        // 建立匯出資料結構
        $exportData = [
            'metadata' => [
                'version' => '1.0',
                'exported_at' => Carbon::now()->toISOString(),
                'exported_by' => auth()->user()->name ?? 'System',
                'total_permissions' => $permissions->count(),
                'filters_applied' => $filters,
            ],
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                    'type' => $permission->type,
                    'dependencies' => $permission->dependencies->pluck('name')->toArray(),
                    'created_at' => $permission->created_at?->toISOString(),
                    'updated_at' => $permission->updated_at?->toISOString(),
                ];
            })->toArray(),
        ];

        // 記錄匯出操作
        $this->auditService->logDataAccess('permissions', 'export', [
            'total_exported' => $permissions->count(),
            'filters' => $filters,
        ]);

        return $exportData;
    }

    /**
     * 匯入權限資料
     * 
     * @param array $importData 匯入資料
     * @param array $options 匯入選項
     * @return array 匯入結果
     */
    public function importPermissions(array $importData, array $options = []): array
    {
        // 驗證匯入資料格式
        $this->validateImportData($importData);

        $results = [
            'success' => false,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'conflicts' => [],
            'warnings' => [],
            'processed_permissions' => [],
        ];

        // 預設匯入選項
        $defaultOptions = [
            'conflict_resolution' => 'skip', // skip, update, merge
            'validate_dependencies' => true,
            'create_missing_dependencies' => false,
            'dry_run' => false,
        ];
        $options = array_merge($defaultOptions, $options);

        DB::beginTransaction();

        try {
            $permissions = $importData['permissions'] ?? [];
            
            // 第一階段：驗證所有權限資料
            $validationResults = $this->validatePermissionsData($permissions);
            if (!empty($validationResults['errors'])) {
                $results['errors'] = $validationResults['errors'];
                DB::rollBack();
                return $results;
            }

            // 第二階段：檢查衝突
            $conflictResults = $this->checkConflicts($permissions);
            $results['conflicts'] = $conflictResults;

            // 第三階段：處理權限匯入
            foreach ($permissions as $index => $permissionData) {
                try {
                    $processResult = $this->processPermissionImport(
                        $permissionData, 
                        $options, 
                        $conflictResults[$index] ?? null
                    );
                    
                    $results['processed_permissions'][] = $processResult;
                    
                    switch ($processResult['action']) {
                        case 'created':
                            $results['created']++;
                            break;
                        case 'updated':
                            $results['updated']++;
                            break;
                        case 'skipped':
                            $results['skipped']++;
                            break;
                    }
                    
                    if (!empty($processResult['warnings'])) {
                        $results['warnings'] = array_merge($results['warnings'], $processResult['warnings']);
                    }
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'permission' => $permissionData['name'] ?? "索引 {$index}",
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // 第四階段：處理依賴關係
            if ($options['validate_dependencies']) {
                $dependencyResults = $this->processDependencies($permissions, $options);
                $results['warnings'] = array_merge($results['warnings'], $dependencyResults['warnings']);
                $results['errors'] = array_merge($results['errors'], $dependencyResults['errors']);
            }

            // 如果是試運行，回滾交易
            if ($options['dry_run']) {
                DB::rollBack();
                $results['success'] = true;
                $results['dry_run'] = true;
            } else {
                DB::commit();
                $results['success'] = empty($results['errors']);
            }

            // 記錄匯入操作
            $this->auditService->logDataAccess('permissions', 'import', [
                'total_processed' => count($permissions),
                'created' => $results['created'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors_count' => count($results['errors']),
                'dry_run' => $options['dry_run'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = [
                'general' => '匯入過程發生錯誤：' . $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * 取得要匯出的權限
     * 
     * @param array $filters
     * @return Collection
     */
    protected function getPermissionsForExport(array $filters): Collection
    {
        $query = Permission::with(['dependencies', 'dependents']);

        // 應用篩選條件
        if (!empty($filters['modules'])) {
            $query->whereIn('module', $filters['modules']);
        }

        if (!empty($filters['types'])) {
            $query->whereIn('type', $filters['types']);
        }

        if (!empty($filters['usage_status'])) {
            switch ($filters['usage_status']) {
                case 'used':
                    $query->has('roles');
                    break;
                case 'unused':
                    $query->doesntHave('roles');
                    break;
            }
        }

        if (!empty($filters['permission_ids'])) {
            $query->whereIn('id', $filters['permission_ids']);
        }

        return $query->orderBy('module')->orderBy('name')->get();
    }

    /**
     * 驗證匯入資料格式
     * 
     * @param array $importData
     * @throws ValidationException
     */
    protected function validateImportData(array $importData): void
    {
        $validator = Validator::make($importData, [
            'metadata' => 'required|array',
            'metadata.version' => 'required|string',
            'permissions' => 'required|array|min:1',
            'permissions.*.name' => 'required|string|max:100',
            'permissions.*.display_name' => 'required|string|max:50',
            'permissions.*.description' => 'nullable|string|max:255',
            'permissions.*.module' => 'required|string|max:50',
            'permissions.*.type' => 'required|string|max:20',
            'permissions.*.dependencies' => 'nullable|array',
            'permissions.*.dependencies.*' => 'string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 檢查版本相容性
        $version = $importData['metadata']['version'] ?? '1.0';
        if (!$this->isVersionCompatible($version)) {
            throw new ValidationException(
                Validator::make([], []),
                ['version' => ['不支援的匯入檔案版本：' . $version]]
            );
        }
    }

    /**
     * 驗證權限資料
     * 
     * @param array $permissions
     * @return array
     */
    protected function validatePermissionsData(array $permissions): array
    {
        $errors = [];
        $names = [];

        foreach ($permissions as $index => $permission) {
            $permissionName = $permission['name'] ?? "索引 {$index}";

            // 檢查權限名稱格式
            if (!preg_match('/^[a-z_\.]+$/', $permission['name'] ?? '')) {
                $errors[] = [
                    'permission' => $permissionName,
                    'field' => 'name',
                    'error' => '權限名稱格式不正確，只能包含小寫字母、底線和點號',
                ];
            }

            // 檢查重複的權限名稱
            if (in_array($permission['name'] ?? '', $names)) {
                $errors[] = [
                    'permission' => $permissionName,
                    'field' => 'name',
                    'error' => '匯入資料中存在重複的權限名稱',
                ];
            } else {
                $names[] = $permission['name'] ?? '';
            }

            // 檢查必填欄位
            $requiredFields = ['name', 'display_name', 'module', 'type'];
            foreach ($requiredFields as $field) {
                if (empty($permission[$field])) {
                    $errors[] = [
                        'permission' => $permissionName,
                        'field' => $field,
                        'error' => "必填欄位 {$field} 不能為空",
                    ];
                }
            }
        }

        return ['errors' => $errors];
    }

    /**
     * 檢查匯入衝突
     * 
     * @param array $permissions
     * @return array
     */
    protected function checkConflicts(array $permissions): array
    {
        $conflicts = [];
        $existingPermissions = Permission::pluck('name', 'id')->toArray();

        foreach ($permissions as $index => $permissionData) {
            $permissionName = $permissionData['name'];
            $existingId = array_search($permissionName, $existingPermissions);

            if ($existingId !== false) {
                $existing = Permission::find($existingId);
                $conflicts[$index] = [
                    'type' => 'name_conflict',
                    'existing_permission' => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'display_name' => $existing->display_name,
                        'module' => $existing->module,
                        'type' => $existing->type,
                        'updated_at' => $existing->updated_at?->toISOString(),
                    ],
                    'import_permission' => $permissionData,
                    'differences' => $this->findDifferences($existing->toArray(), $permissionData),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * 處理單個權限匯入
     * 
     * @param array $permissionData
     * @param array $options
     * @param array|null $conflict
     * @return array
     */
    protected function processPermissionImport(array $permissionData, array $options, ?array $conflict): array
    {
        $result = [
            'permission_name' => $permissionData['name'],
            'action' => 'skipped',
            'warnings' => [],
            'permission_id' => null,
        ];

        // 移除依賴關係，稍後處理
        $dependencies = $permissionData['dependencies'] ?? [];
        unset($permissionData['dependencies'], $permissionData['created_at'], $permissionData['updated_at']);

        if ($conflict) {
            // 處理衝突
            switch ($options['conflict_resolution']) {
                case 'skip':
                    $result['action'] = 'skipped';
                    $result['warnings'][] = '權限已存在，已跳過';
                    $result['permission_id'] = $conflict['existing_permission']['id'];
                    break;

                case 'update':
                    $permission = Permission::find($conflict['existing_permission']['id']);
                    $permission->update($permissionData);
                    $result['action'] = 'updated';
                    $result['permission_id'] = $permission->id;
                    break;

                case 'merge':
                    $permission = Permission::find($conflict['existing_permission']['id']);
                    $mergedData = $this->mergePermissionData($permission->toArray(), $permissionData);
                    $permission->update($mergedData);
                    $result['action'] = 'updated';
                    $result['permission_id'] = $permission->id;
                    $result['warnings'][] = '權限資料已合併';
                    break;
            }
        } else {
            // 建立新權限
            $permission = Permission::create($permissionData);
            $result['action'] = 'created';
            $result['permission_id'] = $permission->id;
        }

        return $result;
    }

    /**
     * 處理依賴關係
     * 
     * @param array $permissions
     * @param array $options
     * @return array
     */
    protected function processDependencies(array $permissions, array $options): array
    {
        $warnings = [];
        $errors = [];

        foreach ($permissions as $permissionData) {
            if (empty($permissionData['dependencies'])) {
                continue;
            }

            $permission = Permission::where('name', $permissionData['name'])->first();
            if (!$permission) {
                continue;
            }

            $dependencyIds = [];
            foreach ($permissionData['dependencies'] as $dependencyName) {
                $dependency = Permission::where('name', $dependencyName)->first();
                
                if (!$dependency) {
                    if ($options['create_missing_dependencies']) {
                        $warnings[] = [
                            'permission' => $permissionData['name'],
                            'message' => "依賴權限 '{$dependencyName}' 不存在，需要手動建立",
                        ];
                    } else {
                        $warnings[] = [
                            'permission' => $permissionData['name'],
                            'message' => "依賴權限 '{$dependencyName}' 不存在，已跳過",
                        ];
                    }
                    continue;
                }

                $dependencyIds[] = $dependency->id;
            }

            // 檢查循環依賴
            try {
                if (!empty($dependencyIds)) {
                    $this->permissionRepository->syncDependencies($permission, $dependencyIds);
                }
            } catch (\InvalidArgumentException $e) {
                $errors[] = [
                    'permission' => $permissionData['name'],
                    'error' => '依賴關係設定失敗：' . $e->getMessage(),
                ];
            }
        }

        return ['warnings' => $warnings, 'errors' => $errors];
    }

    /**
     * 找出兩個權限資料的差異
     * 
     * @param array $existing
     * @param array $import
     * @return array
     */
    protected function findDifferences(array $existing, array $import): array
    {
        $differences = [];
        $compareFields = ['display_name', 'description', 'module', 'type'];

        foreach ($compareFields as $field) {
            $existingValue = $existing[$field] ?? '';
            $importValue = $import[$field] ?? '';
            
            if ($existingValue !== $importValue) {
                $differences[$field] = [
                    'existing' => $existingValue,
                    'import' => $importValue,
                ];
            }
        }

        return $differences;
    }

    /**
     * 合併權限資料
     * 
     * @param array $existing
     * @param array $import
     * @return array
     */
    protected function mergePermissionData(array $existing, array $import): array
    {
        // 簡單的合併策略：匯入資料優先，但保留現有的 ID 和時間戳
        $merged = array_merge($existing, $import);
        
        // 保留原有的重要欄位
        $merged['id'] = $existing['id'];
        $merged['created_at'] = $existing['created_at'];
        
        return $merged;
    }

    /**
     * 檢查版本相容性
     * 
     * @param string $version
     * @return bool
     */
    protected function isVersionCompatible(string $version): bool
    {
        $supportedVersions = ['1.0'];
        return in_array($version, $supportedVersions);
    }

    /**
     * 生成匯入結果報告
     * 
     * @param array $results
     * @return array
     */
    public function generateImportReport(array $results): array
    {
        $report = [
            'summary' => [
                'total_processed' => $results['created'] + $results['updated'] + $results['skipped'],
                'successful' => $results['created'] + $results['updated'],
                'created' => $results['created'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors' => count($results['errors']),
                'warnings' => count($results['warnings']),
                'conflicts' => count($results['conflicts']),
            ],
            'details' => [
                'processed_permissions' => $results['processed_permissions'],
                'errors' => $results['errors'],
                'warnings' => $results['warnings'],
                'conflicts' => $results['conflicts'],
            ],
            'recommendations' => $this->generateRecommendations($results),
        ];

        return $report;
    }

    /**
     * 生成建議
     * 
     * @param array $results
     * @return array
     */
    protected function generateRecommendations(array $results): array
    {
        $recommendations = [];

        if (!empty($results['conflicts'])) {
            $recommendations[] = [
                'type' => 'conflict_resolution',
                'message' => '發現 ' . count($results['conflicts']) . ' 個權限名稱衝突，建議檢查衝突處理策略',
            ];
        }

        if (!empty($results['warnings'])) {
            $dependencyWarnings = array_filter($results['warnings'], function ($warning) {
                return strpos($warning['message'] ?? '', '依賴權限') !== false;
            });
            
            if (!empty($dependencyWarnings)) {
                $recommendations[] = [
                    'type' => 'dependency_issues',
                    'message' => '發現依賴關係問題，建議檢查並手動建立缺失的依賴權限',
                ];
            }
        }

        if ($results['skipped'] > 0) {
            $recommendations[] = [
                'type' => 'skipped_permissions',
                'message' => "有 {$results['skipped']} 個權限被跳過，如需更新請調整衝突處理策略",
            ];
        }

        return $recommendations;
    }
}