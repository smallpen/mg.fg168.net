<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 安全服務
 * 
 * 處理安全相關的業務邏輯，包含密碼政策、登入限制、Session 管理等
 */
class SecurityService
{
    /**
     * 設定資料庫
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 建構子
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * 檢查密碼是否符合安全政策
     */
    public function validatePassword(string $password): array
    {
        $settings = $this->getPasswordPolicy();
        $errors = [];

        // 檢查最小長度
        if (strlen($password) < $settings['min_length']) {
            $errors[] = "密碼長度至少需要 {$settings['min_length']} 個字元";
        }

        // 檢查大寫字母
        if ($settings['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = '密碼必須包含至少一個大寫字母';
        }

        // 檢查小寫字母
        if ($settings['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = '密碼必須包含至少一個小寫字母';
        }

        // 檢查數字
        if ($settings['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = '密碼必須包含至少一個數字';
        }

        // 檢查特殊字元
        if ($settings['require_symbols'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = '密碼必須包含至少一個特殊字元';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password, $settings)
        ];
    }

    /**
     * 計算密碼強度
     */
    public function calculatePasswordStrength(string $password, ?array $settings = null): array
    {
        if (!$settings) {
            $settings = $this->getPasswordPolicy();
        }

        $score = 0;
        $maxScore = 6;

        // 長度評分
        if (strlen($password) >= $settings['min_length']) $score++;
        if (strlen($password) >= 12) $score++;

        // 複雜度評分
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $score++;

        $percentage = ($score / $maxScore) * 100;

        if ($percentage <= 33) {
            return ['level' => 'weak', 'score' => $score, 'percentage' => $percentage];
        } elseif ($percentage <= 66) {
            return ['level' => 'medium', 'score' => $score, 'percentage' => $percentage];
        } else {
            return ['level' => 'strong', 'score' => $score, 'percentage' => $percentage];
        }
    }

    /**
     * 檢查使用者密碼是否過期
     */
    public function isPasswordExpired(User $user): bool
    {
        $expiryDays = $this->getPasswordExpiryDays();
        
        if ($expiryDays <= 0) {
            return false; // 密碼不過期
        }

        $passwordUpdatedAt = $user->password_updated_at ?? $user->created_at;
        $expiryDate = $passwordUpdatedAt->addDays($expiryDays);

        return Carbon::now()->isAfter($expiryDate);
    }

    /**
     * 檢查使用者是否被鎖定
     */
    public function isUserLocked(string $identifier): bool
    {
        $lockoutKey = "login_lockout:{$identifier}";
        return Cache::has($lockoutKey);
    }

    /**
     * 記錄登入失敗
     */
    public function recordLoginFailure(string $identifier): void
    {
        $settings = $this->getLoginPolicy();
        $attemptsKey = "login_attempts:{$identifier}";
        $lockoutKey = "login_lockout:{$identifier}";

        // 增加失敗次數
        $attempts = Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addMinutes(60));

        // 檢查是否需要鎖定
        if ($attempts >= $settings['max_attempts']) {
            Cache::put($lockoutKey, true, now()->addMinutes($settings['lockout_duration']));
            Cache::forget($attemptsKey);

            // 記錄安全事件
            \Log::warning('User account locked due to too many failed login attempts', [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'lockout_duration' => $settings['lockout_duration'],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * 清除登入失敗記錄
     */
    public function clearLoginFailures(string $identifier): void
    {
        $attemptsKey = "login_attempts:{$identifier}";
        $lockoutKey = "login_lockout:{$identifier}";
        
        Cache::forget($attemptsKey);
        Cache::forget($lockoutKey);
    }

    /**
     * 取得剩餘鎖定時間（分鐘）
     */
    public function getRemainingLockoutTime(string $identifier): int
    {
        $lockoutKey = "login_lockout:{$identifier}";
        
        if (!Cache::has($lockoutKey)) {
            return 0;
        }

        $lockoutExpiry = Cache::get($lockoutKey . '_expiry');
        if (!$lockoutExpiry) {
            return 0;
        }

        $remaining = Carbon::parse($lockoutExpiry)->diffInMinutes(now(), false);
        return max(0, $remaining);
    }

    /**
     * 檢查是否啟用雙因子認證
     */
    public function isTwoFactorEnabled(): bool
    {
        $setting = $this->settingsRepository->getSetting('security.two_factor_enabled');
        return (bool) ($setting?->value ?? false);
    }

    /**
     * 檢查是否強制使用 HTTPS
     */
    public function isHttpsForced(): bool
    {
        $setting = $this->settingsRepository->getSetting('security.force_https');
        return (bool) ($setting?->value ?? false);
    }

    /**
     * 取得 Session 生命週期（分鐘）
     */
    public function getSessionLifetime(): int
    {
        $setting = $this->settingsRepository->getSetting('security.session_lifetime');
        return (int) ($setting?->value ?? 120);
    }

    /**
     * 取得密碼政策設定
     */
    public function getPasswordPolicy(): array
    {
        return Cache::remember('security.password_policy', 3600, function () {
            $settings = $this->settingsRepository->getSettings([
                'security.password_min_length',
                'security.password_require_uppercase',
                'security.password_require_lowercase',
                'security.password_require_numbers',
                'security.password_require_symbols',
                'security.password_expiry_days',
            ]);

            return [
                'min_length' => (int) ($settings->get('security.password_min_length')?->value ?? 8),
                'require_uppercase' => (bool) ($settings->get('security.password_require_uppercase')?->value ?? true),
                'require_lowercase' => (bool) ($settings->get('security.password_require_lowercase')?->value ?? true),
                'require_numbers' => (bool) ($settings->get('security.password_require_numbers')?->value ?? true),
                'require_symbols' => (bool) ($settings->get('security.password_require_symbols')?->value ?? false),
                'expiry_days' => (int) ($settings->get('security.password_expiry_days')?->value ?? 0),
            ];
        });
    }

    /**
     * 取得登入政策設定
     */
    public function getLoginPolicy(): array
    {
        return Cache::remember('security.login_policy', 3600, function () {
            $settings = $this->settingsRepository->getSettings([
                'security.login_max_attempts',
                'security.lockout_duration',
            ]);

            return [
                'max_attempts' => (int) ($settings->get('security.login_max_attempts')?->value ?? 5),
                'lockout_duration' => (int) ($settings->get('security.lockout_duration')?->value ?? 15),
            ];
        });
    }

    /**
     * 取得密碼過期天數
     */
    protected function getPasswordExpiryDays(): int
    {
        $setting = $this->settingsRepository->getSetting('security.password_expiry_days');
        return (int) ($setting?->value ?? 0);
    }

    /**
     * 清除安全設定快取
     */
    public function clearSecurityCache(): void
    {
        Cache::forget('security.password_policy');
        Cache::forget('security.login_policy');
        Cache::forget('security.session_policy');
    }

    /**
     * 記錄安全事件
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        \Log::info("Security event: {$event}", array_merge([
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $data));
    }
}