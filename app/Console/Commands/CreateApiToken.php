<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * 建立 API Token 命令
 */
class CreateApiToken extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'api:create-token 
                            {user : 使用者 ID 或使用者名稱}
                            {name : Token 名稱}
                            {--abilities=* : Token 能力列表}
                            {--expires= : 過期時間（天數）}';

    /**
     * 命令描述
     */
    protected $description = '為使用者建立 API Token';

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $tokenName = $this->argument('name');
        $abilities = $this->option('abilities') ?: ['activities:read'];
        $expiresDays = $this->option('expires');

        // 尋找使用者
        $user = $this->findUser($userIdentifier);
        if (!$user) {
            $this->error("找不到使用者: {$userIdentifier}");
            return 1;
        }

        // 檢查使用者權限
        if (!$user->can('activity_logs.view')) {
            $this->error("使用者 {$user->username} 沒有存取活動記錄的權限");
            return 1;
        }

        // 建立 Token
        $token = $user->createToken($tokenName, $abilities);

        // 設定過期時間
        if ($expiresDays) {
            $expiresAt = now()->addDays((int) $expiresDays);
            $token->accessToken->expires_at = $expiresAt;
            $token->accessToken->save();
        }

        // 顯示結果
        $this->info("API Token 建立成功！");
        $this->line("使用者: {$user->name} ({$user->username})");
        $this->line("Token 名稱: {$tokenName}");
        $this->line("能力: " . implode(', ', $abilities));
        
        if ($expiresDays) {
            $this->line("過期時間: {$token->accessToken->expires_at}");
        } else {
            $this->line("過期時間: 永不過期");
        }
        
        $this->newLine();
        $this->warn("請妥善保存以下 Token，它只會顯示一次：");
        $this->line($token->plainTextToken);

        return 0;
    }

    /**
     * 尋找使用者
     */
    private function findUser(string $identifier): ?User
    {
        // 嘗試以 ID 尋找
        if (is_numeric($identifier)) {
            return User::find($identifier);
        }

        // 嘗試以使用者名稱尋找
        return User::where('username', $identifier)->first();
    }
}