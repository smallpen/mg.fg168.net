<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

/**
 * 活動記錄壓縮和歸檔服務
 * 
 * 提供活動記錄的壓縮、歸檔、還原和清理功能
 */
class ActivityCompressionService
{
    /**
     * 壓縮閾值（天數）
     */
    protected int $compressionThreshold = 30;

    /**
     * 歸檔閾值（天數）
     */
    protected int $archiveThreshold = 90;

    /**
     * 刪除閾值（天數）
     */
    protected int $deleteThreshold = 365;

    /**
     * 批次處理大小
     */
    protected int $batchSize = 1000;

    /**
     * 壓縮格式
     */
    protected string $compressionFormat = 'gzip';

    /**
     * 歸檔儲存磁碟
     */
    protected string $archiveDisk = 'local';

    /**
     * 壓縮舊活動記錄
     * 
     * @param Carbon|null $beforeDate 壓縮此日期之前的記錄
     * @return array 壓縮結果
     */
    public function compressOldActivities(?Carbon $beforeDate = null): array
    {
        $beforeDate = $beforeDate ?? now()->subDays($this->compressionThreshold);
        
        $results = [
            'processed_records' => 0,
            'compressed_records' => 0,
            'saved_space' => 0,
            'compression_ratio' => 0,
            'errors' => [],
        ];
        
        try {
            Log::info("開始壓縮活動記錄", ['before_date' => $beforeDate->toDateString()]);
            
            // 取得需要壓縮的記錄
            $activities = DB::table('activities')
                ->where('created_at', '<', $beforeDate)
                ->whereNull('compressed_at')
                ->orderBy('created_at')
                ->get();
            
            $results['processed_records'] = $activities->count();
            
            if ($results['processed_records'] === 0) {
                Log::info("沒有需要壓縮的記錄");
                return $results;
            }
            
            // 分批處理記錄
            $batches = $activities->chunk($this->batchSize);
            
            foreach ($batches as $batch) {
                $batchResult = $this->compressBatch($batch);
                
                $results['compressed_records'] += $batchResult['compressed_count'];
                $results['saved_space'] += $batchResult['saved_space'];
                
                if (!empty($batchResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $batchResult['errors']);
                }
            }
            
            // 計算壓縮比率
            if ($results['processed_records'] > 0) {
                $results['compression_ratio'] = round(
                    ($results['compressed_records'] / $results['processed_records']) * 100, 
                    2
                );
            }
            
            Log::info("活動記錄壓縮完成", $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            
            Log::error("壓縮活動記錄失敗", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $results;
    }

    /**
     * 歸檔舊活動記錄
     * 
     * @param Carbon|null $beforeDate 歸檔此日期之前的記錄
     * @return array 歸檔結果
     */
    public function archiveOldActivities(?Carbon $beforeDate = null): array
    {
        $beforeDate = $beforeDate ?? now()->subDays($this->archiveThreshold);
        
        $results = [
            'processed_records' => 0,
            'archived_records' => 0,
            'archive_files' => 0,
            'total_size' => 0,
            'errors' => [],
        ];
        
        try {
            Log::info("開始歸檔活動記錄", ['before_date' => $beforeDate->toDateString()]);
            
            // 按月份分組歸檔
            $monthlyGroups = DB::table('activities')
                ->where('created_at', '<', $beforeDate)
                ->whereNull('archived_at')
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            foreach ($monthlyGroups as $group) {
                $monthResult = $this->archiveMonthlyRecords($group->year, $group->month);
                
                $results['processed_records'] += $group->count;
                $results['archived_records'] += $monthResult['archived_count'];
                $results['archive_files'] += $monthResult['file_count'];
                $results['total_size'] += $monthResult['file_size'];
                
                if (!empty($monthResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $monthResult['errors']);
                }
            }
            
            Log::info("活動記錄歸檔完成", $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            
            Log::error("歸檔活動記錄失敗", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $results;
    }

    /**
     * 還原歸檔記錄
     * 
     * @param string $archiveFile 歸檔檔案名稱
     * @return array 還原結果
     */
    public function restoreArchivedActivities(string $archiveFile): array
    {
        $results = [
            'restored_records' => 0,
            'skipped_records' => 0,
            'errors' => [],
        ];
        
        try {
            Log::info("開始還原歸檔記錄", ['archive_file' => $archiveFile]);
            
            // 檢查歸檔檔案是否存在
            if (!Storage::disk($this->archiveDisk)->exists("archives/{$archiveFile}")) {
                throw new Exception("歸檔檔案不存在: {$archiveFile}");
            }
            
            // 讀取並解壓縮歸檔檔案
            $compressedData = Storage::disk($this->archiveDisk)->get("archives/{$archiveFile}");
            $decompressedData = $this->decompress($compressedData);
            $records = json_decode($decompressedData, true);
            
            if (!$records) {
                throw new Exception("無法解析歸檔檔案內容");
            }
            
            // 分批還原記錄
            $batches = array_chunk($records, $this->batchSize);
            
            foreach ($batches as $batch) {
                $batchResult = $this->restoreBatch($batch);
                
                $results['restored_records'] += $batchResult['restored_count'];
                $results['skipped_records'] += $batchResult['skipped_count'];
                
                if (!empty($batchResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $batchResult['errors']);
                }
            }
            
            Log::info("歸檔記錄還原完成", $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            
            Log::error("還原歸檔記錄失敗", [
                'archive_file' => $archiveFile,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $results;
    }

    /**
     * 清理過期記錄
     * 
     * @param Carbon|null $beforeDate 清理此日期之前的記錄
     * @return array 清理結果
     */
    public function cleanupExpiredRecords(?Carbon $beforeDate = null): array
    {
        $beforeDate = $beforeDate ?? now()->subDays($this->deleteThreshold);
        
        $results = [
            'deleted_records' => 0,
            'deleted_archives' => 0,
            'freed_space' => 0,
            'errors' => [],
        ];
        
        try {
            Log::info("開始清理過期記錄", ['before_date' => $beforeDate->toDateString()]);
            
            // 刪除過期的活動記錄
            $deletedRecords = DB::table('activities')
                ->where('created_at', '<', $beforeDate)
                ->where('archived_at', '<', now()->subDays(30)) // 已歸檔超過30天
                ->delete();
            
            $results['deleted_records'] = $deletedRecords;
            
            // 清理過期的歸檔檔案
            $archiveCleanupResult = $this->cleanupExpiredArchives($beforeDate);
            $results['deleted_archives'] = $archiveCleanupResult['deleted_count'];
            $results['freed_space'] = $archiveCleanupResult['freed_space'];
            
            Log::info("過期記錄清理完成", $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            
            Log::error("清理過期記錄失敗", [
                'error' => $e->getMessage(),
            ]);
        }
        
        return $results;
    }

    /**
     * 取得壓縮和歸檔統計
     * 
     * @return array
     */
    public function getCompressionStats(): array
    {
        try {
            // 基本統計
            $totalRecords = DB::table('activities')->count();
            $compressedRecords = DB::table('activities')->whereNotNull('compressed_at')->count();
            $archivedRecords = DB::table('activities')->whereNotNull('archived_at')->count();
            
            // 歸檔檔案統計
            $archiveFiles = Storage::disk($this->archiveDisk)->files('archives');
            $totalArchiveSize = 0;
            
            foreach ($archiveFiles as $file) {
                $totalArchiveSize += Storage::disk($this->archiveDisk)->size($file);
            }
            
            // 壓縮比率統計
            $compressionRatio = $totalRecords > 0 ? 
                round(($compressedRecords / $totalRecords) * 100, 2) : 0;
            
            $archiveRatio = $totalRecords > 0 ? 
                round(($archivedRecords / $totalRecords) * 100, 2) : 0;
            
            return [
                'total_records' => $totalRecords,
                'compressed_records' => $compressedRecords,
                'archived_records' => $archivedRecords,
                'compression_ratio' => $compressionRatio,
                'archive_ratio' => $archiveRatio,
                'archive_files_count' => count($archiveFiles),
                'total_archive_size' => $totalArchiveSize,
                'average_file_size' => count($archiveFiles) > 0 ? 
                    round($totalArchiveSize / count($archiveFiles)) : 0,
            ];
            
        } catch (Exception $e) {
            Log::error("取得壓縮統計失敗", ['error' => $e->getMessage()]);
            
            return [
                'error' => $e->getMessage(),
                'total_records' => 0,
            ];
        }
    }

    /**
     * 自動維護（壓縮、歸檔、清理）
     * 
     * @return array 維護結果
     */
    public function autoMaintenance(): array
    {
        $results = [
            'compression' => [],
            'archive' => [],
            'cleanup' => [],
            'total_time' => 0,
        ];
        
        $startTime = microtime(true);
        
        try {
            // 1. 壓縮舊記錄
            $results['compression'] = $this->compressOldActivities();
            
            // 2. 歸檔更舊的記錄
            $results['archive'] = $this->archiveOldActivities();
            
            // 3. 清理過期記錄
            $results['cleanup'] = $this->cleanupExpiredRecords();
            
            $results['total_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info("自動維護完成", [
                'compression_records' => $results['compression']['compressed_records'],
                'archive_records' => $results['archive']['archived_records'],
                'cleanup_records' => $results['cleanup']['deleted_records'],
                'total_time_ms' => $results['total_time'],
            ]);
            
        } catch (Exception $e) {
            Log::error("自動維護失敗", ['error' => $e->getMessage()]);
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * 壓縮批次記錄
     * 
     * @param \Illuminate\Support\Collection $batch
     * @return array
     */
    protected function compressBatch($batch): array
    {
        $result = [
            'compressed_count' => 0,
            'saved_space' => 0,
            'errors' => [],
        ];
        
        foreach ($batch as $activity) {
            try {
                // 壓縮 properties 欄位
                $originalSize = strlen(json_encode($activity->properties ?? []));
                $compressedProperties = $this->compress($activity->properties ?? []);
                $compressedSize = strlen($compressedProperties);
                
                // 更新記錄
                DB::table('activities')
                    ->where('id', $activity->id)
                    ->update([
                        'properties' => $compressedProperties,
                        'compressed_at' => now(),
                        'original_size' => $originalSize,
                        'compressed_size' => $compressedSize,
                    ]);
                
                $result['compressed_count']++;
                $result['saved_space'] += ($originalSize - $compressedSize);
                
            } catch (Exception $e) {
                $result['errors'][] = [
                    'activity_id' => $activity->id,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $result;
    }

    /**
     * 歸檔月份記錄
     * 
     * @param int $year
     * @param int $month
     * @return array
     */
    protected function archiveMonthlyRecords(int $year, int $month): array
    {
        $result = [
            'archived_count' => 0,
            'file_count' => 0,
            'file_size' => 0,
            'errors' => [],
        ];
        
        try {
            // 取得該月份的所有記錄
            $activities = DB::table('activities')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereNull('archived_at')
                ->get();
            
            if ($activities->isEmpty()) {
                return $result;
            }
            
            // 準備歸檔資料
            $archiveData = $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'event' => $activity->event,
                    'description' => $activity->description,
                    'module' => $activity->module,
                    'user_id' => $activity->user_id,
                    'subject_id' => $activity->subject_id,
                    'subject_type' => $activity->subject_type,
                    'properties' => $activity->properties,
                    'ip_address' => $activity->ip_address,
                    'user_agent' => $activity->user_agent,
                    'result' => $activity->result,
                    'risk_level' => $activity->risk_level,
                    'signature' => $activity->signature,
                    'created_at' => $activity->created_at,
                    'updated_at' => $activity->updated_at,
                ];
            })->toArray();
            
            // 壓縮並儲存歸檔檔案
            $archiveFileName = sprintf("activities_%d_%02d.json.gz", $year, $month);
            $compressedData = $this->compress(json_encode($archiveData));
            
            Storage::disk($this->archiveDisk)->put("archives/{$archiveFileName}", $compressedData);
            
            $result['file_size'] = strlen($compressedData);
            $result['file_count'] = 1;
            
            // 標記記錄為已歸檔
            DB::table('activities')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereNull('archived_at')
                ->update([
                    'archived_at' => now(),
                    'archive_file' => $archiveFileName,
                ]);
            
            $result['archived_count'] = $activities->count();
            
            Log::info("月份記錄歸檔完成", [
                'year' => $year,
                'month' => $month,
                'count' => $result['archived_count'],
                'file' => $archiveFileName,
                'size' => $result['file_size'],
            ]);
            
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            
            Log::error("月份記錄歸檔失敗", [
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $result;
    }

    /**
     * 還原批次記錄
     * 
     * @param array $batch
     * @return array
     */
    protected function restoreBatch(array $batch): array
    {
        $result = [
            'restored_count' => 0,
            'skipped_count' => 0,
            'errors' => [],
        ];
        
        foreach ($batch as $record) {
            try {
                // 檢查記錄是否已存在
                $exists = DB::table('activities')->where('id', $record['id'])->exists();
                
                if ($exists) {
                    $result['skipped_count']++;
                    continue;
                }
                
                // 插入記錄
                DB::table('activities')->insert($record);
                $result['restored_count']++;
                
            } catch (Exception $e) {
                $result['errors'][] = [
                    'record_id' => $record['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $result;
    }

    /**
     * 清理過期歸檔檔案
     * 
     * @param Carbon $beforeDate
     * @return array
     */
    protected function cleanupExpiredArchives(Carbon $beforeDate): array
    {
        $result = [
            'deleted_count' => 0,
            'freed_space' => 0,
        ];
        
        try {
            $archiveFiles = Storage::disk($this->archiveDisk)->files('archives');
            
            foreach ($archiveFiles as $file) {
                $fileDate = Storage::disk($this->archiveDisk)->lastModified($file);
                
                if (Carbon::createFromTimestamp($fileDate)->lt($beforeDate)) {
                    $fileSize = Storage::disk($this->archiveDisk)->size($file);
                    
                    Storage::disk($this->archiveDisk)->delete($file);
                    
                    $result['deleted_count']++;
                    $result['freed_space'] += $fileSize;
                }
            }
            
        } catch (Exception $e) {
            Log::error("清理歸檔檔案失敗", ['error' => $e->getMessage()]);
        }
        
        return $result;
    }

    /**
     * 壓縮資料
     * 
     * @param mixed $data
     * @return string
     */
    protected function compress($data): string
    {
        $jsonData = is_string($data) ? $data : json_encode($data);
        
        return match ($this->compressionFormat) {
            'gzip' => gzencode($jsonData, 9),
            'deflate' => gzdeflate($jsonData, 9),
            'bzip2' => bzcompress($jsonData, 9),
            default => $jsonData,
        };
    }

    /**
     * 解壓縮資料
     * 
     * @param string $compressedData
     * @return string
     */
    protected function decompress(string $compressedData): string
    {
        return match ($this->compressionFormat) {
            'gzip' => gzdecode($compressedData),
            'deflate' => gzinflate($compressedData),
            'bzip2' => bzdecompress($compressedData),
            default => $compressedData,
        };
    }
}