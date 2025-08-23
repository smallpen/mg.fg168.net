<?php

namespace App\Console\Commands;

use App\Services\ActivityIntegrityService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckActivityIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:check-integrity 
                            {--from= : 開始日期 (Y-m-d)}
                            {--to= : 結束日期 (Y-m-d)}
                            {--batch-size=1000 : 批次大小}
                            {--export-report : 匯出報告到檔案}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '檢查活動記錄的完整性並生成報告';

    /**
     * 完整性服務
     */
    protected ActivityIntegrityService $integrityService;

    /**
     * 建構子
     */
    public function __construct(ActivityIntegrityService $integrityService)
    {
        parent::__construct();
        $this->integrityService = $integrityService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('開始執行活動記錄完整性檢查...');
        
        // 準備檢查選項
        $options = [
            'batch_size' => (int) $this->option('batch-size'),
            'date_from' => $this->option('from') ? Carbon::parse($this->option('from')) : null,
            'date_to' => $this->option('to') ? Carbon::parse($this->option('to')) : null,
        ];
        
        // 顯示檢查範圍
        if ($options['date_from']) {
            $this->line("開始日期: {$options['date_from']->format('Y-m-d')}");
        }
        
        if ($options['date_to']) {
            $this->line("結束日期: {$options['date_to']->format('Y-m-d')}");
        }
        
        $this->line("批次大小: {$options['batch_size']}");
        $this->newLine();
        
        // 執行完整性檢查
        $report = $this->integrityService->performIntegrityCheck($options);
        
        // 顯示結果
        $this->displayReport($report);
        
        // 匯出報告（如果需要）
        if ($this->option('export-report')) {
            $this->exportReport($report);
        }
        
        // 根據結果設定退出碼
        if ($report['status'] === 'failed') {
            return Command::FAILURE;
        }
        
        if ($report['invalid_records'] > 0) {
            $this->warn('發現完整性問題，請檢查報告詳情');
            return Command::FAILURE;
        }
        
        $this->info('完整性檢查完成，所有記錄都通過驗證');
        return Command::SUCCESS;
    }
    
    /**
     * 顯示檢查報告
     * 
     * @param array $report 檢查報告
     * @return void
     */
    protected function displayReport(array $report): void
    {
        $this->info('=== 完整性檢查報告 ===');
        $this->newLine();
        
        // 基本統計
        $this->line("檢查開始時間: {$report['started_at']->format('Y-m-d H:i:s')}");
        $this->line("檢查完成時間: {$report['completed_at']->format('Y-m-d H:i:s')}");
        $this->line("執行時間: {$report['execution_time']} 秒");
        $this->line("檢查狀態: {$report['status']}");
        $this->newLine();
        
        // 統計資訊
        $this->table([
            '項目', '數量', '百分比'
        ], [
            ['總檢查記錄', number_format($report['total_checked']), '100%'],
            ['有效記錄', number_format($report['valid_records']), $this->calculatePercentage($report['valid_records'], $report['total_checked'])],
            ['無效記錄', number_format($report['invalid_records']), $this->calculatePercentage($report['invalid_records'], $report['total_checked'])],
            ['缺少簽章', number_format($report['missing_signatures']), $this->calculatePercentage($report['missing_signatures'], $report['total_checked'])],
        ]);
        
        // 顯示損壞記錄詳情
        if (!empty($report['corrupted_records'])) {
            $this->newLine();
            $this->error('發現損壞的記錄:');
            
            $corruptedData = [];
            foreach ($report['corrupted_records'] as $record) {
                $corruptedData[] = [
                    $record['id'],
                    $record['type'],
                    $record['created_at'],
                    $record['causer_id'] ?? 'N/A'
                ];
            }
            
            $this->table([
                'ID', '類型', '建立時間', '操作者ID'
            ], $corruptedData);
        }
        
        // 顯示錯誤訊息（如果有）
        if (isset($report['error'])) {
            $this->newLine();
            $this->error("錯誤訊息: {$report['error']}");
        }
    }
    
    /**
     * 匯出報告到檔案
     * 
     * @param array $report 檢查報告
     * @return void
     */
    protected function exportReport(array $report): void
    {
        $filename = 'activity_integrity_report_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);
        
        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("報告已匯出到: {$filepath}");
    }
    
    /**
     * 計算百分比
     * 
     * @param int $value 值
     * @param int $total 總數
     * @return string 百分比字串
     */
    protected function calculatePercentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        
        $percentage = round(($value / $total) * 100, 2);
        return "{$percentage}%";
    }
}
