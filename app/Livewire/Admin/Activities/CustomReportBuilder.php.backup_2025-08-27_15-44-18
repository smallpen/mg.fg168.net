<?php

namespace App\Livewire\Admin\Activities;

use Livewire\Component;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 自訂統計報告建立器
 * 
 * 提供靈活的統計報告建立功能，允許使用者自訂統計條件、圖表類型和匯出格式
 */
class CustomReportBuilder extends Component
{
    /**
     * 報告設定
     */
    public array $reportConfig = [
        'name' => '',
        'description' => '',
        'date_from' => '',
        'date_to' => '',
        'metrics' => [],
        'filters' => [],
        'chart_types' => [],
        'export_formats' => [],
    ];

    /**
     * 可用的統計指標
     */
    public array $availableMetrics = [
        'total_activities' => '總活動數',
        'unique_users' => '活躍使用者數',
        'security_events' => '安全事件數',
        'success_rate' => '操作成功率',
        'activity_by_type' => '活動類型分佈',
        'activity_by_module' => '模組活動分佈',
        'hourly_distribution' => '每小時分佈',
        'daily_trends' => '每日趨勢',
        'top_users' => '最活躍使用者',
        'risk_analysis' => '風險分析',
    ];

    /**
     * 可用的篩選條件
     */
    public array $availableFilters = [
        'user_id' => '特定使用者',
        'activity_type' => '活動類型',
        'module' => '功能模組',
        'risk_level' => '風險等級',
        'result' => '操作結果',
        'ip_address' => 'IP 位址',
    ];

    /**
     * 可用的圖表類型
     */
    public array $availableChartTypes = [
        'line' => '線圖',
        'bar' => '柱狀圖',
        'pie' => '圓餅圖',
        'doughnut' => '甜甜圈圖',
        'area' => '面積圖',
        'heatmap' => '熱力圖',
    ];

    /**
     * 可用的匯出格式
     */
    public array $availableExportFormats = [
        'json' => 'JSON 格式',
        'csv' => 'CSV 格式',
        'excel' => 'Excel 格式',
        'pdf' => 'PDF 報告',
    ];

    /**
     * 預覽資料
     */
    public ?array $previewData = null;

    /**
     * 是否顯示預覽
     */
    public bool $showPreview = false;

    /**
     * 儲存的報告範本
     */
    public array $savedTemplates = [];

    /**
     * 活動記錄資料存取層
     */
    protected ?ActivityRepositoryInterface $activityRepository = null;

    /**
     * 元件初始化
     */
    public function mount(): void
    {
        $this->authorize('activity_logs.view');
        $this->activityRepository = app(ActivityRepositoryInterface::class);
        
        // 設定預設值
        $this->reportConfig['date_from'] = now()->subDays(7)->format('Y-m-d');
        $this->reportConfig['date_to'] = now()->format('Y-m-d');
        $this->reportConfig['metrics'] = ['total_activities', 'unique_users'];
        $this->reportConfig['chart_types'] = ['line'];
        $this->reportConfig['export_formats'] = ['json'];
        
        $this->loadSavedTemplates();
    }

    /**
     * 取得活動記錄資料存取層實例
     */
    protected function getActivityRepository(): ActivityRepositoryInterface
    {
        if ($this->activityRepository === null) {
            $this->activityRepository = app(ActivityRepositoryInterface::class);
        }
        
        return $this->activityRepository;
    }

    /**
     * 新增統計指標
     */
    public function addMetric(string $metric): void
    {
        if (!in_array($metric, $this->reportConfig['metrics'])) {
            $this->reportConfig['metrics'][] = $metric;
            $this->updatePreview();
        }
    }

    /**
     * 移除統計指標
     */
    public function removeMetric(string $metric): void
    {
        $this->reportConfig['metrics'] = array_diff($this->reportConfig['metrics'], [$metric]);
        $this->updatePreview();
    }

    /**
     * 新增篩選條件
     */
    public function addFilter(string $filter, string $value): void
    {
        $this->reportConfig['filters'][$filter] = $value;
        $this->updatePreview();
    }

    /**
     * 移除篩選條件
     */
    public function removeFilter(string $filter): void
    {
        unset($this->reportConfig['filters'][$filter]);
        $this->updatePreview();
    }

