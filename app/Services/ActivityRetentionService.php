<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRetentionPolicy;
use App\Models\ActivityCleanupLog;
use App\Models\ArchivedActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * 活動記錄保留服務
 * 
 * 處理活動記錄的保留政策、自動清理和歸檔功能
 */
class ActivityRetentionService
{
    /**
     * 批次處理大小
     */
    protected int $batchSize = 100;

    /**
     * 執行所有啟用的保留政策
     *
     * @param bool $dryRun 是否為測試執行（不實際刪除資料）
     * @return array 執行結果摘要
     */
    public function executeAllPolicies(bool $dryRun = false): array
    {
        $policies = ActivityRetentionPolicy::active()
            ->byPriority()
            ->get();

        $results = [];
        $totalProcessed = 0;
        $totalDeleted = 0;
        $totalArchived = 0;

        foreach ($policies as $policy) {
            try {
                $result = $this->executePolicy($policy, $dryRun);
                $results[] = $result;
                
                $totalProcessed += $result['records_processed'];
                $totalDeleted += $result['records_deleted'];
                $totalArchived += $result['records_archived'];
                
            } catch (Exception $e) {
                Log::error("執行保留政策失敗: {$policy->name}", [
                    'policy_id' => $policy->id,
                    'error' => $e->getMessage(),
                ]);
                
                $results[] = [
                    'policy_id' => $policy->id,
                    'policy_name' => $policy->name,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'records_processed' => 0,
                    'records_deleted' => 0,
                    'records_archived' => 0,
                ];
            }
        }

        return [
            'total_policies' => count($policies),
            'successful_policies' => count(array_filter($results, fn($r) => $r['status'] === 'completed')),
            'failed_policies' => count(array_filter($results, fn($r) => $r['status'] === 'failed')),
            'total_records_processed' => $totalProcessed,
            'total_records_deleted' => $totalDeleted,
            'total_records_archived' => $totalArchived,
            'policy_results' => $results,
            'executed_at' => Carbon::now()->toDateTimeString(),
            'dry_run' => $dryRun,
        ];
    }

