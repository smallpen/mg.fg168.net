<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Jobs\ExportActivitiesJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * 活動記錄匯出服務
 * 
 * 處理活動記錄的匯出邏輯，支援多種格式和批量處理
 */
class ActivityExportService
{
    /**
     * 匯出快取前綴
     */
    protected string $cachePrefix = 'activity_export';

    /**
     * 匯出檔案儲存路徑
     */
    protected string $exportPath = 'exports/activities';

    /**
     * 直接匯出（小量資料）
     */
    public function exportDirect(array $config): array
    {
        $activities = $this->getActivitiesForExport($config['filters'], $config['options']);
        
        $filename = $this->generateFilename($config['format']);
        $filePath = $this->exportPath . '/' . $filename;
        
        // 根據格式匯出
        switch ($config['format']) {
            case 'csv':
                $this->exportToCsv($activities, $filePath, $config['options']);
                break;
            case 'json':
                $this->exportToJson($activities, $filePath, $config['options']);
                break;
            case 'pdf':
                $this->exportToPdf($activities, $filePath, $config['options']);
                break;
            default:
                throw new \InvalidArgumentException("不支援的匯出格式: {$config['format']}");
        }

        // 記錄匯出歷史
        $this->recordExportHistory($config['user_id'], [
            'filename' => $filename,
            'format' => $config['format'],
            'file_path' => $filePath,
            'record_count' => $activities->count(),
            'file_size' => \Storage::disk('local')->size($filePath),
            'filters' => $config['filters'],
            'completed_at' => now(),
        ]);

        return [
            'filename' => $filename,
            'file_path' => $filePath,
            'download_url' => route('admin.activities.download-export', ['filename' => $filename]),
            'record_count' => $activities->count(),
            'file_size' => \Storage::disk('local')->size($filePath),
        ];
    }

    /**
     * 批量匯出（大量資料）
     */
    public function exportBatch(array $config): string
    {
        $jobId = Str::uuid()->toString();
        
        // 儲存匯出配置
        Cache::put(
            $this->getCacheKey("job_{$jobId}"),
            $config,
            now()->addHours(24)
        );

        // 初始化進度追蹤
        $this->updateExportProgress($jobId, 0, '準備中...');

        // 派發匯出工作
        ExportActivitiesJob::dispatch($jobId, $config)
            ->onQueue('exports');

        return $jobId;
    }

    /**
     * 取消匯出工作
     */
    public function cancelExport(string $jobId): bool
    {
        // 清除快取的配置和進度
        Cache::forget($this->getCacheKey("job_{$jobId}"));
        Cache::forget($this->getCacheKey("progress_{$jobId}"));
        
        // 這裡可以加入取消佇列工作的邏輯
        
        return true;
    }

    /**
     * 更新匯出進度
     */
    public function updateExportProgress(string $jobId, int $progress, string $status): void
    {
        Cache::put(
            $this->getCacheKey("progress_{$jobId}"),
            [
                'job_id' => $jobId,
                'progress' => $progress,
                'status' => $status,
                'updated_at' => now(),
            ],
            now()->addHours(24)
        );

        // 廣播進度更新事件
        broadcast(new \App\Events\ExportProgressUpdated($jobId, $progress, $status));
    }

    /**
     * 標記匯出完成
     */
    public function markExportCompleted(string $jobId, array $result): void
    {
        $progressData = [
            'job_id' => $jobId,
            'progress' => 100,
            'status' => '完成',
            'completed' => true,
            'download_url' => $result['download_url'],
            'completed_at' => now(),
        ];

        Cache::put(
            $this->getCacheKey("progress_{$jobId}"),
            $progressData,
            now()->addDays(7)
        );

        // 廣播完成事件
        broadcast(new \App\Events\ExportCompleted($jobId, $result));
    }

    /**
     * 標記匯出失敗
     */
    public function markExportFailed(string $jobId, string $error): void
    {
        $progressData = [
            'job_id' => $jobId,
            'progress' => 0,
            'status' => '失敗',
            'error' => $error,
            'failed_at' => now(),
        ];

        Cache::put(
            $this->getCacheKey("progress_{$jobId}"),
            $progressData,
            now()->addDays(1)
        );

        // 廣播失敗事件
        broadcast(new \App\Events\ExportFailed($jobId, $error));
    }

    /**
     * 取得使用者匯出歷史
     */
    public function getUserExportHistory(int|string $userId, int $limit = 10): array
    {
        $cacheKey = $this->getCacheKey("history_{$userId}");
        
        $history = Cache::get($cacheKey, []);
        
        // 按時間排序並限制數量
        usort($history, function ($a, $b) {
            return $b['completed_at'] <=> $a['completed_at'];
        });

        return array_slice($history, 0, $limit);
    }

