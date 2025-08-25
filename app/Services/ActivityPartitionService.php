<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Exception;

/**
 * 活動記錄分區管理服務
 * 
 * 管理活動記錄的資料分區，包含建立、維護、歸檔和清理功能
 */
class ActivityPartitionService
{
    /**
     * 分區表前綴
     */
    protected string $tablePrefix = 'activities_';

    /**
     * 主表名稱
     */
    protected string $mainTable = 'activities';

    /**
     * 分區保留月數
     */
    protected int $retentionMonths = 12;

    /**
     * 建立月份分區
     * 
     * @param Carbon $date 分區日期
     * @return bool
     */
    public function createMonthlyPartition(Carbon $date): bool
    {
        $partitionName = $this->getPartitionName($date);
        $tableName = $this->tablePrefix . $date->format('Y_m');
        
        try {
            // 記錄開始維護
            $logId = $this->logMaintenanceStart($partitionName, 'create');
            
            // 檢查分區是否已存在
            if ($this->partitionExists($partitionName)) {
                $this->logMaintenanceComplete($logId, 'success', '分區已存在');
                return true;
            }
            
            // 建立分區表
            $this->createPartitionTable($tableName, $date);
            
            // 記錄分區資訊
            $this->recordPartition($partitionName, $date, $tableName);
            
            // 記錄完成維護
            $this->logMaintenanceComplete($logId, 'success', "成功建立分區 {$partitionName}");
            
            Log::info("成功建立活動記錄分區", [
                'partition_name' => $partitionName,
                'table_name' => $tableName,
                'date' => $date->toDateString(),
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logMaintenanceComplete($logId ?? null, 'failed', $e->getMessage());
            
            Log::error("建立活動記錄分區失敗", [
                'partition_name' => $partitionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * 建立分區表
     * 
     * @param string $tableName
     * @param Carbon $date
     * @return void
     */
    protected function createPartitionTable(string $tableName, Carbon $date): void
    {
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        
        // 建立與主表相同結構的分區表
        DB::statement("
            CREATE TABLE {$tableName} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                type VARCHAR(255) NOT NULL,
                event VARCHAR(255) NULL,
                description VARCHAR(255) NOT NULL,
                module VARCHAR(255) NULL,
                user_id BIGINT UNSIGNED NULL,
                subject_id BIGINT UNSIGNED NULL,
                subject_type VARCHAR(255) NULL,
                properties JSON NULL,
                ip_address VARCHAR(255) NULL,
                user_agent TEXT NULL,
                result VARCHAR(255) NOT NULL DEFAULT 'success',
                risk_level TINYINT NOT NULL DEFAULT 1,
                signature VARCHAR(255) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (id, created_at),
                INDEX idx_user_created (user_id, created_at),
                INDEX idx_type_created (type, created_at),
                INDEX idx_module_created (module, created_at),
                INDEX idx_result_created (result, created_at),
                INDEX idx_risk_created (risk_level, created_at),
                INDEX idx_ip_created (ip_address, created_at),
                INDEX idx_user_type_time (user_id, type, created_at),
                INDEX idx_subject_time (subject_type, subject_id, created_at),
                INDEX idx_security_events (type, risk_level, created_at),
                INDEX idx_stats (module, type, created_at),
                CONSTRAINT chk_date_range CHECK (
                    created_at >= '{$startDate->toDateTimeString()}' AND 
                    created_at < '{$endDate->addDay()->toDateTimeString()}'
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * 跨分區查詢活動記錄
     * 
     * @param array $filters 查詢條件
     * @return \Illuminate\Database\Query\Builder
     */
    public function queryAcrossPartitions(array $filters = []): \Illuminate\Database\Query\Builder
    {
        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : null;
        $dateTo = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : null;
        
        // 取得需要查詢的分區
        $partitions = $this->getPartitionsForDateRange($dateFrom, $dateTo);
        
        if (empty($partitions)) {
            // 如果沒有分區，查詢主表
            return DB::table($this->mainTable);
        }
        
        // 建立 UNION 查詢
        $query = null;
        
        foreach ($partitions as $partition) {
            $tableName = $this->tablePrefix . $partition['partition_suffix'];
            
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            
            $partitionQuery = DB::table($tableName);
            
            if ($query === null) {
                $query = $partitionQuery;
            } else {
                $query = $query->union($partitionQuery);
            }
        }
        
        // 如果沒有找到任何分區表，回退到主表
        if ($query === null) {
            $query = DB::table($this->mainTable);
        }
        
        return $query;
    }

    /**
     * 歸檔舊分區
     * 
     * @param Carbon $beforeDate 歸檔此日期之前的分區
     * @return int 歸檔的分區數量
     */
    public function archiveOldPartitions(Carbon $beforeDate): int
    {
        $archivedCount = 0;
        
        // 取得需要歸檔的分區
        $partitions = DB::table('activity_partitions')
            ->where('end_date', '<', $beforeDate)
            ->where('is_active', true)
            ->get();
        
        foreach ($partitions as $partition) {
            try {
                $logId = $this->logMaintenanceStart($partition->partition_name, 'archive');
                
                // 建立歸檔表
                $archiveTableName = $partition->table_name . '_archived';
                $this->createArchiveTable($partition->table_name, $archiveTableName);
                
                // 移動資料到歸檔表
                $recordCount = $this->moveDataToArchive($partition->table_name, $archiveTableName);
                
                // 更新分區狀態
                DB::table('activity_partitions')
                    ->where('id', $partition->id)
                    ->update([
                        'is_active' => false,
                        'record_count' => $recordCount,
                        'last_maintenance_at' => now(),
                        'updated_at' => now(),
                    ]);
                
                $this->logMaintenanceComplete($logId, 'success', "歸檔 {$recordCount} 筆記錄");
                $archivedCount++;
                
            } catch (Exception $e) {
                $this->logMaintenanceComplete($logId ?? null, 'failed', $e->getMessage());
                
                Log::error("歸檔分區失敗", [
                    'partition_name' => $partition->partition_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $archivedCount;
    }

    /**
     * 清理過期分區
     * 
     * @param Carbon $beforeDate 清理此日期之前的分區
     * @return int 清理的分區數量
     */
    public function cleanupExpiredPartitions(Carbon $beforeDate): int
    {
        $cleanedCount = 0;
        
        // 取得需要清理的分區
        $partitions = DB::table('activity_partitions')
            ->where('end_date', '<', $beforeDate)
            ->where('is_active', false)
            ->get();
        
        foreach ($partitions as $partition) {
            try {
                $logId = $this->logMaintenanceStart($partition->partition_name, 'drop');
                
                // 刪除分區表
                if (Schema::hasTable($partition->table_name)) {
                    Schema::drop($partition->table_name);
                }
                
                // 刪除歸檔表（如果存在）
                $archiveTableName = $partition->table_name . '_archived';
                if (Schema::hasTable($archiveTableName)) {
                    Schema::drop($archiveTableName);
                }
                
                // 刪除分區記錄
                DB::table('activity_partitions')->where('id', $partition->id)->delete();
                
                $this->logMaintenanceComplete($logId, 'success', "成功清理分區");
                $cleanedCount++;
                
            } catch (Exception $e) {
                $this->logMaintenanceComplete($logId ?? null, 'failed', $e->getMessage());
                
                Log::error("清理分區失敗", [
                    'partition_name' => $partition->partition_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $cleanedCount;
    }

    /**
     * 優化分區表
     * 
     * @param string $partitionName
     * @return bool
     */
    public function optimizePartition(string $partitionName): bool
    {
        try {
            $logId = $this->logMaintenanceStart($partitionName, 'optimize');
            
            $partition = DB::table('activity_partitions')
                ->where('partition_name', $partitionName)
                ->first();
            
            if (!$partition) {
                throw new Exception("分區不存在: {$partitionName}");
            }
            
            $tableName = $partition->table_name;
            
            // 執行表優化
            DB::statement("OPTIMIZE TABLE {$tableName}");
            
            // 分析表統計資訊
            DB::statement("ANALYZE TABLE {$tableName}");
            
            // 更新記錄數量
            $recordCount = DB::table($tableName)->count();
            
            DB::table('activity_partitions')
                ->where('id', $partition->id)
                ->update([
                    'record_count' => $recordCount,
                    'last_maintenance_at' => now(),
                    'updated_at' => now(),
                ]);
            
            $this->logMaintenanceComplete($logId, 'success', "優化完成，記錄數: {$recordCount}");
            
            return true;
            
        } catch (Exception $e) {
            $this->logMaintenanceComplete($logId ?? null, 'failed', $e->getMessage());
            
            Log::error("優化分區失敗", [
                'partition_name' => $partitionName,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 取得分區統計資訊
     * 
     * @return array
     */
    public function getPartitionStats(): array
    {
        $partitions = DB::table('activity_partitions')
            ->orderBy('start_date', 'desc')
            ->get();
        
        $stats = [
            'total_partitions' => $partitions->count(),
            'active_partitions' => $partitions->where('is_active', true)->count(),
            'archived_partitions' => $partitions->where('is_active', false)->count(),
            'total_records' => $partitions->sum('record_count'),
            'partitions' => [],
        ];
        
        foreach ($partitions as $partition) {
            $stats['partitions'][] = [
                'name' => $partition->partition_name,
                'start_date' => $partition->start_date,
                'end_date' => $partition->end_date,
                'is_active' => $partition->is_active,
                'record_count' => $partition->record_count,
                'last_maintenance' => $partition->last_maintenance_at,
            ];
        }
        
        return $stats;
    }

    /**
     * 自動維護分區
     * 
     * @return array 維護結果
     */
    public function autoMaintenance(): array
    {
        $results = [
            'created_partitions' => 0,
            'archived_partitions' => 0,
            'cleaned_partitions' => 0,
            'optimized_partitions' => 0,
            'errors' => [],
        ];
        
        try {
            // 建立未來 2 個月的分區
            for ($i = 0; $i < 2; $i++) {
                $date = now()->addMonths($i);
                if ($this->createMonthlyPartition($date)) {
                    $results['created_partitions']++;
                }
            }
            
            // 歸檔超過保留期的分區
            $archiveDate = now()->subMonths($this->retentionMonths);
            $results['archived_partitions'] = $this->archiveOldPartitions($archiveDate);
            
            // 清理超過保留期 + 3 個月的分區
            $cleanupDate = now()->subMonths($this->retentionMonths + 3);
            $results['cleaned_partitions'] = $this->cleanupExpiredPartitions($cleanupDate);
            
            // 優化活躍分區
            $activePartitions = DB::table('activity_partitions')
                ->where('is_active', true)
                ->where('last_maintenance_at', '<', now()->subDays(7))
                ->orWhereNull('last_maintenance_at')
                ->get();
            
            foreach ($activePartitions as $partition) {
                if ($this->optimizePartition($partition->partition_name)) {
                    $results['optimized_partitions']++;
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            
            Log::error("自動維護分區失敗", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $results;
    }

    /**
     * 取得分區名稱
     * 
     * @param Carbon $date
     * @return string
     */
    protected function getPartitionName(Carbon $date): string
    {
        return $this->tablePrefix . $date->format('Y_m');
    }

    /**
     * 檢查分區是否存在
     * 
     * @param string $partitionName
     * @return bool
     */
    protected function partitionExists(string $partitionName): bool
    {
        return DB::table('activity_partitions')
            ->where('partition_name', $partitionName)
            ->exists();
    }

    /**
     * 記錄分區資訊
     * 
     * @param string $partitionName
     * @param Carbon $date
     * @param string $tableName
     * @return void
     */
    protected function recordPartition(string $partitionName, Carbon $date, string $tableName): void
    {
        DB::table('activity_partitions')->updateOrInsert(
            ['partition_name' => $partitionName],
            [
                'start_date' => $date->copy()->startOfMonth(),
                'end_date' => $date->copy()->endOfMonth(),
                'table_name' => $tableName,
                'is_active' => true,
                'record_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * 取得日期範圍內的分區
     * 
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @return array
     */
    protected function getPartitionsForDateRange(?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = DB::table('activity_partitions');
        
        if ($dateFrom) {
            $query->where('end_date', '>=', $dateFrom->toDateString());
        }
        
        if ($dateTo) {
            $query->where('start_date', '<=', $dateTo->toDateString());
        }
        
        return $query->get()->map(function ($partition) {
            return [
                'partition_name' => $partition->partition_name,
                'partition_suffix' => str_replace($this->tablePrefix, '', $partition->partition_name),
                'table_name' => $partition->table_name,
            ];
        })->toArray();
    }

    /**
     * 建立歸檔表
     * 
     * @param string $sourceTable
     * @param string $archiveTable
     * @return void
     */
    protected function createArchiveTable(string $sourceTable, string $archiveTable): void
    {
        DB::statement("CREATE TABLE {$archiveTable} LIKE {$sourceTable}");
    }

    /**
     * 移動資料到歸檔表
     * 
     * @param string $sourceTable
     * @param string $archiveTable
     * @return int
     */
    protected function moveDataToArchive(string $sourceTable, string $archiveTable): int
    {
        // 複製資料
        DB::statement("INSERT INTO {$archiveTable} SELECT * FROM {$sourceTable}");
        
        // 取得記錄數量
        $recordCount = DB::table($sourceTable)->count();
        
        // 清空原表（保留結構）
        DB::statement("TRUNCATE TABLE {$sourceTable}");
        
        return $recordCount;
    }

    /**
     * 記錄維護開始
     * 
     * @param string $partitionName
     * @param string $operation
     * @return int
     */
    protected function logMaintenanceStart(string $partitionName, string $operation): int
    {
        return DB::table('partition_maintenance_logs')->insertGetId([
            'partition_name' => $partitionName,
            'operation' => $operation,
            'status' => 'in_progress',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 記錄維護完成
     * 
     * @param int|null $logId
     * @param string $status
     * @param string|null $details
     * @return void
     */
    protected function logMaintenanceComplete(?int $logId, string $status, ?string $details = null): void
    {
        if (!$logId) {
            return;
        }
        
        DB::table('partition_maintenance_logs')
            ->where('id', $logId)
            ->update([
                'status' => $status,
                'details' => $details,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}