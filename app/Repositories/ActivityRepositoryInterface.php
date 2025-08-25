<?php

namespace App\Repositories;

use App\Models\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 活動記錄儲存庫介面
 */
interface ActivityRepositoryInterface
{
    /**
     * 取得分頁的活動記錄
     */
    public function getPaginatedActivities(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 根據 ID 取得活動記錄
     */
    public function getActivityById(int $id): ?Activity;

    /**
     * 取得相關活動記錄
     */
    public function getRelatedActivities(Activity $activity): Collection;

    /**
     * 取得活動統計資料
     */
    public function getActivityStats(string $timeRange): array;

    /**
     * 取得安全事件
     */
    public function getSecurityEvents(string $timeRange): Collection;

    /**
     * 取得最活躍使用者
     */
    public function getTopUsers(string $timeRange, int $limit = 10): Collection;

    /**
     * 匯出活動記錄
     */
    public function exportActivities(array $filters, string $format): string;

    /**
     * 清理舊活動記錄
     */
    public function cleanupOldActivities(int $daysToKeep): int;

    /**
     * 驗證完整性
     */
    public function verifyIntegrity(): array;

    /**
     * 建立備份
     */
    public function createBackup(string $timeRange): string;

    /**
     * 搜尋活動記錄
     */
    public function searchActivities(string $query, array $filters = []): Collection;

    /**
     * 批量刪除活動記錄
     */
    public function bulkDelete(array $activityIds): int;

    /**
     * 批量歸檔活動記錄
     */
    public function bulkArchive(array $activityIds): int;

    /**
     * 批量匯出活動記錄
     */
    public function bulkExport(array $activityIds): string;
}