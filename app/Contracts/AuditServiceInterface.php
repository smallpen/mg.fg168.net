<?php

namespace App\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 審計服務介面
 * 
 * 定義審計服務的標準方法
 */
interface AuditServiceInterface
{
    /**
     * 記錄操作日誌
     * 
     * @param string $action 操作類型
     * @param mixed $subject 操作對象
     * @param array $data 額外資料
     * @param mixed $user 操作使用者
     * @return void
     */
    public function log(string $action, $subject, array $data = [], $user = null): void;

    /**
     * 搜尋審計日誌
     * 
     * @param array $filters 篩選條件
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator;

    /**
     * 取得統計資料
     * 
     * @param int $days 統計天數
     * @return array
     */
    public function getStats(int $days = 30): array;

    /**
     * 清理舊日誌
     * 
     * @param int $daysToKeep 保留天數
     * @return int 刪除的記錄數量
     */
    public function cleanup(int $daysToKeep = 365): int;

    /**
     * 匯出日誌
     * 
     * @param array $filters 篩選條件
     * @return array
     */
    public function export(array $filters = []): array;
}