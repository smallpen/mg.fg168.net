<?php

namespace App\Jobs;

use App\Services\ActivityExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄匯出工作
 * 
 * 處理大量活動記錄的批量匯出
 */
class ExportActivitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作 ID
     */
    public string $jobId;

    /**
     * 匯出配置
     */
    public array $config;

    /**
     * 工作超時時間（秒）
     */
    public int $timeout = 3600; // 1 小時

    /**
     * 最大重試次數
     */
    public int $tries = 3;

    /**
     * 建立新的工作實例
     */
    public function __construct(string $jobId, array $config)
    {
        $this->jobId = $jobId;
        $this->config = $config;
        
        // 設定佇列名稱
        $this->onQueue('exports');
    }

    /**
     * 執行工作
     */
    public function handle(ActivityExportService $exportService): void
    {
        try {
            Log::info("開始匯出工作", ['job_id' => $this->jobId]);
            
            // 更新進度：開始處理
            $exportService->updateExportProgress($this->jobId, 5, '開始處理匯出...');

            // 執行匯出
            $result = $this->performExport($exportService);

            // 更新進度：完成
            $exportService->updateExportProgress($this->jobId, 100, '匯出完成');
            
            // 標記完成
            $exportService->markExportCompleted($this->jobId, $result);

            Log::info("匯出工作完成", [
                'job_id' => $this->jobId,
                'file_path' => $result['file_path'],
                'record_count' => $result['record_count']
            ]);

        } catch (\Exception $e) {
            Log::error("匯出工作失敗", [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $exportService->markExportFailed($this->jobId, $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * 工作失敗時的處理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("匯出工作最終失敗", [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage()
        ]);

        $exportService = app(ActivityExportService::class);
        $exportService->markExportFailed($this->jobId, $exception->getMessage());
    }

    /**
     * 執行匯出處理
     */
    protected function performExport(ActivityExportService $exportService): array
    {
        // 取得活動記錄總數
        $totalRecords = $this->getTotalRecords();
        $exportService->updateExportProgress($this->jobId, 10, "準備匯出 {$totalRecords} 筆記錄...");

        // 分批處理
        $batchSize = $this->config['options']['batch_size'] ?? 1000;
        $batches = ceil($totalRecords / $batchSize);
        $processedRecords = 0;
        
        // 建立臨時檔案
        $tempFiles = [];
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $offset = $batch * $batchSize;
            
            // 更新進度
            $progress = 10 + (($batch / $batches) * 80); // 10-90% 用於資料處理
            $exportService->updateExportProgress(
                $this->jobId, 
                (int) $progress, 
                "處理第 " . ($batch + 1) . " 批，共 {$batches} 批..."
            );

            // 取得這一批的資料
            $activities = $this->getActivitiesBatch($offset, $batchSize);
            
            if ($activities->isEmpty()) {
                break;
            }

            // 處理這一批資料
            $tempFile = $this->processBatch($activities, $batch, $exportService);
            $tempFiles[] = $tempFile;
            
            $processedRecords += $activities->count();
            
            // 記憶體清理
            unset($activities);
            
            // 避免記憶體洩漏
            if ($batch % 10 === 0) {
                gc_collect_cycles();
            }
        }

        // 合併所有批次檔案
        $exportService->updateExportProgress($this->jobId, 90, '合併匯出檔案...');
        $finalFile = $this->mergeBatchFiles($tempFiles, $exportService);

        // 清理臨時檔案
        $this->cleanupTempFiles($tempFiles);

        // 記錄匯出歷史
        $this->recordExportHistory($exportService, $finalFile, $processedRecords);

        return [
            'filename' => basename($finalFile['file_path']),
            'file_path' => $finalFile['file_path'],
            'download_url' => $finalFile['download_url'],
            'record_count' => $processedRecords,
            'file_size' => $finalFile['file_size'],
        ];
    }

    /**
     * 取得活動記錄總數
     */
    protected function getTotalRecords(): int
    {
        $query = \App\Models\Activity::query();
        $this->applyFilters($query, $this->config['filters']);
        return $query->count();
    }

    /**
     * 取得一批活動記錄
     */
    protected function getActivitiesBatch(int $offset, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $query = \App\Models\Activity::query();

        // 根據選項決定載入的關聯
        $with = [];
        if ($this->config['options']['include_user_details']) {
            $with[] = 'user:id,username,name,email';
        }
        if ($this->config['options']['include_related_data']) {
            $with[] = 'subject';
        }

        if (!empty($with)) {
            $query->with($with);
        }

        // 應用篩選條件
        $this->applyFilters($query, $this->config['filters']);

        // 選擇需要的欄位
        $select = [
            'id', 'type', 'description', 'module', 'user_id',
            'subject_id', 'subject_type', 'ip_address', 'user_agent',
            'result', 'risk_level', 'created_at'
        ];

        if ($this->config['options']['include_properties']) {
            $select[] = 'properties';
        }

        $query->select($select);

        // 排序和分頁
        return $query->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * 處理一批資料
     */
    protected function processBatch($activities, int $batchNumber, ActivityExportService $exportService): array
    {
        $tempFilename = "temp_batch_{$this->jobId}_{$batchNumber}.{$this->config['format']}";
        $tempPath = "exports/temp/{$tempFilename}";

        switch ($this->config['format']) {
            case 'csv':
                $this->processCsvBatch($activities, $tempPath, $batchNumber === 0);
                break;
            case 'json':
                $this->processJsonBatch($activities, $tempPath);
                break;
            case 'pdf':
                // PDF 不適合分批處理，這裡先儲存為 JSON
                $this->processJsonBatch($activities, $tempPath);
                break;
        }

        return [
            'filename' => $tempFilename,
            'path' => $tempPath,
            'batch_number' => $batchNumber,
            'record_count' => $activities->count(),
        ];
    }

    /**
     * 處理 CSV 批次
     */
    protected function processCsvBatch($activities, string $tempPath, bool $includeHeader): void
    {
        $csvData = [];
        
        // 如果是第一批，加入標題行
        if ($includeHeader) {
            $headers = [
                'ID', '時間', '類型', '描述', '模組', 'IP位址', '結果', '風險等級'
            ];

            if ($this->config['options']['include_user_details']) {
                $headers = array_merge($headers, ['使用者ID', '使用者名稱', '使用者信箱']);
            }

            if ($this->config['options']['include_properties']) {
                $headers[] = '詳細資料';
            }

            $csvData[] = $headers;
        }

        // 處理資料行
        foreach ($activities as $activity) {
            $row = [
                $activity->id,
                $activity->created_at->format('Y-m-d H:i:s'),
                $this->getTypeDisplayName($activity->type),
                $activity->description,
                $activity->module ?? '',
                $activity->ip_address ?? '',
                $this->getResultDisplayName($activity->result),
                $this->getRiskLevelText($activity->risk_level),
            ];

            if ($this->config['options']['include_user_details'] && $activity->user) {
                $row = array_merge($row, [
                    $activity->user->id,
                    $activity->user->name,
                    $activity->user->email ?? '',
                ]);
            } elseif ($this->config['options']['include_user_details']) {
                $row = array_merge($row, ['', '系統', '']);
            }

            if ($this->config['options']['include_properties']) {
                $properties = is_array($activity->properties) 
                    ? json_encode($activity->properties, JSON_UNESCAPED_UNICODE)
                    : '';
                $row[] = $properties;
            }

            $csvData[] = $row;
        }

        // 生成 CSV 內容
        $csv = '';
        foreach ($csvData as $row) {
            $csv .= implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field ?? '') . '"';
            }, $row)) . "\n";
        }

        // 第一批加入 BOM
        if ($includeHeader) {
            $csv = "\xEF\xBB\xBF" . $csv;
        }

        \Storage::disk('local')->put($tempPath, $csv);
    }

    /**
     * 處理 JSON 批次
     */
    protected function processJsonBatch($activities, string $tempPath): void
    {
        $jsonData = $activities->map(function ($activity) {
            $data = [
                'id' => $activity->id,
                'type' => $activity->type,
                'type_display' => $this->getTypeDisplayName($activity->type),
                'description' => $activity->description,
                'module' => $activity->module,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
                'result' => $activity->result,
                'result_display' => $this->getResultDisplayName($activity->result),
                'risk_level' => $activity->risk_level,
                'risk_level_text' => $this->getRiskLevelText($activity->risk_level),
                'created_at' => $activity->created_at->toDateTimeString(),
            ];

            if ($this->config['options']['include_user_details'] && $activity->user) {
                $data['user'] = [
                    'id' => $activity->user->id,
                    'username' => $activity->user->username,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email ?? '',
                ];
            }

            if ($this->config['options']['include_properties'] && $activity->properties) {
                $data['properties'] = $activity->properties;
            }

            if ($this->config['options']['include_related_data'] && $activity->subject) {
                $data['subject'] = [
                    'type' => $activity->subject_type,
                    'id' => $activity->subject_id,
                    'data' => $activity->subject->toArray(),
                ];
            }

            return $data;
        })->toArray();

        \Storage::disk('local')->put($tempPath, json_encode($jsonData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 合併批次檔案
     */
    protected function mergeBatchFiles(array $tempFiles, ActivityExportService $exportService): array
    {
        $finalFilename = "activities_export_{$this->jobId}." . $this->config['format'];
        $finalPath = "exports/activities/{$finalFilename}";

        switch ($this->config['format']) {
            case 'csv':
                $this->mergeCsvFiles($tempFiles, $finalPath);
                break;
            case 'json':
                $this->mergeJsonFiles($tempFiles, $finalPath);
                break;
            case 'pdf':
                $this->convertJsonToPdf($tempFiles, $finalPath);
                break;
        }

        return [
            'filename' => $finalFilename,
            'file_path' => $finalPath,
            'download_url' => \Storage::disk('local')->url($finalPath),
            'file_size' => \Storage::disk('local')->size($finalPath),
        ];
    }

    /**
     * 合併 CSV 檔案
     */
    protected function mergeCsvFiles(array $tempFiles, string $finalPath): void
    {
        $finalContent = '';
        
        foreach ($tempFiles as $tempFile) {
            $content = \Storage::disk('local')->get($tempFile['path']);
            
            // 第一個檔案保留完整內容，其他檔案跳過標題行
            if ($tempFile['batch_number'] === 0) {
                $finalContent .= $content;
            } else {
                $lines = explode("\n", $content);
                $finalContent .= implode("\n", array_slice($lines, 1));
            }
        }

        \Storage::disk('local')->put($finalPath, $finalContent);
    }

    /**
     * 合併 JSON 檔案
     */
    protected function mergeJsonFiles(array $tempFiles, string $finalPath): void
    {
        $allActivities = [];
        
        foreach ($tempFiles as $tempFile) {
            $content = \Storage::disk('local')->get($tempFile['path']);
            $batchData = json_decode($content, true);
            $allActivities = array_merge($allActivities, $batchData);
        }

        $finalData = [
            'export_info' => [
                'exported_at' => now()->toDateTimeString(),
                'total_records' => count($allActivities),
                'format' => 'json',
                'job_id' => $this->jobId,
                'options' => $this->config['options'],
            ],
            'activities' => $allActivities,
        ];

        \Storage::disk('local')->put($finalPath, json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 轉換 JSON 為 PDF
     */
    protected function convertJsonToPdf(array $tempFiles, string $finalPath): void
    {
        // 載入所有資料
        $allActivities = [];
        foreach ($tempFiles as $tempFile) {
            $content = \Storage::disk('local')->get($tempFile['path']);
            $batchData = json_decode($content, true);
            $allActivities = array_merge($allActivities, $batchData);
        }

        // 轉換為 Collection 以便在視圖中使用
        $activities = collect($allActivities)->map(function ($item) {
            return (object) $item;
        });

        $data = [
            'activities' => $activities,
            'options' => $this->config['options'],
            'export_info' => [
                'exported_at' => now()->format('Y-m-d H:i:s'),
                'total_records' => $activities->count(),
                'job_id' => $this->jobId,
            ],
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.activities-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        \Storage::disk('local')->put($finalPath, $pdf->output());
    }

    /**
     * 清理臨時檔案
     */
    protected function cleanupTempFiles(array $tempFiles): void
    {
        foreach ($tempFiles as $tempFile) {
            \Storage::disk('local')->delete($tempFile['path']);
        }
    }

    /**
     * 記錄匯出歷史
     */
    protected function recordExportHistory(ActivityExportService $exportService, array $fileInfo, int $recordCount): void
    {
        $cacheKey = "activity_export.history_{$this->config['user_id']}";
        $history = Cache::get($cacheKey, []);
        
        $history[] = [
            'job_id' => $this->jobId,
            'filename' => $fileInfo['filename'],
            'format' => $this->config['format'],
            'file_path' => $fileInfo['file_path'],
            'record_count' => $recordCount,
            'file_size' => $fileInfo['file_size'],
            'filters' => $this->config['filters'],
            'completed_at' => now(),
        ];
        
        // 保留最近 50 筆記錄
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        Cache::put($cacheKey, $history, now()->addDays(30));
    }

    /**
     * 應用篩選條件
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($filters['date_from'])->startOfDay());
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($filters['date_to'])->endOfDay());
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        
        if (!empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }
        
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', "%{$filters['ip_address']}%");
        }
        
        if (!empty($filters['risk_level'])) {
            if ($filters['risk_level'] === 'high') {
                $query->where('risk_level', '>=', 7);
            } else {
                $query->where('risk_level', $filters['risk_level']);
            }
        }
        
        if (!empty($filters['security_events_only'])) {
            $query->where('risk_level', '>=', 5);
        }
    }

    /**
     * 輔助方法：取得類型顯示名稱
     */
    protected function getTypeDisplayName(string $type): string
    {
        return match ($type) {
            'login' => '登入',
            'logout' => '登出',
            'login_failed' => '登入失敗',
            'create_user' => '建立使用者',
            'update_user' => '更新使用者',
            'delete_user' => '刪除使用者',
            'create_role' => '建立角色',
            'update_role' => '更新角色',
            'delete_role' => '刪除角色',
            'assign_role' => '指派角色',
            'remove_role' => '移除角色',
            'create_permission' => '建立權限',
            'update_permission' => '更新權限',
            'delete_permission' => '刪除權限',
            'view_dashboard' => '檢視儀表板',
            'export_data' => '匯出資料',
            'system_setting' => '系統設定',
            'security_event' => '安全事件',
            default => $type,
        };
    }

    /**
     * 輔助方法：取得結果顯示名稱
     */
    protected function getResultDisplayName(string $result): string
    {
        return match ($result) {
            'success' => '成功',
            'failed' => '失敗',
            'warning' => '警告',
            'error' => '錯誤',
            default => $result,
        };
    }

    /**
     * 輔助方法：取得風險等級文字
     */
    protected function getRiskLevelText(int $riskLevel): string
    {
        return match (true) {
            $riskLevel >= 8 => '極高',
            $riskLevel >= 6 => '高',
            $riskLevel >= 4 => '中',
            $riskLevel >= 2 => '低',
            default => '極低',
        };
    }
}