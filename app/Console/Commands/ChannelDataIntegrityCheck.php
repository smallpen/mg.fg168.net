<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\ChannelIntegrityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChannelDataIntegrityCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'channel:integrity-check 
                            {--fix : 自動修復發現的問題}
                            {--report : 生成詳細報告}
                            {--agent= : 檢查特定代理ID}
                            {--level= : 檢查特定層級}';

    /**
     * The console command description.
     */
    protected $description = '檢查通路管理系統的資料完整性和一致性';

    private ChannelIntegrityService $integrityService;
    private array $issues = [];
    private array $statistics = [];

    public function __construct(ChannelIntegrityService $integrityService)
    {
        parent::__construct();
        $this->integrityService = $integrityService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 開始通路管理系統資料完整性檢查...');
        $startTime = microtime(true);

        try {
            // 初始化統計資料
            $this->initializeStatistics();

            // 執行各項檢查
            $this->checkAccountUniqueness();
            $this->checkPrefixConsistency();
            $this->checkHierarchyIntegrity();
            $this->checkPointsConsistency();
            $this->checkOrphanedRecords();
            $this->checkTransactionIntegrity();
            $this->checkBusinessRules();

            // 生成報告
            $this->generateReport();

            // 自動修復（如果指定）
            if ($this->option('fix') && !empty($this->issues)) {
                $this->performAutoFix();
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("✅ 檢查完成，耗時 {$executionTime} 秒");

            return empty($this->issues) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('❌ 檢查過程中發生錯誤: ' . $e->getMessage());
            Log::error('Channel integrity check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 2;
        }
    }

    /**
     * 初始化統計資料
     */
    private function initializeStatistics(): void
    {
        $this->statistics = [
            'total_agents' => Agent::count(),
            'total_players' => Player::count(),
            'total_transactions' => PointTransaction::count(),
            'active_agents' => Agent::where('is_active', true)->count(),
            'active_players' => Player::where('is_active', true)->count(),
            'total_points_allocated' => Agent::sum('total_points'),
            'issues_found' => 0,
            'issues_fixed' => 0,
        ];

        $this->info("📊 系統統計: {$this->statistics['total_agents']} 代理, {$this->statistics['total_players']} 玩家, {$this->statistics['total_transactions']} 交易記錄");
    }

    /**
     * 檢查帳號唯一性
     */
    private function checkAccountUniqueness(): void
    {
        $this->info('🔍 檢查帳號唯一性...');

        // 檢查代理帳號重複
        $duplicateAgentAccounts = DB::table('agents')
            ->select('account', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('account')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateAgentAccounts as $duplicate) {
            $this->addIssue('account_duplicate', [
                'type' => 'agent',
                'account' => $duplicate->account,
                'count' => $duplicate->count,
                'severity' => 'high',
                'description' => "代理帳號 '{$duplicate->account}' 重複 {$duplicate->count} 次"
            ]);
        }

        // 檢查玩家帳號重複
        $duplicatePlayerAccounts = DB::table('players')
            ->select('account', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('account')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicatePlayerAccounts as $duplicate) {
            $this->addIssue('account_duplicate', [
                'type' => 'player',
                'account' => $duplicate->account,
                'count' => $duplicate->count,
                'severity' => 'high',
                'description' => "玩家帳號 '{$duplicate->account}' 重複 {$duplicate->count} 次"
            ]);
        }

        // 檢查代理和玩家帳號衝突
        $conflictAccounts = DB::table('agents as a')
            ->join('players as p', 'a.account', '=', 'p.account')
            ->whereNull('a.deleted_at')
            ->whereNull('p.deleted_at')
            ->select('a.account')
            ->distinct()
            ->get();

        foreach ($conflictAccounts as $conflict) {
            $this->addIssue('account_conflict', [
                'account' => $conflict->account,
                'severity' => 'high',
                'description' => "帳號 '{$conflict->account}' 在代理和玩家中都存在"
            ]);
        }
    }

    /**
     * 檢查前置符號一致性
     */
    private function checkPrefixConsistency(): void
    {
        $this->info('🔍 檢查前置符號一致性...');

        // 檢查第一層代理前置符號重複
        $duplicatePrefixes = Agent::where('level', 1)
            ->whereNotNull('prefix')
            ->select('prefix', DB::raw('COUNT(*) as count'))
            ->groupBy('prefix')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicatePrefixes as $duplicate) {
            $this->addIssue('prefix_duplicate', [
                'prefix' => $duplicate->prefix,
                'count' => $duplicate->count,
                'severity' => 'high',
                'description' => "前置符號 '{$duplicate->prefix}' 被 {$duplicate->count} 個第一層代理使用"
            ]);
        }

        // 檢查帳號前置符號與層級結構不符
        $agents = Agent::with('parent')->get();
        foreach ($agents as $agent) {
            $expectedPrefix = $this->integrityService->getExpectedPrefix($agent);
            $actualPrefix = $this->integrityService->extractPrefix($agent->account);

            if ($expectedPrefix !== $actualPrefix) {
                $this->addIssue('prefix_mismatch', [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'expected_prefix' => $expectedPrefix,
                    'actual_prefix' => $actualPrefix,
                    'severity' => 'medium',
                    'description' => "代理 '{$agent->name}' 的帳號前置符號不符合層級結構"
                ]);
            }
        }

        // 檢查玩家帳號前置符號
        $players = Player::with('agent')->get();
        foreach ($players as $player) {
            $expectedPrefix = $this->integrityService->getExpectedPrefix($player->agent);
            $actualPrefix = $this->integrityService->extractPrefix($player->account);

            if ($expectedPrefix !== $actualPrefix) {
                $this->addIssue('player_prefix_mismatch', [
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                    'agent_name' => $player->agent->name,
                    'expected_prefix' => $expectedPrefix,
                    'actual_prefix' => $actualPrefix,
                    'severity' => 'medium',
                    'description' => "玩家 '{$player->name}' 的帳號前置符號與隸屬代理不符"
                ]);
            }
        }
    }

    /**
     * 檢查層級結構完整性
     */
    private function checkHierarchyIntegrity(): void
    {
        $this->info('🔍 檢查層級結構完整性...');

        // 檢查循環引用
        $agents = Agent::all();
        foreach ($agents as $agent) {
            if ($this->integrityService->hasCircularReference($agent)) {
                $this->addIssue('circular_reference', [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'severity' => 'critical',
                    'description' => "代理 '{$agent->name}' 存在循環引用"
                ]);
            }
        }

        // 檢查層級計算錯誤
        foreach ($agents as $agent) {
            $expectedLevel = $this->integrityService->calculateLevel($agent);
            if ($agent->level !== $expectedLevel) {
                $this->addIssue('level_mismatch', [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'expected_level' => $expectedLevel,
                    'actual_level' => $agent->level,
                    'severity' => 'medium',
                    'description' => "代理 '{$agent->name}' 的層級計算錯誤"
                ]);
            }
        }

        // 檢查孤立節點
        $orphanedAgents = Agent::whereNotNull('parent_id')
            ->whereDoesntHave('parent')
            ->get();

        foreach ($orphanedAgents as $agent) {
            $this->addIssue('orphaned_agent', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'parent_id' => $agent->parent_id,
                'severity' => 'high',
                'description' => "代理 '{$agent->name}' 的上層代理不存在"
            ]);
        }
    }

    /**
     * 檢查點數一致性
     */
    private function checkPointsConsistency(): void
    {
        $this->info('🔍 檢查點數一致性...');

        $agents = Agent::with(['children', 'players'])->get();
        
        foreach ($agents as $agent) {
            // 檢查點數計算
            $calculatedAllocated = $this->integrityService->calculateAllocatedPoints($agent);
            $calculatedRemaining = $agent->total_points - $calculatedAllocated;

            if (abs($agent->allocated_points - $calculatedAllocated) > 0.01) {
                $this->addIssue('points_allocated_mismatch', [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'recorded_allocated' => $agent->allocated_points,
                    'calculated_allocated' => $calculatedAllocated,
                    'difference' => $agent->allocated_points - $calculatedAllocated,
                    'severity' => 'high',
                    'description' => "代理 '{$agent->name}' 的已分配點數計算錯誤"
                ]);
            }

            if (abs($agent->remaining_points - $calculatedRemaining) > 0.01) {
                $this->addIssue('points_remaining_mismatch', [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'recorded_remaining' => $agent->remaining_points,
                    'calculated_remaining' => $calculatedRemaining,
                    'difference' => $agent->remaining_points - $calculatedRemaining,
                    'severity' => 'high',
                    'description' => "代理 '{$agent->name}' 的剩餘點數計算錯誤"
                ]);
            }

            // 檢查負點數
            if ($agent->remaining_points < 0) {
                $this->addIssue('negative_points', [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'remaining_points' => $agent->remaining_points,
                    'severity' => 'critical',
                    'description' => "代理 '{$agent->name}' 的剩餘點數為負數"
                ]);
            }
        }

        // 檢查玩家負點數
        $playersWithNegativePoints = Player::where('points', '<', 0)->get();
        foreach ($playersWithNegativePoints as $player) {
            $this->addIssue('player_negative_points', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'points' => $player->points,
                'severity' => 'critical',
                'description' => "玩家 '{$player->name}' 的點數為負數"
            ]);
        }
    }

    /**
     * 檢查孤立記錄
     */
    private function checkOrphanedRecords(): void
    {
        $this->info('🔍 檢查孤立記錄...');

        // 檢查孤立玩家
        $orphanedPlayers = Player::whereDoesntHave('agent')->get();
        foreach ($orphanedPlayers as $player) {
            $this->addIssue('orphaned_player', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'agent_id' => $player->agent_id,
                'severity' => 'high',
                'description' => "玩家 '{$player->name}' 的隸屬代理不存在"
            ]);
        }

        // 檢查孤立交易記錄
        $orphanedTransactions = PointTransaction::where(function ($query) {
            $query->whereNotNull('agent_id')
                  ->whereDoesntHave('agent');
        })->orWhere(function ($query) {
            $query->whereNotNull('player_id')
                  ->whereDoesntHave('player');
        })->get();

        foreach ($orphanedTransactions as $transaction) {
            $this->addIssue('orphaned_transaction', [
                'transaction_id' => $transaction->id,
                'agent_id' => $transaction->agent_id,
                'player_id' => $transaction->player_id,
                'severity' => 'medium',
                'description' => "交易記錄 ID {$transaction->id} 的關聯對象不存在"
            ]);
        }
    }

    /**
     * 檢查交易記錄完整性
     */
    private function checkTransactionIntegrity(): void
    {
        $this->info('🔍 檢查交易記錄完整性...');

        // 檢查交易餘額計算
        $agents = Agent::has('pointTransactions')->get();
        foreach ($agents as $agent) {
            $transactions = $agent->pointTransactions()
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $runningBalance = 0;
            foreach ($transactions as $transaction) {
                $expectedBalanceAfter = $runningBalance + $transaction->amount;
                
                if (abs($transaction->balance_after - $expectedBalanceAfter) > 0.01) {
                    $this->addIssue('transaction_balance_error', [
                        'transaction_id' => $transaction->id,
                        'agent_id' => $agent->id,
                        'agent_name' => $agent->name,
                        'expected_balance' => $expectedBalanceAfter,
                        'recorded_balance' => $transaction->balance_after,
                        'severity' => 'medium',
                        'description' => "代理 '{$agent->name}' 的交易記錄餘額計算錯誤"
                    ]);
                }
                
                $runningBalance = $transaction->balance_after;
            }
        }
    }

    /**
     * 檢查業務規則
     */
    private function checkBusinessRules(): void
    {
        $this->info('🔍 檢查業務規則...');

        // 檢查前置符號格式
        $invalidPrefixes = Agent::where('level', 1)
            ->where(function ($query) {
                $query->whereNull('prefix')
                      ->orWhere('prefix', '')
                      ->orWhereRaw('LENGTH(prefix) != 1')
                      ->orWhereRaw('prefix NOT REGEXP "^[a-z]$"');
            })
            ->get();

        foreach ($invalidPrefixes as $agent) {
            $this->addIssue('invalid_prefix_format', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'prefix' => $agent->prefix,
                'severity' => 'medium',
                'description' => "第一層代理 '{$agent->name}' 的前置符號格式無效"
            ]);
        }

        // 檢查帳號格式
        $invalidAccounts = collect()
            ->merge(Agent::whereRaw('account NOT REGEXP "^[a-z][a-zA-Z0-9_]+$"')->get())
            ->merge(Player::whereRaw('account NOT REGEXP "^[a-z][a-zA-Z0-9_]+$"')->get());

        foreach ($invalidAccounts as $record) {
            $type = $record instanceof Agent ? 'agent' : 'player';
            $this->addIssue('invalid_account_format', [
                'type' => $type,
                'id' => $record->id,
                'name' => $record->name,
                'account' => $record->account,
                'severity' => 'medium',
                'description' => ucfirst($type) . " '{$record->name}' 的帳號格式無效"
            ]);
        }
    }

    /**
     * 添加問題到列表
     */
    private function addIssue(string $type, array $data): void
    {
        $this->issues[] = array_merge(['type' => $type], $data);
        $this->statistics['issues_found']++;
        
        $severity = $data['severity'] ?? 'medium';
        $icon = match($severity) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪'
        };
        
        $this->warn("{$icon} {$data['description']}");
    }

    /**
     * 生成報告
     */
    private function generateReport(): void
    {
        $this->info("\n📋 檢查報告");
        $this->info("=" . str_repeat("=", 50));
        
        foreach ($this->statistics as $key => $value) {
            $label = match($key) {
                'total_agents' => '總代理數',
                'total_players' => '總玩家數',
                'total_transactions' => '總交易記錄',
                'active_agents' => '活躍代理數',
                'active_players' => '活躍玩家數',
                'total_points_allocated' => '總分配點數',
                'issues_found' => '發現問題數',
                'issues_fixed' => '已修復問題數',
                default => $key
            };
            $this->info("{$label}: {$value}");
        }

        if (!empty($this->issues)) {
            $this->info("\n🔍 問題詳情:");
            $groupedIssues = collect($this->issues)->groupBy('type');
            
            foreach ($groupedIssues as $type => $issues) {
                $this->info("\n{$type}: {$issues->count()} 個問題");
                foreach ($issues as $issue) {
                    $this->line("  - {$issue['description']}");
                }
            }
        }

        // 生成詳細報告文件
        if ($this->option('report')) {
            $this->generateDetailedReport();
        }
    }

    /**
     * 生成詳細報告文件
     */
    private function generateDetailedReport(): void
    {
        $reportPath = storage_path('logs/channel-integrity-report-' . date('Y-m-d-H-i-s') . '.json');
        
        $report = [
            'timestamp' => now()->toISOString(),
            'statistics' => $this->statistics,
            'issues' => $this->issues,
            'summary' => [
                'total_issues' => count($this->issues),
                'critical_issues' => collect($this->issues)->where('severity', 'critical')->count(),
                'high_issues' => collect($this->issues)->where('severity', 'high')->count(),
                'medium_issues' => collect($this->issues)->where('severity', 'medium')->count(),
                'low_issues' => collect($this->issues)->where('severity', 'low')->count(),
            ]
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("📄 詳細報告已生成: {$reportPath}");
    }

    /**
     * 執行自動修復
     */
    private function performAutoFix(): void
    {
        $this->info("\n🔧 開始自動修復...");
        
        foreach ($this->issues as $issue) {
            try {
                $fixed = $this->integrityService->autoFix($issue);
                if ($fixed) {
                    $this->statistics['issues_fixed']++;
                    $this->info("✅ 已修復: {$issue['description']}");
                } else {
                    $this->warn("⚠️  無法自動修復: {$issue['description']}");
                }
            } catch (\Exception $e) {
                $this->error("❌ 修復失敗: {$issue['description']} - {$e->getMessage()}");
            }
        }
        
        $this->info("🔧 自動修復完成，共修復 {$this->statistics['issues_fixed']} 個問題");
    }
}