    /**
     * 清除使用者匯出歷史
     */
    public function clearUserExportHistory(int|string $userId): void
    {
        Cache::forget($this->getCacheKey("history_{$userId}"));
    }

    /**
     * 取得活動記錄用於匯出
     */
    protected function getActivitiesForExport(array $filters, array $options): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::query();

        // 根據選項決定載入的關聯
        $with = [];
        if ($options['include_user_details']) {
            $with[] = 'user:id,username,name,email';
        }
        if ($options['include_related_data']) {
            $with[] = 'subject';
        }

        if (!empty($with)) {
            $query->with($with);
        }

        // 應用篩選條件
        $this->applyFilters($query, $filters);

        // 選擇需要的欄位
        $select = [
            'id', 'type', 'description', 'module', 'user_id',
            'subject_id', 'subject_type', 'ip_address', 'user_agent',
            'result', 'risk_level', 'created_at'
        ];

        if ($options['include_properties']) {
            $select[] = 'properties';
        }

        $query->select($select);

        // 排序
        $query->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * 匯出為 CSV 格式
     */
    protected function exportToCsv($activities, string $filePath, array $options): void
    {
        $csvData = [];
        
        // CSV 標題行
        $headers = [
            'ID', '時間', '類型', '描述', '模組', 'IP位址', '結果', '風險等級'
        ];

        if ($options['include_user_details']) {
            $headers = array_merge($headers, ['使用者ID', '使用者名稱', '使用者信箱']);
        }

        if ($options['include_properties']) {
            $headers[] = '詳細資料';
        }

        $csvData[] = $headers;

        // 資料行
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

            if ($options['include_user_details'] && $activity->user) {
                $row = array_merge($row, [
                    $activity->user->id,
                    $activity->user->name,
                    $activity->user->email ?? '',
                ]);
            } elseif ($options['include_user_details']) {
                $row = array_merge($row, ['', '系統', '']);
            }

            if ($options['include_properties']) {
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

        // 加入 BOM 以支援中文
        $csv = "\xEF\xBB\xBF" . $csv;

        \Storage::disk('local')->put($filePath, $csv);
    }

    /**
     * 匯出為 JSON 格式
     */
    protected function exportToJson($activities, string $filePath, array $options): void
    {
        $jsonData = [
            'export_info' => [
                'exported_at' => now()->toDateTimeString(),
                'total_records' => $activities->count(),
                'format' => 'json',
                'options' => $options,
            ],
            'activities' => $activities->map(function ($activity) use ($options) {
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

                if ($options['include_user_details'] && $activity->user) {
                    $data['user'] = [
                        'id' => $activity->user->id,
                        'username' => $activity->user->username,
                        'name' => $activity->user->name,
                        'email' => $activity->user->email ?? '',
                    ];
                }

                if ($options['include_properties'] && $activity->properties) {
                    $data['properties'] = $activity->properties;
                }

                if ($options['include_related_data'] && $activity->subject) {
                    $data['subject'] = [
                        'type' => $activity->subject_type,
                        'id' => $activity->subject_id,
                        'data' => $activity->subject->toArray(),
                    ];
                }

                return $data;
            })->toArray(),
        ];

        \Storage::disk('local')->put($filePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 匯出為 PDF 格式
     */
    protected function exportToPdf($activities, string $filePath, array $options): void
    {
        $data = [
            'activities' => $activities,
            'options' => $options,
            'export_info' => [
                'exported_at' => now()->format('Y-m-d H:i:s'),
                'total_records' => $activities->count(),
            ],
        ];

        $pdf = Pdf::loadView('exports.activities-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        \Storage::disk('local')->put($filePath, $pdf->output());
    }

    /**
     * 應用篩選條件
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
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
     * 記錄匯出歷史
     */
    protected function recordExportHistory(int|string $userId, array $data): void
    {
        $cacheKey = $this->getCacheKey("history_{$userId}");
        $history = Cache::get($cacheKey, []);
        
        // 加入新記錄
        $history[] = $data;
        
        // 保留最近 50 筆記錄
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        Cache::put($cacheKey, $history, now()->addDays(30));
    }

    /**
     * 生成檔案名稱
     */
    protected function generateFilename(string $format): string
    {
        return 'activities_export_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
    }

    /**
     * 取得快取鍵
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->cachePrefix}.{$key}";
    }

    /**
     * 取得類型顯示名稱
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
     * 取得結果顯示名稱
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
     * 取得風險等級文字
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