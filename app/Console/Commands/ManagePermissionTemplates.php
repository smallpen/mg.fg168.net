<?php

namespace App\Console\Commands;

use App\Models\PermissionTemplate;
use App\Services\PermissionTemplateService;
use Illuminate\Console\Command;

/**
 * 權限模板管理命令
 * 
 * 提供命令列介面來管理權限模板
 */
class ManagePermissionTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:template 
                            {action : 動作 (list|create-system|apply|export|import)}
                            {--template= : 模板名稱}
                            {--module= : 模組前綴}
                            {--file= : 檔案路徑}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '管理權限模板';

    /**
     * 權限模板服務
     */
    protected PermissionTemplateService $templateService;

    /**
     * 建構函式
     */
    public function __construct(PermissionTemplateService $templateService)
    {
        parent::__construct();
        $this->templateService = $templateService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listTemplates();
                break;
            case 'create-system':
                $this->createSystemTemplates();
                break;
            case 'apply':
                $this->applyTemplate();
                break;
            case 'export':
                $this->exportTemplate();
                break;
            case 'import':
                $this->importTemplate();
                break;
            default:
                $this->error("未知的動作: {$action}");
                $this->info('可用動作: list, create-system, apply, export, import');
        }
    }

    /**
     * 列出所有模板
     */
    private function listTemplates()
    {
        $templates = PermissionTemplate::with('creator')->get();

        if ($templates->isEmpty()) {
            $this->info('沒有找到任何模板');
            return;
        }

        $headers = ['ID', '名稱', '顯示名稱', '模組', '權限數量', '類型', '建立者', '建立時間'];
        $rows = [];

        foreach ($templates as $template) {
            $rows[] = [
                $template->id,
                $template->name,
                $template->display_name,
                $template->module,
                count($template->permissions),
                $template->is_system_template ? '系統' : '自定義',
                $template->creator?->name ?? '系統',
                $template->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * 建立系統模板
     */
    private function createSystemTemplates()
    {
        $this->info('正在建立系統模板...');
        
        PermissionTemplate::createSystemTemplates();
        
        $this->info('系統模板建立完成');
    }

    /**
     * 應用模板
     */
    private function applyTemplate()
    {
        $templateName = $this->option('template');
        $modulePrefix = $this->option('module');

        if (!$templateName) {
            $templateName = $this->ask('請輸入模板名稱');
        }

        if (!$modulePrefix) {
            $modulePrefix = $this->ask('請輸入模組前綴');
        }

        $template = PermissionTemplate::where('name', $templateName)->first();
        if (!$template) {
            $this->error("找不到模板: {$templateName}");
            return;
        }

        $this->info("正在應用模板 '{$template->display_name}' 到模組 '{$modulePrefix}'...");

        try {
            $results = $this->templateService->applyTemplate($template, $modulePrefix);

            $this->info("模板應用完成:");
            $this->info("- 建立權限: " . count($results['created']));
            $this->info("- 跳過權限: " . count($results['skipped']));
            $this->info("- 錯誤數量: " . count($results['errors']));

            if (!empty($results['created'])) {
                $this->info("\n建立的權限:");
                foreach ($results['created'] as $permission) {
                    $this->line("  - {$permission->name} ({$permission->display_name})");
                }
            }

            if (!empty($results['skipped'])) {
                $this->warn("\n跳過的權限:");
                foreach ($results['skipped'] as $skipped) {
                    $this->line("  - {$skipped['name']}: {$skipped['reason']}");
                }
            }

            if (!empty($results['errors'])) {
                $this->error("\n錯誤:");
                foreach ($results['errors'] as $error) {
                    $this->line("  - {$error['name']}: {$error['error']}");
                }
            }
        } catch (\Exception $e) {
            $this->error("應用模板失敗: " . $e->getMessage());
        }
    }

    /**
     * 匯出模板
     */
    private function exportTemplate()
    {
        $templateName = $this->option('template');
        $filePath = $this->option('file');

        if (!$templateName) {
            $templateName = $this->ask('請輸入模板名稱');
        }

        if (!$filePath) {
            $filePath = $this->ask('請輸入匯出檔案路徑');
        }

        $template = PermissionTemplate::where('name', $templateName)->first();
        if (!$template) {
            $this->error("找不到模板: {$templateName}");
            return;
        }

        try {
            $exportData = $this->templateService->exportTemplate($template);
            file_put_contents($filePath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $this->info("模板已匯出到: {$filePath}");
        } catch (\Exception $e) {
            $this->error("匯出失敗: " . $e->getMessage());
        }
    }

    /**
     * 匯入模板
     */
    private function importTemplate()
    {
        $filePath = $this->option('file');

        if (!$filePath) {
            $filePath = $this->ask('請輸入匯入檔案路徑');
        }

        if (!file_exists($filePath)) {
            $this->error("檔案不存在: {$filePath}");
            return;
        }

        try {
            $templateData = json_decode(file_get_contents($filePath), true);
            if (!$templateData) {
                $this->error("無法解析 JSON 檔案");
                return;
            }

            $template = $this->templateService->importTemplate($templateData);
            
            $this->info("模板匯入成功:");
            $this->info("- ID: {$template->id}");
            $this->info("- 名稱: {$template->name}");
            $this->info("- 顯示名稱: {$template->display_name}");
            $this->info("- 權限數量: " . count($template->permissions));
        } catch (\Exception $e) {
            $this->error("匯入失敗: " . $e->getMessage());
        }
    }
}
