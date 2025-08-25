<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * 列出 API Token 命令
 */
class ListApiTokens extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'api:list-tokens 
                            {--user= : 特定使用者的 Token}
                            {--expired : 只顯示已過期的 Token}
                            {--active : 只顯示有效的 Token}';

    /**
     * 命令描述
     */
    protected $description = '列出 API Token';

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $userFilter = $this->option('user');
        $showExpired = $this->option('expired');
        $showActive = $this->option('active');

        $query = PersonalAccessToken::with('tokenable');

        // 篩選使用者
        if ($userFilter) {
            $user = $this->findUser($userFilter);
            if (!$user) {
                $this->error("找不到使用者: {$userFilter}");
                return 1;
            }
            $query->where('tokenable_id', $user->id)
                  ->where('tokenable_type', User::class);
        }

        // 篩選過期狀態
        if ($showExpired) {
            $query->where('expires_at', '<', now());
        } elseif ($showActive) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
        }

        $tokens = $query->orderBy('created_at', 'desc')->get();

        if ($tokens->isEmpty()) {
            $this->info('沒有找到符合條件的 Token');
            return 0;
        }

        // 準備表格資料
        $headers = ['ID', '名稱', '使用者', '能力', '最後使用', '過期時間', '狀態'];
        $rows = [];

        foreach ($tokens as $token) {
            $user = $token->tokenable;
            $abilities = implode(', ', $token->abilities ?: ['*']);
            $lastUsed = $token->last_used_at ? $token->last_used_at->diffForHumans() : '從未使用';
            $expiresAt = $token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : '永不過期';
            
            $status = $this->getTokenStatus($token);
            
            $rows[] = [
                $token->id,
                $token->name,
                $user ? "{$user->name} ({$user->username})" : 'N/A',
                $abilities,
                $lastUsed,
                $expiresAt,
                $status
            ];
        }

        $this->table($headers, $rows);

        $this->info("總計: {$tokens->count()} 個 Token");

        return 0;
    }

    /**
     * 取得 Token 狀態
     */
    private function getTokenStatus(PersonalAccessToken $token): string
    {
        if ($token->expires_at && $token->expires_at->isPast()) {
            return '已過期';
        }

        if (!$token->last_used_at) {
            return '未使用';
        }

        if ($token->last_used_at->isAfter(now()->subDays(7))) {
            return '活躍';
        }

        return '閒置';
    }

    /**
     * 尋找使用者
     */
    private function findUser(string $identifier): ?User
    {
        if (is_numeric($identifier)) {
            return User::find($identifier);
        }

        return User::where('username', $identifier)->first();
    }
}