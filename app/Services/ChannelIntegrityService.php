<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ChannelIntegrityService
{
    /**
     * 獲取代理的預期前置符號
     */
    public function getExpectedPrefix(Agent $agent): string
    {
        if ($agent->level === 1) {
            return $agent->prefix ?? '';
        }

        $current = $agent;
        while ($current->parent && $current->parent->level > 1) {
            $current = $current->parent;
        }

        return $current->parent?->prefix ?? '';
    }

    /**
     * 從帳號中提取前置符號
     */
    public function extractPrefix(string $account): string
    {
        if (preg_match('/^([a-z])/', $account, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * 檢查代理是否存在循環引用
     */
    public function hasCircularReference(Agent $agent): bool
    {
        $visited = [];
        $current = $agent;

        while ($current && $current->parent_id) {
            if (in_array($current->id, $visited)) {
                return true;
            }
            $visited[] = $current->id;
            $current = $current->parent;
        }

        return false;
    }

    /**
     * 計算代理的正確層級
     */
    public function calculateLevel(Agent $agent): int
    {
        if (!$agent->parent_id) {
            return 1;
        }

        $level = 1;
        $current = $agent;
        $visited = [];

        while ($current->parent_id) {
            if (in_array($current->id, $visited)) {
                // 循環引用，返回錯誤值
                return -1;
            }
            $visited[] = $current->id;
            $current = $current->parent;
            $level++;
        }

        return $level;
    }

    /**
     * 計算代理的已分配點數
     */
    public function calculateAllocatedPoints(Agent $agent): float
    {
        $childrenPoints = $agent->children()->sum('total_points');
        $playersPoints = $agent->players()->sum('points');
        
        return $childrenPoints + $playersPoints;
    }

    /**
     * 自動修復問題
     */
    public function autoFix(array $issue): bool
    {
        try {
            return match($issue['type']) {
                'level_mismatch' => $this->fixLevelMismatch($issue),
                'points_allocated_mismatch' => $this->fixPointsAllocatedMismatch($issue),
                'points_remaining_mismatch' => $this->fixPointsRemainingMismatch($issue),
                'prefix_mismatch' => $this->fixPrefixMismatch($issue),
                'player_prefix_mismatch' => $this->fixPlayerPrefixMismatch($issue),
                'orphaned_player' => $this->fixOrphanedPlayer($issue),
                'orphaned_transaction' => $this->fixOrphanedTransaction($issue),
                'invalid_account_format' => $this->fixInvalidAccountFormat($issue),
                default => false
            };
        } catch (\Exception $e) {
            Log::error('Auto-fix failed', [
                'issue' => $issue,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 修復層級不匹配問題
     */
    private function fixLevelMismatch(array $issue): bool
    {
        $agent = Agent::find($issue['agent_id']);
        if (!$agent) {
            return false;
        }

        $correctLevel = $this->calculateLevel($agent);
        if ($correctLevel > 0) {
            $agent->update(['level' => $correctLevel]);
            return true;
        }

        return false;
    }

    /**
     * 修復已分配點數不匹配問題
     */
    private function fixPointsAllocatedMismatch(array $issue): bool
    {
        $agent = Agent::find($issue['agent_id']);
        if (!$agent) {
            return false;
        }

        $correctAllocated = $this->calculateAllocatedPoints($agent);
        $correctRemaining = $agent->total_points - $correctAllocated;

        $agent->update([
            'allocated_points' => $correctAllocated,
            'remaining_points' => $correctRemaining
        ]);

        return true;
    }

    /**
     * 修復剩餘點數不匹配問題
     */
    private function fixPointsRemainingMismatch(array $issue): bool
    {
        return $this->fixPointsAllocatedMismatch($issue);
    }

    /**
     * 修復代理前置符號不匹配問題
     */
    private function fixPrefixMismatch(array $issue): bool
    {
        $agent = Agent::find($issue['agent_id']);
        if (!$agent) {
            return false;
        }

        $expectedPrefix = $this->getExpectedPrefix($agent);
        $newAccount = $expectedPrefix . $agent->username;

        // 檢查新帳號是否已存在
        if (Agent::where('account', $newAccount)->where('id', '!=', $agent->id)->exists() ||
            Player::where('account', $newAccount)->exists()) {
            return false;
        }

        $agent->update(['account' => $newAccount]);
        return true;
    }

    /**
     * 修復玩家前置符號不匹配問題
     */
    private function fixPlayerPrefixMismatch(array $issue): bool
    {
        $player = Player::find($issue['player_id']);
        if (!$player || !$player->agent) {
            return false;
        }

        $expectedPrefix = $this->getExpectedPrefix($player->agent);
        $newAccount = $expectedPrefix . $player->username;

        // 檢查新帳號是否已存在
        if (Player::where('account', $newAccount)->where('id', '!=', $player->id)->exists() ||
            Agent::where('account', $newAccount)->exists()) {
            return false;
        }

        $player->update(['account' => $newAccount]);
        return true;
    }

    /**
     * 修復孤立玩家問題
     */
    private function fixOrphanedPlayer(array $issue): bool
    {
        $player = Player::find($issue['player_id']);
        if (!$player) {
            return false;
        }

        // 嘗試找到合適的代理
        $prefix = $this->extractPrefix($player->account);
        $suitableAgent = Agent::where('prefix', $prefix)
            ->where('level', 1)
            ->where('is_active', true)
            ->first();

        if ($suitableAgent) {
            $player->update(['agent_id' => $suitableAgent->id]);
            return true;
        }

        return false;
    }

    /**
     * 修復孤立交易記錄問題
     */
    private function fixOrphanedTransaction(array $issue): bool
    {
        $transaction = PointTransaction::find($issue['transaction_id']);
        if (!$transaction) {
            return false;
        }

        // 軟刪除孤立的交易記錄
        $transaction->delete();
        return true;
    }

    /**
     * 修復無效帳號格式問題
     */
    private function fixInvalidAccountFormat(array $issue): bool
    {
        $model = $issue['type'] === 'agent' ? Agent::find($issue['id']) : Player::find($issue['id']);
        if (!$model) {
            return false;
        }

        // 生成新的有效帳號
        $prefix = $issue['type'] === 'agent' 
            ? $this->getExpectedPrefix($model)
            : $this->getExpectedPrefix($model->agent);

        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $model->username);
        $newAccount = $prefix . $baseUsername;

        // 確保帳號唯一性
        $counter = 1;
        $originalAccount = $newAccount;
        while ($this->accountExists($newAccount, $issue['type'], $issue['id'])) {
            $newAccount = $originalAccount . $counter;
            $counter++;
        }

        $model->update(['account' => $newAccount]);
        return true;
    }

    /**
     * 檢查帳號是否已存在
     */
    private function accountExists(string $account, string $type, int $excludeId): bool
    {
        $agentExists = Agent::where('account', $account)
            ->when($type === 'agent', fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        $playerExists = Player::where('account', $account)
            ->when($type === 'player', fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        return $agentExists || $playerExists;
    }

    /**
     * 執行點數一致性稽核
     */
    public function auditPointsConsistency(): array
    {
        $results = [
            'total_agents_checked' => 0,
            'agents_with_issues' => 0,
            'total_discrepancy' => 0,
            'issues' => []
        ];

        $agents = Agent::with(['children', 'players'])->get();
        $results['total_agents_checked'] = $agents->count();

        foreach ($agents as $agent) {
            $calculatedAllocated = $this->calculateAllocatedPoints($agent);
            $calculatedRemaining = $agent->total_points - $calculatedAllocated;

            $allocatedDiscrepancy = abs($agent->allocated_points - $calculatedAllocated);
            $remainingDiscrepancy = abs($agent->remaining_points - $calculatedRemaining);

            if ($allocatedDiscrepancy > 0.01 || $remainingDiscrepancy > 0.01) {
                $results['agents_with_issues']++;
                $results['total_discrepancy'] += $allocatedDiscrepancy + $remainingDiscrepancy;
                
                $results['issues'][] = [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'allocated_discrepancy' => $allocatedDiscrepancy,
                    'remaining_discrepancy' => $remainingDiscrepancy,
                    'recorded_allocated' => $agent->allocated_points,
                    'calculated_allocated' => $calculatedAllocated,
                    'recorded_remaining' => $agent->remaining_points,
                    'calculated_remaining' => $calculatedRemaining
                ];
            }
        }

        return $results;
    }

    /**
     * 重新計算所有代理的點數
     */
    public function recalculateAllPoints(): array
    {
        $results = [
            'agents_updated' => 0,
            'total_corrections' => 0,
            'errors' => []
        ];

        DB::beginTransaction();
        
        try {
            $agents = Agent::with(['children', 'players'])->get();
            
            foreach ($agents as $agent) {
                $calculatedAllocated = $this->calculateAllocatedPoints($agent);
                $calculatedRemaining = $agent->total_points - $calculatedAllocated;

                if (abs($agent->allocated_points - $calculatedAllocated) > 0.01 ||
                    abs($agent->remaining_points - $calculatedRemaining) > 0.01) {
                    
                    $agent->update([
                        'allocated_points' => $calculatedAllocated,
                        'remaining_points' => $calculatedRemaining
                    ]);

                    $results['agents_updated']++;
                    $results['total_corrections'] += abs($agent->allocated_points - $calculatedAllocated) + 
                                                   abs($agent->remaining_points - $calculatedRemaining);
                }
            }

            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * 驗證資料備份完整性
     */
    public function validateBackupIntegrity(string $backupPath): array
    {
        $results = [
            'valid' => false,
            'tables_checked' => 0,
            'records_validated' => 0,
            'issues' => []
        ];

        try {
            if (!file_exists($backupPath)) {
                $results['issues'][] = '備份檔案不存在';
                return $results;
            }

            // 讀取備份檔案
            $backupData = json_decode(file_get_contents($backupPath), true);
            
            if (!$backupData) {
                $results['issues'][] = '備份檔案格式無效';
                return $results;
            }

            // 驗證必要的表格
            $requiredTables = ['agents', 'players', 'point_transactions'];
            foreach ($requiredTables as $table) {
                if (!isset($backupData[$table])) {
                    $results['issues'][] = "缺少表格: {$table}";
                } else {
                    $results['tables_checked']++;
                    $results['records_validated'] += count($backupData[$table]);
                }
            }

            // 驗證資料完整性
            if (isset($backupData['agents'])) {
                foreach ($backupData['agents'] as $agent) {
                    if (!isset($agent['id'], $agent['name'], $agent['account'])) {
                        $results['issues'][] = '代理資料缺少必要欄位';
                    }
                }
            }

            if (isset($backupData['players'])) {
                foreach ($backupData['players'] as $player) {
                    if (!isset($player['id'], $player['name'], $player['account'], $player['agent_id'])) {
                        $results['issues'][] = '玩家資料缺少必要欄位';
                    }
                }
            }

            $results['valid'] = empty($results['issues']);

        } catch (\Exception $e) {
            $results['issues'][] = '驗證過程發生錯誤: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * 建立資料備份
     */
    public function createBackup(): string
    {
        $timestamp = now()->format('Y-m-d-H-i-s');
        $backupPath = storage_path("backups/channel-backup-{$timestamp}.json");

        // 確保備份目錄存在
        $backupDir = dirname($backupPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupData = [
            'timestamp' => now()->toISOString(),
            'version' => '1.0',
            'agents' => Agent::all()->toArray(),
            'players' => Player::all()->toArray(),
            'point_transactions' => PointTransaction::all()->toArray(),
            'metadata' => [
                'total_agents' => Agent::count(),
                'total_players' => Player::count(),
                'total_transactions' => PointTransaction::count(),
                'total_points' => Agent::sum('total_points')
            ]
        ];

        file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $backupPath;
    }

    /**
     * 還原資料備份
     */
    public function restoreBackup(string $backupPath): array
    {
        $results = [
            'success' => false,
            'restored_records' => 0,
            'errors' => []
        ];

        // 先驗證備份完整性
        $validation = $this->validateBackupIntegrity($backupPath);
        if (!$validation['valid']) {
            $results['errors'] = $validation['issues'];
            return $results;
        }

        DB::beginTransaction();
        
        try {
            $backupData = json_decode(file_get_contents($backupPath), true);

            // 清空現有資料
            PointTransaction::truncate();
            Player::truncate();
            Agent::truncate();

            // 還原代理資料
            foreach ($backupData['agents'] as $agentData) {
                Agent::create($agentData);
                $results['restored_records']++;
            }

            // 還原玩家資料
            foreach ($backupData['players'] as $playerData) {
                Player::create($playerData);
                $results['restored_records']++;
            }

            // 還原交易記錄
            foreach ($backupData['point_transactions'] as $transactionData) {
                PointTransaction::create($transactionData);
                $results['restored_records']++;
            }

            DB::commit();
            $results['success'] = true;

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = '還原過程發生錯誤: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * 檢查系統健康狀態
     */
    public function checkSystemHealth(): array
    {
        return [
            'database_connection' => $this->checkDatabaseConnection(),
            'table_integrity' => $this->checkTableIntegrity(),
            'data_consistency' => $this->checkDataConsistency(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];
    }

    /**
     * 檢查資料庫連接
     */
    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => '資料庫連接正常'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => '資料庫連接失敗: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查表格完整性
     */
    private function checkTableIntegrity(): array
    {
        $tables = ['agents', 'players', 'point_transactions'];
        $results = [];

        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $results[$table] = ['status' => 'healthy', 'count' => $count];
            } catch (\Exception $e) {
                $results[$table] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * 檢查資料一致性
     */
    private function checkDataConsistency(): array
    {
        $audit = $this->auditPointsConsistency();
        
        return [
            'status' => $audit['agents_with_issues'] === 0 ? 'healthy' : 'warning',
            'agents_checked' => $audit['total_agents_checked'],
            'issues_found' => $audit['agents_with_issues'],
            'total_discrepancy' => $audit['total_discrepancy']
        ];
    }

    /**
     * 獲取效能指標
     */
    private function getPerformanceMetrics(): array
    {
        $start = microtime(true);
        
        // 執行一些基本查詢來測試效能
        Agent::count();
        Player::count();
        PointTransaction::count();
        
        $queryTime = microtime(true) - $start;

        return [
            'query_response_time' => round($queryTime * 1000, 2) . 'ms',
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
        ];
    }
}