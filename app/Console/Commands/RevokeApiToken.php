<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * 撤銷 API Token 命令
 */
class RevokeApiToken extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'api:revoke-token 
                            {token : Token ID 或 Token 名稱}
                            {--user= : 限制特定使用者的 Token}
                            {--force : 強制撤銷，不需要確認}';

    /**
     * 命令描述
     */
    protected $description = '撤銷 API Token';

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $tokenIdentifier = $this->argument('token');
        $userFilter = $this->option('user');
        $force = $this->option('force');

        // 尋找 Token
        $tokens = $this->findTokens($tokenIdentifier, $userFilter);

        if ($tokens->isEmpty()) {
            $this->error("找不到符合條件的 Token: {$tokenIdentifier}");
            return 1;
        }

        if ($tokens->count() > 1) {
            $this->error("找到多個符合條件的 Token，請使用更具體的識別條件");
            $this->listTokens($tokens);
            return 1;
        }

        $token = $tokens->first();
        $user = $token->tokenable;

        // 顯示 Token 資訊
        $this->info("準備撤銷以下 Token：");
        $this->line("ID: {$token->id}");
        $this->line("名稱: {$token->name}");
        $this->line("使用者: {$user->name} ({$user->username})");
        $this->line("建立時間: {$token->created_at}");
        $this->line("最後使用: " . ($token->last_used_at ? $token->last_used_at : '從未使用'));

        // 確認撤銷
        if (!$force && !$this->confirm('確定要撤銷此 Token 嗎？')) {
            $this->info('操作已取消');
            return 0;
        }

        // 撤銷 Token
        $token->delete();

        $this->info("Token 已成功撤銷");

        return 0;
    }

    /**
     * 尋找 Token
     */
    private function findTokens(string $identifier, ?string $userFilter)
    {
        $query = PersonalAccessToken::with('tokenable');

        // 嘗試以 ID 尋找
        if (is_numeric($identifier)) {
            $query->where('id', $identifier);
        } else {
            // 嘗試以名稱尋找
            $query->where('name', $identifier);
        }

        // 篩選使用者
        if ($userFilter) {
            $user = $this->findUser($userFilter);
            if ($user) {
                $query->where('tokenable_id', $user->id)
                      ->where('tokenable_type', get_class($user));
            }
        }

        return $query->get();
    }

    /**
     * 列出 Token
     */
    private function listTokens($tokens): void
    {
        $headers = ['ID', '名稱', '使用者', '建立時間'];
        $rows = [];

        foreach ($tokens as $token) {
            $user = $token->tokenable;
            $rows[] = [
                $token->id,
                $token->name,
                $user ? "{$user->name} ({$user->username})" : 'N/A',
                $token->created_at->format('Y-m-d H:i:s')
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * 尋找使用者
     */
    private function findUser(string $identifier)
    {
        if (is_numeric($identifier)) {
            return \App\Models\User::find($identifier);
        }

        return \App\Models\User::where('username', $identifier)->first();
    }
}