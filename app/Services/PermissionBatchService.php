<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 權限批量處理服務
 * 
 * 提供權限相關的批量操作和分批處理功能
 */
class PermissionBatchService
{
    /**
     * 權限快取服務
     */
    private PermissionCacheService $cacheService;

    /**
     * 預設批次大小
     */
    const DEFAULT_BATCH_SIZE = 100;
    const LARGE_BATCH_SIZE = 500;
    const SMALL_BATCH_SIZE = 50;

    public function __construct(PermissionCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 批量建立權限
     * 
     * @param array $permissionsData 權限資料陣列
     * @param int $batchSize 批次大小
     * @return array 處理結果
     */
    public function batchCreatePermissions(array $permissionsData, int $batchSize = self::DEFAULT_BATCH_SIZE): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'created_ids' => [],
        ];

        $chunks = array_chunk($permissionsData, $batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            
            try {
                foreach ($chunk as $index => $permissionData) {
                    try {
                        // 檢查權限是否已存在
                        if (Permission::where('name', $permissionData['name'])->exists()) {
                            $results['errors'][] = [
                                'index' => $chunkIndex * $batchSize + $index,
                                'data' => $permissionData,
                                'error' => '權限名稱已存在',
                            ];
                            $results['failed']++;
                            continue;
                        }

                        $permission = Permission::create($permissionData);
                        $results['created_ids'][] = $permission->id;
                        $results['success']++;
                        
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'index' => $chunkIndex * $batchSize + $index,
                            'data' => $permissionData,
                            'error' => $e->getMessage(),
                        ];
                        $results['failed']++;
                    }
                }
                
                DB::commit();
                
                // 清除相關快取
                $this->cacheService->clearPermissionCache();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('批量建立權限失敗', [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                ]);
                
                // 將整個批次標記為失敗
                foreach ($chunk as $index => $permissionData) {
                    $results['errors'][] = [
                        'index' => $chunkIndex * $batchSize + $index,
                        'data' => $permissionData,
                        'error' => '批次處理失敗: ' . $e->getMessage(),
                    ];
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * 批量更新權限
     * 
     * @param array $updates 更新資料陣列 [['id' => 1, 'data' => [...]], ...]
     * @param int $batchSize 批次大小
     * @return array 處理結果
     */
    public function batchUpdatePermissions(array $updates, int $batchSize = self::DEFAULT_BATCH_SIZE): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'updated_ids' => [],
        ];

        $chunks = array_chunk($updates, $batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            
            try {
                foreach ($chunk as $index => $update) {
                    try {
                        $permission = Permission::findOrFail($update['id']);
                        $permission->update($update['data']);
                        
                        $results['updated_ids'][] = $permission->id;
                        $results['success']++;
                        
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'index' => $chunkIndex * $batchSize + $index,
                            'id' => $update['id'],
                            'data' => $update['data'],
                            'error' => $e->getMessage(),
                        ];
                        $results['failed']++;
                    }
                }
                
                DB::commit();
                
                // 清除相關快取
                foreach ($chunk as $update) {
                    $this->cacheService->clearPermissionCache($update['id']);
                }
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('批量更新權限失敗', [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 批量刪除權限
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @param int $batchSize 批次大小
     * @return array 處理結果
     */
    public function batchDeletePermissions(array $permissionIds, int $batchSize = self::DEFAULT_BATCH_SIZE): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'deleted_ids' => [],
        ];

        $chunks = array_chunk($permissionIds, $batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            
            try {
                foreach ($chunk as $index => $permissionId) {
                    try {
                        $permission = Permission::findOrFail($permissionId);
                        
                        // 檢查是否可以刪除
                        if (!$permission->can_be_deleted) {
                            $results['errors'][] = [
                                'index' => $chunkIndex * $batchSize + $index,
                                'id' => $permissionId,
                                'error' => '權限無法刪除（被使用或為系統權限）',
                            ];
                            $results['failed']++;
                            continue;
                        }

                        // 移除依賴關係
                        $permission->dependencies()->detach();
                        $permission->dependents()->detach();
                        
                        // 移除角色關聯
                        $permission->roles()->detach();
                        
                        $permission->delete();
                        
                        $results['deleted_ids'][] = $permissionId;
                        $results['success']++;
                        
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'index' => $chunkIndex * $batchSize + $index,
                            'id' => $permissionId,
                            'error' => $e->getMessage(),
                        ];
                        $results['failed']++;
                    }
                }
                
                DB::commit();
                
                // 清除相關快取
                $this->cacheService->clearPermissionCache();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('批量刪除權限失敗', [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 批量同步角色權限
     * 
     * @param array $roleSyncs 同步資料 [['role_id' => 1, 'permission_ids' => [1,2,3]], ...]
     * @param int $batchSize 批次大小
     * @return array 處理結果
     */
    public function batchSyncRolePermissions(array $roleSyncs, int $batchSize = self::SMALL_BATCH_SIZE): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'synced_roles' => [],
        ];

        $chunks = array_chunk($roleSyncs, $batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            
            try {
                foreach ($chunk as $index => $sync) {
                    try {
                        $role = Role::findOrFail($sync['role_id']);
                        $role->permissions()->sync($sync['permission_ids']);
                        
                        $results['synced_roles'][] = $sync['role_id'];
                        $results['success']++;
                        
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'index' => $chunkIndex * $batchSize + $index,
                            'role_id' => $sync['role_id'],
                            'permission_ids' => $sync['permission_ids'],
                            'error' => $e->getMessage(),
                        ];
                        $results['failed']++;
                    }
                }
                
                DB::commit();
                
                // 清除相關快取
                foreach ($chunk as $sync) {
                    $this->cacheService->clearRolePermissionCache($sync['role_id']);
                }
                $this->cacheService->clearUserPermissionCache();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('批量同步角色權限失敗', [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 批量處理權限依賴關係
     * 
     * @param array $dependencies 依賴關係資料 [['permission_id' => 1, 'dependency_ids' => [2,3]], ...]
     * @param int $batchSize 批次大小
     * @return array 處理結果
     */
    public function batchSyncPermissionDependencies(array $dependencies, int $batchSize = self::SMALL_BATCH_SIZE): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'synced_permissions' => [],
        ];

        $chunks = array_chunk($dependencies, $batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            
            try {
                foreach ($chunk as $index => $dependency) {
                    try {
                        $permission = Permission::findOrFail($dependency['permission_id']);
                        
                        // 檢查循環依賴
                        foreach ($dependency['dependency_ids'] as $depId) {
                            if ($permission->hasCircularDependency([$depId])) {
                                throw new \InvalidArgumentException("會造成循環依賴: {$depId}");
                            }
                        }
                        
                        $permission->dependencies()->sync($dependency['dependency_ids']);
                        
                        $results['synced_permissions'][] = $dependency['permission_id'];
                        $results['success']++;
                        
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'index' => $chunkIndex * $batchSize + $index,
                            'permission_id' => $dependency['permission_id'],
                            'dependency_ids' => $dependency['dependency_ids'],
                            'error' => $e->getMessage(),
                        ];
                        $results['failed']++;
                    }
                }
                
                DB::commit();
                
                // 清除依賴關係快取
                foreach ($chunk as $dependency) {
                    $this->cacheService->clearPermissionCache($dependency['permission_id']);
                }
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('批量處理權限依賴關係失敗', [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 分批載入權限資料
     * 
     * @param array $filters 篩選條件
     * @param int $batchSize 批次大小
     * @param callable|null $callback 每批處理回調
     * @return Collection 所有權限資料
     */
    public function loadPermissionsInBatches(array $filters = [], int $batchSize = self::LARGE_BATCH_SIZE, ?callable $callback = null): Collection
    {
        $allPermissions = collect();
        $offset = 0;
        
        do {
            $query = Permission::with(['dependencies:id,name', 'dependents:id,name', 'roles:id,name'])
                              ->withCount(['roles', 'dependencies', 'dependents']);

            // 應用篩選條件
            if (!empty($filters['module'])) {
                $query->where('module', $filters['module']);
            }
            
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $batch = $query->offset($offset)
                          ->limit($batchSize)
                          ->orderBy('id')
                          ->get();

            if ($batch->isEmpty()) {
                break;
            }

            $allPermissions = $allPermissions->merge($batch);
            
            // 執行回調函數
            if ($callback) {
                $callback($batch, $offset, $batchSize);
            }
            
            $offset += $batchSize;
            
        } while ($batch->count() === $batchSize);

        return $allPermissions;
    }

    /**
     * 批量計算權限使用統計
     * 
     * @param array $permissionIds 權限 ID 陣列，空陣列表示所有權限
     * @param int $batchSize 批次大小
     * @return array 統計結果
     */
    public function batchCalculateUsageStats(array $permissionIds = [], int $batchSize = self::DEFAULT_BATCH_SIZE): array
    {
        if (empty($permissionIds)) {
            $permissionIds = Permission::pluck('id')->toArray();
        }

        $stats = [];
        $chunks = array_chunk($permissionIds, $batchSize);
        
        foreach ($chunks as $chunk) {
            $batchStats = $this->cacheService->getBatchPermissionUsage($chunk);
            $stats = array_merge($stats, $batchStats);
        }

        return $stats;
    }

    /**
     * 批量驗證權限依賴關係
     * 
     * @param array $permissionIds 權限 ID 陣列，空陣列表示所有權限
     * @param int $batchSize 批次大小
     * @return array 驗證結果
     */
    public function batchValidateDependencies(array $permissionIds = [], int $batchSize = self::SMALL_BATCH_SIZE): array
    {
        if (empty($permissionIds)) {
            $permissionIds = Permission::pluck('id')->toArray();
        }

        $results = [
            'valid' => 0,
            'invalid' => 0,
            'errors' => [],
        ];

        $chunks = array_chunk($permissionIds, $batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $permissionId) {
                try {
                    $permission = Permission::find($permissionId);
                    if (!$permission) {
                        continue;
                    }

                    // 檢查依賴關係的完整性
                    $dependencies = $permission->dependencies()->pluck('id')->toArray();
                    
                    foreach ($dependencies as $depId) {
                        if (\App\Models\PermissionDependency::hasDependencyPath($depId, $permissionId)) {
                            $results['errors'][] = [
                                'permission_id' => $permissionId,
                                'dependency_id' => $depId,
                                'error' => '存在循環依賴',
                            ];
                            $results['invalid']++;
                        } else {
                            $results['valid']++;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'permission_id' => $permissionId,
                        'error' => $e->getMessage(),
                    ];
                    $results['invalid']++;
                }
            }
        }

        return $results;
    }

    /**
     * 批量清理無效資料
     * 
     * @param int $batchSize 批次大小
     * @return array 清理結果
     */
    public function batchCleanupInvalidData(int $batchSize = self::DEFAULT_BATCH_SIZE): array
    {
        $results = [
            'cleaned_dependencies' => 0,
            'cleaned_role_permissions' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        
        try {
            // 清理無效的權限依賴關係
            $results['cleaned_dependencies'] = \App\Models\PermissionDependency::cleanupInvalidDependencies();
            
            // 清理無效的角色權限關聯
            $results['cleaned_role_permissions'] = DB::table('role_permissions as rp')
                                                    ->leftJoin('roles as r', 'rp.role_id', '=', 'r.id')
                                                    ->leftJoin('permissions as p', 'rp.permission_id', '=', 'p.id')
                                                    ->where(function ($query) {
                                                        $query->whereNull('r.id')->orWhereNull('p.id');
                                                    })
                                                    ->delete();
            
            DB::commit();
            
            // 清除相關快取
            $this->cacheService->clearPermissionCache();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            Log::error('批量清理無效資料失敗', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * 取得批量處理進度
     * 
     * @param int $total 總數
     * @param int $processed 已處理數
     * @return array 進度資訊
     */
    public function getProgress(int $total, int $processed): array
    {
        $percentage = $total > 0 ? round(($processed / $total) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'processed' => $processed,
            'remaining' => $total - $processed,
            'percentage' => $percentage,
            'is_complete' => $processed >= $total,
        ];
    }
}