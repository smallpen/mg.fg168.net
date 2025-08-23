<?php

namespace App\Repositories\Contracts;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 活動記錄資料存取層介面
 * 
 * 定義活動記錄相關的資料庫操作方法，包括查詢、篩選、分頁和統計功能
 */
interface ActivityRepositoryInterface
{
    /**
     * 取得分頁的活動記錄，支援搜尋和篩選
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function getPaginatedActivities(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 根據 ID 取得活動記錄
     *
     * @param int $id 活動記錄 ID
     * @return Activity|null
     */
    public function getActivityById(int $id): ?Activity;

    /**
     * 取得相關活動記錄
     *
     * @param Activity $activity 參考活動記錄
     * @return Collection
     */
    public function getRelatedActivities(Activity $activity): Collection;

    /**
     * 取得活動統計資料
     *
     * @param string $timeRange 時間範圍 (1d, 7d, 30d, 90d)
     * @return array
     */
    public function getActivityStats(string $timeRange): array;

    /**
     * 取得安全事件記錄
     *
     * @param string $timeRange 時間範圍
     * @return Collection
     */
    public function getSecurityEvents(string $timeRange): Collection;

    /**
     * 取得最活躍使用者排行榜
     *
     * @param string $timeRange 時間範圍
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getTopUsers(string $timeRange, int $limit = 10): Collection;

    /**
     * 匯出活動記錄
     *
     * @param array $filters 篩選條件
     * @param string $format 匯出格式 (csv, json, pdf)
     * @return string 匯出檔案路徑
     */
    public function exportActivities(array $filters, string $format): string;

    /**
     * 清理舊的活動記錄
     *
     * @param int $daysToKeep 保留天數
     * @return int 清理的記錄數量
     */
    public function cleanupOldActivities(int $daysToKeep): int;

    /**
     * 驗證活動記錄完整性
     *
     * @return array 驗證結果
     */
    public function verifyIntegrity(): array;

    /**
     * 建立活動記錄備份
     *
     * @param string $timeRange 時間範圍
     * @return string 備份檔案路徑
     */
    public function createBackup(string $timeRange): string;

    /**
     * 搜尋活動記錄
     *
     * @param string $query 搜尋關鍵字
     * @param array $filters 額外篩選條件
     * @return Collection
     */
    public function searchActivities(string $query, array $filters = []): Collection;

    /**
     * 根據使用者取得活動記錄
     *
     * @param int $userId 使用者 ID
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getActivitiesByUser(int $userId, int $limit = 20): Collection;

    /**
     * 根據類型取得活動記錄
     *
     * @param string $type 活動類型
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getActivitiesByType(string $type, int $limit = 20): Collection;

    /**
     * 根據模組取得活動記錄
     *
     * @param string $module 模組名稱
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getActivitiesByModule(string $module, int $limit = 20): Collection;

    /**
     * 取得最近的活動記錄
     *
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getRecentActivities(int $limit = 20): Collection;

    /**
     * 取得今日活動統計
     *
     * @return array
     */
    public function getTodayStats(): array;

    /**
     * 取得活動趨勢資料
     *
     * @param string $timeRange 時間範圍
     * @param string $groupBy 分組方式 (hour, day, week, month)
     * @return array
     */
    public function getActivityTrends(string $timeRange, string $groupBy = 'day'): array;

    /**
     * 批量建立活動記錄
     *
     * @param array $activities 活動記錄陣列
     * @return bool
     */
    public function createBatch(array $activities): bool;

    /**
     * 檢查可疑活動模式
     *
     * @param int $userId 使用者 ID
     * @param string $timeRange 時間範圍
     * @return array
     */
    public function detectSuspiciousPatterns(int $userId, string $timeRange): array;
}