    /**
     * 執行單一保留政策
     *
     * @param ActivityRetentionPolicy $policy
     * @param bool $dryRun
     * @return array
     */
    public function executePolicy(ActivityRetentionPolicy $policy, bool $dryRun = false): array
    {
        // 建立清理日誌
        $cleanupLog = ActivityCleanupLog::createLog([
            'policy_id' => $policy->id,
            'type' => ActivityCleanupLog::TYPE_AUTOMATIC,
            'action' => $policy->action,
            'activity_type' => $policy->activity_type,
            'module' => $policy->module,
            'date_from' => $policy->getExpiryDate()->toDateString(),
            'date_to' => Carbon::now()->toDateString(),
            'executed_by' => auth()->id() ?? 1,
        ]);

        try {
            // 取得適用的活動記錄
            $query = $policy->getApplicableActivitiesQuery();
            $totalRecords = $query->count();

            if ($totalRecords === 0) {
                $cleanupLog->markAsCompleted([
                    'message' => '沒有符合條件的記錄需要處理',
                ]);

                return [
                    'policy_id' => $policy->id,
                    'policy_name' => $policy->name,
                    'status' => 'completed',
                    'records_processed' => 0,
                    'records_deleted' => 0,
                    'records_archived' => 0,
                    'message' => '沒有符合條件的記錄',
                ];
            }

            $processed = 0;
            $deleted = 0;
            $archived = 0;
            $archivePath = null;

            // 如果是歸檔操作，先建立歸檔檔案
            if ($policy->action === ActivityRetentionPolicy::ACTION_ARCHIVE && !$dryRun) {
                $archivePath = $this->createArchiveFile($policy, $query);
                $cleanupLog->update(['archive_path' => $archivePath]);
            }

            // 分批處理記錄
            $query->chunk($this->batchSize, function ($activities) use (
                $policy, 
                $dryRun, 
                &$processed, 
                &$deleted, 
                &$archived, 
                $cleanupLog
            ) {
                if ($policy->action === ActivityRetentionPolicy::ACTION_ARCHIVE) {
                    if (!$dryRun) {
                        // 建立歸檔記錄
                        $archivedCount = ArchivedActivity::createBatchFromActivities(
                            $activities, 
                            "保留政策歸檔: {$policy->name}",
                            auth()->id() ?? 1
                        );
                        $archived += $archivedCount;
                        
                        // 刪除原始記錄
                        Activity::whereIn('id', $activities->pluck('id'))->delete();
                        $deleted += $activities->count();
                    }
                } else {
                    // 直接刪除
                    if (!$dryRun) {
                        Activity::whereIn('id', $activities->pluck('id'))->delete();
                        $deleted += $activities->count();
                    }
                }

                $processed += $activities->count();
                
                // 更新進度
                $cleanupLog->updateProgress($processed, $deleted, $archived);
            });

            // 更新政策執行時間
            if (!$dryRun) {
                $policy->markAsExecuted();
            }

            // 標記清理日誌為完成
            $cleanupLog->markAsCompleted([
                'total_records_found' => $totalRecords,
                'policy_name' => $policy->name,
                'action' => $policy->action_text,
                'dry_run' => $dryRun,
            ]);

            return [
                'policy_id' => $policy->id,
                'policy_name' => $policy->name,
                'status' => 'completed',
                'records_processed' => $processed,
                'records_deleted' => $deleted,
                'records_archived' => $archived,
                'archive_path' => $archivePath,
                'cleanup_log_id' => $cleanupLog->id,
            ];

        } catch (Exception $e) {
            $cleanupLog->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * 手動執行清理操作
     *
     * @param array $criteria 清理條件
     * @param string $action 動作 (delete|archive)
     * @param bool $dryRun 是否為測試執行
     * @return array
     */
    public function manualCleanup(array $criteria, string $action = 'archive', bool $dryRun = false): array
    {
        // 建立清理日誌
        $cleanupLog = ActivityCleanupLog::createLog([
            'type' => ActivityCleanupLog::TYPE_MANUAL,
            'action' => $action,
            'activity_type' => $criteria['activity_type'] ?? null,
            'module' => $criteria['module'] ?? null,
            'date_from' => $criteria['date_from'] ?? Carbon::now()->subDays(90)->toDateString(),
            'date_to' => $criteria['date_to'] ?? Carbon::now()->toDateString(),
            'executed_by' => auth()->id() ?? 1,
        ]);

        try {
            // 建立查詢
            $query = Activity::query();

            // 應用篩選條件
            if (!empty($criteria['date_from'])) {
                $query->where('created_at', '>=', Carbon::parse($criteria['date_from']));
            }

            if (!empty($criteria['date_to'])) {
                $query->where('created_at', '<=', Carbon::parse($criteria['date_to'])->endOfDay());
            }

            if (!empty($criteria['activity_type'])) {
                $query->where('type', $criteria['activity_type']);
            }

            if (!empty($criteria['module'])) {
                $query->where('module', $criteria['module']);
            }

            if (!empty($criteria['user_id'])) {
                $query->where('user_id', $criteria['user_id']);
            }

            if (isset($criteria['risk_level_min'])) {
                $query->where('risk_level', '>=', $criteria['risk_level_min']);
            }

            if (isset($criteria['risk_level_max'])) {
                $query->where('risk_level', '<=', $criteria['risk_level_max']);
            }

            $totalRecords = $query->count();

            if ($totalRecords === 0) {
                $cleanupLog->markAsCompleted([
                    'message' => '沒有符合條件的記錄需要處理',
                ]);

                return [
                    'status' => 'completed',
                    'records_processed' => 0,
                    'records_deleted' => 0,
                    'records_archived' => 0,
                    'message' => '沒有符合條件的記錄',
                ];
            }

            $processed = 0;
            $deleted = 0;
            $archived = 0;
            $archivePath = null;

            // 如果是歸檔操作，先建立歸檔檔案
            if ($action === 'archive' && !$dryRun) {
                $archivePath = $this->createManualArchiveFile($criteria, $query);
                $cleanupLog->update(['archive_path' => $archivePath]);
            }

            // 分批處理記錄
            $query->chunk($this->batchSize, function ($activities) use (
                $action, 
                $dryRun, 
                &$processed, 
                &$deleted, 
                &$archived, 
                $cleanupLog
            ) {
                if ($action === 'archive') {
                    if (!$dryRun) {
                        // 建立歸檔記錄
                        $archivedCount = ArchivedActivity::createBatchFromActivities(
                            $activities, 
                            '手動歸檔操作',
                            auth()->id() ?? 1
                        );
                        $archived += $archivedCount;
                        
                        // 刪除原始記錄
                        Activity::whereIn('id', $activities->pluck('id'))->delete();
                        $deleted += $activities->count();
                    }
                } else {
                    // 直接刪除
                    if (!$dryRun) {
                        Activity::whereIn('id', $activities->pluck('id'))->delete();
                        $deleted += $activities->count();
                    }
                }

                $processed += $activities->count();
                
                // 更新進度
                $cleanupLog->updateProgress($processed, $deleted, $archived);
            });

            // 標記清理日誌為完成
            $cleanupLog->markAsCompleted([
                'total_records_found' => $totalRecords,
                'action' => $action === 'archive' ? '歸檔' : '刪除',
                'criteria' => $criteria,
                'dry_run' => $dryRun,
            ]);

            return [
                'status' => 'completed',
                'records_processed' => $processed,
                'records_deleted' => $deleted,
                'records_archived' => $archived,
                'archive_path' => $archivePath,
                'cleanup_log_id' => $cleanupLog->id,
            ];

        } catch (Exception $e) {
            $cleanupLog->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * 建立歸檔檔案
     *
     * @param ActivityRetentionPolicy $policy
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return string
     */
    protected function createArchiveFile(ActivityRetentionPolicy $policy, $query): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "activity_archive_policy_{$policy->id}_{$timestamp}.json";
        $path = "archives/activities/{$filename}";

        $activities = $query->with(['user:id,username,name'])->get();

        $archiveData = [
            'archive_info' => [
                'created_at' => Carbon::now()->toDateTimeString(),
                'policy_id' => $policy->id,
                'policy_name' => $policy->name,
                'total_records' => $activities->count(),
                'date_range' => [
                    'from' => $policy->getExpiryDate()->toDateString(),
                    'to' => Carbon::now()->toDateString(),
                ],
                'criteria' => [
                    'activity_type' => $policy->activity_type,
                    'module' => $policy->module,
                    'conditions' => $policy->conditions,
                ],
            ],
            'activities' => $activities->toArray(),
        ];

        Storage::put($path, json_encode($archiveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * 建立手動歸檔檔案
     *
     * @param array $criteria
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return string
     */
    protected function createManualArchiveFile(array $criteria, $query): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "activity_archive_manual_{$timestamp}.json";
        $path = "archives/activities/{$filename}";

        $activities = $query->with(['user:id,username,name'])->get();

        $archiveData = [
            'archive_info' => [
                'created_at' => Carbon::now()->toDateTimeString(),
                'type' => 'manual',
                'total_records' => $activities->count(),
                'criteria' => $criteria,
                'executed_by' => auth()->user()?->name ?? '系統',
            ],
            'activities' => $activities->toArray(),
        ];

        Storage::put($path, json_encode($archiveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * 取得保留政策統計
     *
     * @return array
     */
    public function getPolicyStats(): array
    {
        $policies = ActivityRetentionPolicy::with(['cleanupLogs' => function ($query) {
            $query->recent(30)->completed();
        }])->get();

        $stats = [];

        foreach ($policies as $policy) {
            $policyStats = $policy->getStats();
            $recentLogs = $policy->cleanupLogs;

            $stats[] = [
                'policy' => [
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'is_active' => $policy->is_active,
                    'action' => $policy->action_text,
                    'retention_days' => $policy->retention_days,
                    'scope' => $policy->scope_description,
                ],
                'stats' => $policyStats,
                'recent_executions' => $recentLogs->count(),
                'total_processed_30d' => $recentLogs->sum('records_processed'),
                'total_archived_30d' => $recentLogs->sum('records_archived'),
                'total_deleted_30d' => $recentLogs->sum('records_deleted'),
                'last_execution' => $recentLogs->first()?->started_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $stats;
    }

    /**
     * 取得清理歷史統計
     *
     * @param string $timeRange
     * @return array
     */
    public function getCleanupHistory(string $timeRange = '30d'): array
    {
        return ActivityCleanupLog::getCleanupStats($timeRange);
    }

    /**
     * 取得歸檔統計
     *
     * @param string $timeRange
     * @return array
     */
    public function getArchiveStats(string $timeRange = '30d'): array
    {
        return ArchivedActivity::getArchiveStats($timeRange);
    }

    /**
     * 預覽保留政策影響
     *
     * @param ActivityRetentionPolicy $policy
     * @return array
     */
    public function previewPolicyImpact(ActivityRetentionPolicy $policy): array
    {
        $query = $policy->getApplicableActivitiesQuery();
        
        $totalRecords = $query->count();
        $oldestRecord = $query->min('created_at');
        $newestRecord = $query->max('created_at');
        
        // 按類型分組統計
        $byType = $query->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->pluck('count', 'type')
            ->toArray();

        // 按模組分組統計
        $byModule = $query->selectRaw('module, COUNT(*) as count')
            ->whereNotNull('module')
            ->groupBy('module')
            ->orderBy('count', 'desc')
            ->pluck('count', 'module')
            ->toArray();

        // 按風險等級分組統計
        $byRiskLevel = $query->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->orderBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();

        return [
            'total_records' => $totalRecords,
            'estimated_size_mb' => round(($totalRecords * 2) / 1024, 2),
            'date_range' => [
                'oldest' => $oldestRecord,
                'newest' => $newestRecord,
            ],
            'breakdown' => [
                'by_type' => $byType,
                'by_module' => $byModule,
                'by_risk_level' => $byRiskLevel,
            ],
            'policy_info' => [
                'name' => $policy->name,
                'action' => $policy->action_text,
                'retention_days' => $policy->retention_days,
                'expiry_date' => $policy->getExpiryDate()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * 還原歸檔記錄
     *
     * @param array $archivedIds 歸檔記錄ID陣列
     * @return array
     */
    public function restoreArchivedActivities(array $archivedIds): array
    {
        $restored = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $archivedActivities = ArchivedActivity::whereIn('id', $archivedIds)->get();

            foreach ($archivedActivities as $archived) {
                try {
                    // 檢查原始記錄是否已存在
                    $existingActivity = Activity::find($archived->original_id);
                    if ($existingActivity) {
                        $errors[] = "記錄 ID {$archived->original_id} 已存在，跳過還原";
                        $failed++;
                        continue;
                    }

                    // 建立新的活動記錄
                    Activity::create([
                        'type' => $archived->type,
                        'event' => $archived->event,
                        'description' => $archived->description,
                        'module' => $archived->module,
                        'user_id' => $archived->user_id,
                        'subject_id' => $archived->subject_id,
                        'subject_type' => $archived->subject_type,
                        'properties' => $archived->properties,
                        'ip_address' => $archived->ip_address,
                        'user_agent' => $archived->user_agent,
                        'result' => $archived->result,
                        'risk_level' => $archived->risk_level,
                        'signature' => $archived->signature,
                        'created_at' => $archived->original_created_at,
                        'updated_at' => $archived->original_created_at,
                    ]);

                    // 刪除歸檔記錄
                    $archived->delete();
                    $restored++;

                } catch (Exception $e) {
                    $errors[] = "還原記錄 ID {$archived->id} 失敗: " . $e->getMessage();
                    $failed++;
                }
            }

            DB::commit();

            return [
                'status' => 'completed',
                'restored' => $restored,
                'failed' => $failed,
                'errors' => $errors,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 設定批次處理大小
     *
     * @param int $size
     * @return self
     */
    public function setBatchSize(int $size): self
    {
        $this->batchSize = $size;
        return $this;
    }
}