    /**
     * 切換圖表類型
     */
    public function toggleChartType(string $chartType): void
    {
        if (in_array($chartType, $this->reportConfig['chart_types'])) {
            $this->reportConfig['chart_types'] = array_diff($this->reportConfig['chart_types'], [$chartType]);
        } else {
            $this->reportConfig['chart_types'][] = $chartType;
        }
    }

    /**
     * 切換匯出格式
     */
    public function toggleExportFormat(string $format): void
    {
        if (in_array($format, $this->reportConfig['export_formats'])) {
            $this->reportConfig['export_formats'] = array_diff($this->reportConfig['export_formats'], [$format]);
        } else {
            $this->reportConfig['export_formats'][] = $format;
        }
    }

    /**
     * 更新預覽
     */
    public function updatePreview(): void
    {
        if (empty($this->reportConfig['metrics'])) {
            $this->previewData = null;
            $this->showPreview = false;
            return;
        }

        try {
            $this->previewData = $this->generateReportData();
            $this->showPreview = true;
            
            $this->dispatch('preview-updated', [
                'data' => $this->previewData,
                'chartTypes' => $this->reportConfig['chart_types']
            ]);
            
        } catch (\Exception $e) {
            session()->flash('error', '預覽生成失敗：' . $e->getMessage());
            $this->previewData = null;
            $this->showPreview = false;
        }
    }

    /**
     * 生成報告資料
     */
    protected function generateReportData(): array
    {
        $timeRange = $this->calculateTimeRange();
        $filters = $this->buildFilters();
        
        $data = [];
        
        foreach ($this->reportConfig['metrics'] as $metric) {
            $data[$metric] = $this->generateMetricData($metric, $timeRange, $filters);
        }
        
        return [
            'config' => $this->reportConfig,
            'generated_at' => now()->toDateTimeString(),
            'time_range' => $timeRange,
            'filters' => $filters,
            'data' => $data,
            'summary' => $this->generateSummary($data),
        ];
    }

    /**
     * 生成特定指標資料
     */
    protected function generateMetricData(string $metric, string $timeRange, array $filters): array
    {
        $repository = $this->getActivityRepository();
        
        return match ($metric) {
            'total_activities' => $this->getTotalActivitiesData($repository, $timeRange, $filters),
            'unique_users' => $this->getUniqueUsersData($repository, $timeRange, $filters),
            'security_events' => $this->getSecurityEventsData($repository, $timeRange, $filters),
            'success_rate' => $this->getSuccessRateData($repository, $timeRange, $filters),
            'activity_by_type' => $this->getActivityByTypeData($repository, $timeRange, $filters),
            'activity_by_module' => $this->getActivityByModuleData($repository, $timeRange, $filters),
            'hourly_distribution' => $this->getHourlyDistributionData($repository, $timeRange, $filters),
            'daily_trends' => $this->getDailyTrendsData($repository, $timeRange, $filters),
            'top_users' => $this->getTopUsersData($repository, $timeRange, $filters),
            'risk_analysis' => $this->getRiskAnalysisData($repository, $timeRange, $filters),
            default => [],
        };
    }

