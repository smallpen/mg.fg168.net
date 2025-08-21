<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * 權限依賴關係樞紐模型
 * 
 * 管理權限之間的依賴關係，包含循環依賴檢查
 */
class PermissionDependency extends Pivot
{
    /**
     * 資料表名稱
     *
     * @var string
     */
    protected $table = 'permission_dependencies';

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'permission_id',
        'depends_on_permission_id',
    ];

    /**
     * 權限關聯
     * 
     * @return BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    /**
     * 依賴權限關聯
     * 
     * @return BelongsTo
     */
    public function dependency(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'depends_on_permission_id');
    }

    /**
     * 驗證是否會產生循環依賴
     * 
     * @param int $permissionId 權限 ID
     * @param int $dependencyId 依賴權限 ID
     * @return bool true 表示沒有循環依賴，false 表示有循環依賴
     */
    public static function validateNoCycle(int $permissionId, int $dependencyId): bool
    {
        // 不能依賴自己
        if ($permissionId === $dependencyId) {
            return false;
        }

        // 檢查是否會形成循環依賴
        return !static::hasDependencyPath($dependencyId, $permissionId);
    }

    /**
     * 檢查從起始權限到目標權限是否存在依賴路徑
     * 
     * @param int $fromId 起始權限 ID
     * @param int $toId 目標權限 ID
     * @param array $visited 已訪問的權限 ID（避免無限迴圈）
     * @return bool
     */
    public static function hasDependencyPath(int $fromId, int $toId, array $visited = []): bool
    {
        // 避免無限迴圈
        if (in_array($fromId, $visited)) {
            return false;
        }

        $visited[] = $fromId;

        // 取得直接依賴
        $directDependencies = DB::table('permission_dependencies')
                               ->where('permission_id', $fromId)
                               ->pluck('depends_on_permission_id')
                               ->toArray();

        // 檢查直接依賴
        if (in_array($toId, $directDependencies)) {
            return true;
        }

        // 遞迴檢查間接依賴
        foreach ($directDependencies as $dependencyId) {
            if (static::hasDependencyPath($dependencyId, $toId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得從起始權限到目標權限的依賴路徑
     * 
     * @param int $fromId 起始權限 ID
     * @param int $toId 目標權限 ID
     * @return array 依賴路徑，如果沒有路徑則返回空陣列
     */
    public static function getDependencyPath(int $fromId, int $toId): array
    {
        return static::findPath($fromId, $toId, []);
    }

    /**
     * 遞迴尋找依賴路徑
     * 
     * @param int $fromId 起始權限 ID
     * @param int $toId 目標權限 ID
     * @param array $path 當前路徑
     * @return array
     */
    private static function findPath(int $fromId, int $toId, array $path): array
    {
        // 避免循環
        if (in_array($fromId, $path)) {
            return [];
        }

        $path[] = $fromId;

        // 找到目標
        if ($fromId === $toId) {
            return $path;
        }

        // 取得直接依賴
        $directDependencies = DB::table('permission_dependencies')
                               ->where('permission_id', $fromId)
                               ->pluck('depends_on_permission_id')
                               ->toArray();

        // 遞迴搜尋
        foreach ($directDependencies as $dependencyId) {
            $result = static::findPath($dependencyId, $toId, $path);
            if (!empty($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * 取得權限的所有直接依賴
     * 
     * @param int $permissionId 權限 ID
     * @return array
     */
    public static function getDirectDependencies(int $permissionId): array
    {
        return DB::table('permission_dependencies')
                 ->where('permission_id', $permissionId)
                 ->pluck('depends_on_permission_id')
                 ->toArray();
    }

    /**
     * 取得依賴特定權限的所有權限
     * 
     * @param int $permissionId 權限 ID
     * @return array
     */
    public static function getDirectDependents(int $permissionId): array
    {
        return DB::table('permission_dependencies')
                 ->where('depends_on_permission_id', $permissionId)
                 ->pluck('permission_id')
                 ->toArray();
    }

    /**
     * 取得權限的完整依賴樹（包含間接依賴）
     * 
     * @param int $permissionId 權限 ID
     * @return array
     */
    public static function getFullDependencyTree(int $permissionId): array
    {
        return static::buildDependencyTree($permissionId, []);
    }

    /**
     * 遞迴建立依賴樹
     * 
     * @param int $permissionId 權限 ID
     * @param array $visited 已訪問的權限 ID
     * @return array
     */
    private static function buildDependencyTree(int $permissionId, array $visited): array
    {
        if (in_array($permissionId, $visited)) {
            return [];
        }

        $visited[] = $permissionId;
        $tree = [];

        $directDependencies = static::getDirectDependencies($permissionId);

        foreach ($directDependencies as $dependencyId) {
            $tree[$dependencyId] = static::buildDependencyTree($dependencyId, $visited);
        }

        return $tree;
    }

    /**
     * 取得權限的完整被依賴樹（包含間接被依賴）
     * 
     * @param int $permissionId 權限 ID
     * @return array
     */
    public static function getFullDependentTree(int $permissionId): array
    {
        return static::buildDependentTree($permissionId, []);
    }

    /**
     * 遞迴建立被依賴樹
     * 
     * @param int $permissionId 權限 ID
     * @param array $visited 已訪問的權限 ID
     * @return array
     */
    private static function buildDependentTree(int $permissionId, array $visited): array
    {
        if (in_array($permissionId, $visited)) {
            return [];
        }

        $visited[] = $permissionId;
        $tree = [];

        $directDependents = static::getDirectDependents($permissionId);

        foreach ($directDependents as $dependentId) {
            $tree[$dependentId] = static::buildDependentTree($dependentId, $visited);
        }

        return $tree;
    }

    /**
     * 檢查權限依賴關係的完整性
     * 
     * @return array 返回檢查結果
     */
    public static function validateIntegrity(): array
    {
        $issues = [];

        // 檢查循環依賴
        $allDependencies = DB::table('permission_dependencies')->get();
        
        foreach ($allDependencies as $dependency) {
            if (static::hasDependencyPath($dependency->depends_on_permission_id, $dependency->permission_id)) {
                $issues[] = [
                    'type' => 'circular_dependency',
                    'permission_id' => $dependency->permission_id,
                    'depends_on_permission_id' => $dependency->depends_on_permission_id,
                    'message' => "權限 {$dependency->permission_id} 和 {$dependency->depends_on_permission_id} 之間存在循環依賴",
                ];
            }
        }

        // 檢查孤立的依賴關係（依賴的權限不存在）
        $orphanedDependencies = DB::table('permission_dependencies as pd')
                                  ->leftJoin('permissions as p1', 'pd.permission_id', '=', 'p1.id')
                                  ->leftJoin('permissions as p2', 'pd.depends_on_permission_id', '=', 'p2.id')
                                  ->whereNull('p1.id')
                                  ->orWhereNull('p2.id')
                                  ->select('pd.*')
                                  ->get();

        foreach ($orphanedDependencies as $orphaned) {
            $issues[] = [
                'type' => 'orphaned_dependency',
                'permission_id' => $orphaned->permission_id,
                'depends_on_permission_id' => $orphaned->depends_on_permission_id,
                'message' => "依賴關係中的權限不存在",
            ];
        }

        return [
            'is_valid' => empty($issues),
            'issues' => $issues,
            'total_issues' => count($issues),
        ];
    }

    /**
     * 清理無效的依賴關係
     * 
     * @return int 清理的記錄數量
     */
    public static function cleanupInvalidDependencies(): int
    {
        // 清理孤立的依賴關係
        $deletedCount = DB::table('permission_dependencies as pd')
                          ->leftJoin('permissions as p1', 'pd.permission_id', '=', 'p1.id')
                          ->leftJoin('permissions as p2', 'pd.depends_on_permission_id', '=', 'p2.id')
                          ->where(function ($query) {
                              $query->whereNull('p1.id')->orWhereNull('p2.id');
                          })
                          ->delete();

        return $deletedCount;
    }
}