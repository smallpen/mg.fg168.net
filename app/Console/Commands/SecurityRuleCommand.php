<?php

namespace App\Console\Commands;

use App\Models\MonitorRule;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * 安全規則管理命令
 * 管理監控規則的建立、更新、啟用和停用
 */
class SecurityRuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:rule 
                            {action : 動作 (list|create|enable|disable|delete|default)}
                            {--id= : 規則 ID}
                            {--name= : 規則名稱}
                            {--user= : 建立者使用者 ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '管理安全監控規則';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match($action) {
            'list' => $this->listRules(),
            'create' => $this->createRule(),
            'enable' => $this->enableRule(),
            'disable' => $this->disableRule(),
            'delete' => $this->deleteRule(),
            'default' => $this->createDefaultRules(),
            default => $this->showHelp()
        };
    }

    /**
     * 列出所有規則
     */
    protected function listRules(): int
    {
        $rules = MonitorRule::with('creator')->orderBy('priority', 'desc')->get();

        if ($rules->isEmpty()) {
            $this->info('沒有找到任何監控規則。');
            return 0;
        }

        $this->info('=== 監控規則列表 ===');
        
        $tableData = [];
        foreach ($rules as $rule) {
            $tableData[] = [
                $rule->id,
                $rule->name,
                $rule->priority_text,
                $rule->is_active ? '✓' : '✗',
                $rule->triggered_count,
                $rule->creator->name ?? 'Unknown',
                $rule->created_at->format('Y-m-d H:i')
            ];
        }

        $this->table(
            ['ID', '名稱', '優先級', '啟用', '觸發次數', '建立者', '建立時間'],
            $tableData
        );

        return 0;
    }

    /**
     * 建立新規則
     */
    protected function createRule(): int
    {
        $name = $this->option('name') ?: $this->ask('規則名稱');
        $description = $this->ask('規則描述');
        $userId = $this->option('user') ?: $this->ask('建立者使用者 ID');

        if (!$name || !$description || !$userId) {
            $this->error('缺少必要參數。');
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error('找不到指定的使用者。');
            return 1;
        }

        // 互動式建立條件
        $conditions = $this->buildConditions();
        $actions = $this->buildActions();
        $priority = $this->choice('優先級', ['1' => '低', '2' => '中', '3' => '高', '4' => '嚴重'], '2');

        $rule = MonitorRule::create([
            'name' => $name,
            'description' => $description,
            'conditions' => $conditions,
            'actions' => $actions,
            'priority' => (int)$priority,
            'created_by' => $userId,
            'is_active' => true
        ]);

        $this->info("監控規則「{$rule->name}」建立成功！ID: {$rule->id}");
        return 0;
    }

    /**
     * 啟用規則
     */
    protected function enableRule(): int
    {
        $ruleId = $this->option('id') ?: $this->ask('規則 ID');
        
        if (!$ruleId) {
            $this->error('請提供規則 ID。');
            return 1;
        }

        $rule = MonitorRule::find($ruleId);
        if (!$rule) {
            $this->error('找不到指定的規則。');
            return 1;
        }

        $rule->update(['is_active' => true]);
        $this->info("規則「{$rule->name}」已啟用。");
        return 0;
    }

    /**
     * 停用規則
     */
    protected function disableRule(): int
    {
        $ruleId = $this->option('id') ?: $this->ask('規則 ID');
        
        if (!$ruleId) {
            $this->error('請提供規則 ID。');
            return 1;
        }

        $rule = MonitorRule::find($ruleId);
        if (!$rule) {
            $this->error('找不到指定的規則。');
            return 1;
        }

        $rule->update(['is_active' => false]);
        $this->info("規則「{$rule->name}」已停用。");
        return 0;
    }

    /**
     * 刪除規則
     */
    protected function deleteRule(): int
    {
        $ruleId = $this->option('id') ?: $this->ask('規則 ID');
        
        if (!$ruleId) {
            $this->error('請提供規則 ID。');
            return 1;
        }

        $rule = MonitorRule::find($ruleId);
        if (!$rule) {
            $this->error('找不到指定的規則。');
            return 1;
        }

        if ($this->confirm("確定要刪除規則「{$rule->name}」嗎？")) {
            $rule->delete();
            $this->info("規則「{$rule->name}」已刪除。");
        } else {
            $this->info('取消刪除。');
        }

        return 0;
    }

    /**
     * 建立預設規則
     */
    protected function createDefaultRules(): int
    {
        $userId = $this->option('user') ?: $this->ask('建立者使用者 ID');
        
        if (!$userId) {
            $this->error('請提供建立者使用者 ID。');
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error('找不到指定的使用者。');
            return 1;
        }

        $this->info('建立預設監控規則...');
        
        try {
            MonitorRule::createDefaultRules($user);
            $this->info('預設監控規則建立成功！');
        } catch (\Exception $e) {
            $this->error('建立預設規則時發生錯誤：' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 顯示幫助資訊
     */
    protected function showHelp(): int
    {
        $this->info('安全規則管理命令');
        $this->info('');
        $this->info('可用動作：');
        $this->info('  list     - 列出所有規則');
        $this->info('  create   - 建立新規則');
        $this->info('  enable   - 啟用規則');
        $this->info('  disable  - 停用規則');
        $this->info('  delete   - 刪除規則');
        $this->info('  default  - 建立預設規則');
        $this->info('');
        $this->info('範例：');
        $this->info('  php artisan security:rule list');
        $this->info('  php artisan security:rule create --name="登入失敗監控" --user=1');
        $this->info('  php artisan security:rule enable --id=1');
        $this->info('  php artisan security:rule default --user=1');

        return 0;
    }

    /**
     * 建立條件配置
     */
    protected function buildConditions(): array
    {
        $conditions = [];

        // 活動類型
        $activityType = $this->ask('活動類型 (例如: login, create, delete)');
        if ($activityType) {
            $conditions['activity_type'] = $activityType;
        }

        // 結果狀態
        $result = $this->choice('結果狀態', ['', 'success', 'failed', 'warning'], '');
        if ($result) {
            $conditions['result'] = $result;
        }

        // 計數閾值
        $threshold = $this->ask('計數閾值 (預設: 5)', '5');
        $conditions['count_threshold'] = (int)$threshold;

        // 時間窗口
        $timeWindow = $this->ask('時間窗口（秒，預設: 300）', '300');
        $conditions['time_window'] = (int)$timeWindow;

        // 分組方式
        $groupBy = $this->choice('分組方式', ['', 'ip_address', 'user_id'], '');
        if ($groupBy) {
            $conditions['group_by'] = $groupBy;
        }

        return $conditions;
    }

    /**
     * 建立動作配置
     */
    protected function buildActions(): array
    {
        $actions = [];

        $actions['create_alert'] = $this->confirm('建立安全警報？', true);
        $actions['notify_admin'] = $this->confirm('通知管理員？', false);
        $actions['block_ip'] = $this->confirm('封鎖 IP？', false);
        $actions['log_detailed'] = $this->confirm('記錄詳細日誌？', false);
        $actions['require_approval'] = $this->confirm('需要審批？', false);

        return $actions;
    }
}