    /**
     * 取得總活動數資料
     */
    protected function getTotalActivitiesData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return [
            'total' => $stats['total_activities'] ?? 0,
            'average_daily' => $stats['average_daily'] ?? 0,
            'trend' => $stats['daily_trends'] ?? [],
        ];
    }

    /**
     * 取得活躍使用者資料
     */
    protected function getUniqueUsersData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return [
            'total' => $stats['unique_users'] ?? 0,
            'top_users' => $repository->getTopUsers($timeRange, 10)->toArray(),
        ];
    }

    /**
     * 取得安全事件資料
     */
    protected function getSecurityEventsData($repository, string $timeRange, array $filters): array
    {
        $events = $repository->getSecurityEvents($timeRange);
        return [
            'total' => $events->count(),
            'by_risk_level' => $events->groupBy('risk_level')->map->count()->toArray(),
            'recent_events' => $events->take(10)->toArray(),
        ];
    }

    /**
     * 取得成功率資料
     */
    protected function getSuccessRateData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return [
            'success_rate' => $stats['success_rate'] ?? 0,
            'success_count' => $stats['success_count'] ?? 0,
            'failure_count' => $stats['failure_count'] ?? 0,
        ];
    }

    /**
     * 取得活動類型分佈資料
     */
    protected function getActivityByTypeData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return $stats['activity_by_type'] ?? [];
    }

    /**
     * 取得模組分佈資料
     */
    protected function getActivityByModuleData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return $stats['activity_by_module'] ?? [];
    }

    /**
     * 取得每小時分佈資料
     */
    protected function getHourlyDistributionData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return $stats['hourly_distribution'] ?? [];
    }

    /**
     * 取得每日趨勢資料
     */
    protected function getDailyTrendsData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return $stats['daily_trends'] ?? [];
    }

    /**
     * 取得最活躍使用者資料
     */
    protected function getTopUsersData($repository, string $timeRange, array $filters): array
    {
        return $repository->getTopUsers($timeRange, 20)->toArray();
    }

    /**
     * 取得風險分析資料
     */
    protected function getRiskAnalysisData($repository, string $timeRange, array $filters): array
    {
        $stats = $repository->getActivityStats($timeRange);
        return [
            'high_risk_count' => $stats['high_risk_activities'] ?? 0,
            'medium_risk_count' => $stats['medium_risk_activities'] ?? 0,
            'low_risk_count' => $stats['low_risk_activities'] ?? 0,
            'risk_distribution' => $stats['risk_distribution'] ?? [],
        ];
    }

    /**
     * 計算時間範圍
     */
    protected function calculateTimeRange(): string
    {
        $from = Carbon::parse($this->reportConfig['date_from']);
        $to = Carbon::parse($this->reportConfig['date_to']);
        $days = $from->diffInDays($to) + 1;
        
        return $days . 'd';
    }

    /**
     * 建立篩選條件
     */
    protected function buildFilters(): array
    {
        $filters = $this->reportConfig['filters'];
        $filters['date_from'] = $this->reportConfig['date_from'];
        $filters['date_to'] = $this->reportConfig['date_to'];
        
        return $filters;
    }

    /**
     * 生成摘要資訊
     */
    protected function generateSummary(array $data): array
    {
        $summary = [
            'total_metrics' => count($this->reportConfig['metrics']),
            'date_range' => $this->reportConfig['date_from'] . ' 至 ' . $this->reportConfig['date_to'],
            'filters_applied' => count($this->reportConfig['filters']),
        ];
        
        // 計算關鍵指標
        if (isset($data['total_activities']['total'])) {
            $summary['total_activities'] = $data['total_activities']['total'];
        }
        
        if (isset($data['unique_users']['total'])) {
            $summary['unique_users'] = $data['unique_users']['total'];
        }
        
        if (isset($data['security_events']['total'])) {
            $summary['security_events'] = $data['security_events']['total'];
        }
        
        return $summary;
    }

    /**
     * 儲存報告範本
     */
    public function saveTemplate(): void
    {
        if (empty($this->reportConfig['name'])) {
            session()->flash('error', '請輸入報告範本名稱');
            return;
        }
        
        try {
            $template = [
                'id' => uniqid(),
                'name' => $this->reportConfig['name'],
                'description' => $this->reportConfig['description'],
                'config' => $this->reportConfig,
                'created_at' => now()->toDateTimeString(),
            ];
            
            $this->savedTemplates[] = $template;
            $this->saveSavedTemplates();
            
            session()->flash('success', '報告範本已儲存');
            
        } catch (\Exception $e) {
            session()->flash('error', '儲存範本失敗：' . $e->getMessage());
        }
    }

    /**
     * 載入報告範本
     */
    public function loadTemplate(string $templateId): void
    {
        $template = collect($this->savedTemplates)->firstWhere('id', $templateId);
        
        if ($template) {
            $this->reportConfig = $template['config'];
            $this->updatePreview();
            session()->flash('success', '已載入報告範本：' . $template['name']);
        } else {
            session()->flash('error', '找不到指定的報告範本');
        }
    }

    /**
     * 刪除報告範本
     */
    public function deleteTemplate(string $templateId): void
    {
        $this->savedTemplates = array_filter($this->savedTemplates, function ($template) use ($templateId) {
            return $template['id'] !== $templateId;
        });
        
        $this->saveSavedTemplates();
        session()->flash('success', '報告範本已刪除');
    }

    /**
     * 生成並匯出報告
     */
    public function generateReport(): void
    {
        $this->authorize('activity_logs.export');
        
        if (empty($this->reportConfig['metrics'])) {
            session()->flash('error', '請至少選擇一個統計指標');
            return;
        }
        
        if (empty($this->reportConfig['export_formats'])) {
            session()->flash('error', '請至少選擇一種匯出格式');
            return;
        }
        
        try {
            $reportData = $this->generateReportData();
            $exportFiles = [];
            
            foreach ($this->reportConfig['export_formats'] as $format) {
                $filename = $this->exportReport($reportData, $format);
                $exportFiles[] = [
                    'format' => $format,
                    'filename' => $filename,
                    'url' => route('admin.activities.download-export', ['filename' => $filename]),
                ];
            }
            
            $this->dispatch('report-generated', [
                'files' => $exportFiles,
                'config' => $this->reportConfig
            ]);
            
            session()->flash('success', '自訂報告已生成完成，共 ' . count($exportFiles) . ' 個檔案');
            
        } catch (\Exception $e) {
            session()->flash('error', '生成報告失敗：' . $e->getMessage());
        }
    }

    /**
     * 匯出報告
     */
    protected function exportReport(array $reportData, string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportName = !empty($this->reportConfig['name']) ? 
            str_replace(' ', '_', $this->reportConfig['name']) : 'custom_report';
        
        $filename = "custom_report_{$reportName}_{$timestamp}.{$format}";
        $path = storage_path('app/exports/' . $filename);
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        switch ($format) {
            case 'json':
                file_put_contents($path, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
                
            case 'csv':
                $this->exportToCsv($reportData, $path);
                break;
                
            case 'excel':
                $this->exportToExcel($reportData, $path);
                break;
                
            case 'pdf':
                $this->exportToPdf($reportData, $path);
                break;
        }
        
        return $filename;
    }

    /**
     * 匯出為 CSV
     */
    protected function exportToCsv(array $reportData, string $path): void
    {
        $handle = fopen($path, 'w');
        
        // 寫入 BOM 以支援中文
        fwrite($handle, "\xEF\xBB\xBF");
        
        // 寫入標題
        fputcsv($handle, ['指標', '數值', '說明']);
        
        foreach ($reportData['data'] as $metric => $data) {
            $metricName = $this->availableMetrics[$metric] ?? $metric;
            
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_numeric($value)) {
                        fputcsv($handle, [$metricName, $value, $key]);
                    }
                }
            }
        }
        
        fclose($handle);
    }

    /**
     * 匯出為 Excel（簡化版，實際應使用 PhpSpreadsheet）
     */
    protected function exportToExcel(array $reportData, string $path): void
    {
        // 簡化實作，實際應使用 PhpSpreadsheet
        $this->exportToCsv($reportData, $path);
    }

    /**
     * 匯出為 PDF（簡化版，實際應使用 TCPDF 或 DomPDF）
     */
    protected function exportToPdf(array $reportData, string $path): void
    {
        // 簡化實作，生成 HTML 然後轉換為 PDF
        $html = view('admin.activities.report-pdf', ['reportData' => $reportData])->render();
        file_put_contents($path, $html);
    }

    /**
     * 載入儲存的範本
     */
    protected function loadSavedTemplates(): void
    {
        $cacheKey = 'activity_report_templates_' . auth()->id();
        $this->savedTemplates = Cache::get($cacheKey, []);
    }

    /**
     * 儲存範本到快取
     */
    protected function saveSavedTemplates(): void
    {
        $cacheKey = 'activity_report_templates_' . auth()->id();
        Cache::put($cacheKey, $this->savedTemplates, 86400); // 24 小時
    }

    /**
     * 重設報告設定
     */
    public function resetConfig(): void
    {
        $this->reportConfig = [
            'name' => '',
            'description' => '',
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'metrics' => ['total_activities', 'unique_users'],
            'filters' => [],
            'chart_types' => ['line'],
            'export_formats' => ['json'],
        ];
        
        $this->previewData = null;
        $this->showPreview = false;
        
        session()->flash('success', '報告設定已重設');
    }

    /**
     * 取得指標描述
     */
    public function getMetricDescription(string $metric): string
    {
        return match ($metric) {
            'total_activities' => '統計總活動記錄數量和每日平均',
            'unique_users' => '統計活躍使用者數量',
            'security_events' => '統計安全相關事件數量',
            'success_rate' => '計算操作成功率百分比',
            'activity_by_type' => '按活動類型分組統計',
            'activity_by_module' => '按功能模組分組統計',
            'hourly_distribution' => '24小時活動分佈統計',
            'daily_trends' => '每日活動趨勢變化',
            'top_users' => '最活躍使用者排行榜',
            'risk_analysis' => '風險等級分析統計',
            default => '統計指標說明',
        };
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.activities.custom-report-builder');
    }
}