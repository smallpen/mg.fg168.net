<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SettingBackup;
use App\Models\SettingChange;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 設定安全服務
 * 
 * 提供設定相關的安全功能，包括加密、審計、備份加密等
 */
class SettingsSecurityService
{
    /**
     * 加密服務
     */
    protected EncryptionService $encryptionService;

    /**
     * 建構函式
     */
    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * 檢查設定是否需要加密
     */
    public function shouldEncryptSetting(string $key): bool
    {
        // 從配置檔案中取得需要加密的設定
        $encryptedSettings = config('system-settings.settings', []);
        
        if (isset($encryptedSettings[$key]['encrypted']) && $encryptedSettings[$key]['encrypted']) {
            return true;
        }

        // 根據鍵值模式判斷
        $encryptPatterns = [
            '*_secret*',
            '*_password*',
            '*_key*',
            '*_token*',
            '*client_secret*',
            '*webhook_secret*',
            '*api_keys*',
            'notification.smtp_password',
            'integration.*_client_secret',
            'integration.*_secret_key',
            'integration.stripe_secret_key',
            'integration.stripe_webhook_secret',
            'integration.paypal_client_secret',
            'integration.aws_secret_key',
            'integration.google_drive_client_secret',
            'integration.facebook_app_secret',
            'integration.github_client_secret',
        ];

        foreach ($encryptPatterns as $pattern) {
            if (fnmatch($pattern, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 安全地儲存設定值
     */
    public function secureStoreSetting(string $key, $value): array
    {
        $result = [
            'success' => false,
            'encrypted' => false,
            'stored_value' => $value,
            'error' => null
        ];

        try {
            // 檢查是否需要加密
            if ($this->shouldEncryptSetting($key)) {
                $encryptedValue = $this->encryptionService->encrypt(json_encode($value));
                $result['stored_value'] = $encryptedValue;
                $result['encrypted'] = true;
            }

            $result['success'] = true;
            
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error("Failed to secure store setting {$key}", [
                'error' => $e->getMessage(),
                'key' => $key
            ]);
        }

        return $result;
    }

    /**
     * 安全地讀取設定值
     */
    public function secureReadSetting(string $key, $storedValue)
    {
        try {
            // 如果設定需要加密，嘗試解密
            if ($this->shouldEncryptSetting($key) && !empty($storedValue)) {
                return json_decode($this->encryptionService->decrypt($storedValue), true);
            }

            return $storedValue;
            
        } catch (\Exception $e) {
            Log::warning("Failed to decrypt setting {$key}, returning original value", [
                'error' => $e->getMessage(),
                'key' => $key
            ]);
            
            // 如果解密失敗，返回原值（可能是未加密的舊資料）
            return $storedValue;
        }
    }

    /**
     * 建立加密的設定備份
     */
    public function createEncryptedBackup(string $name, string $description = '', array $categories = []): array
    {
        $result = [
            'success' => false,
            'backup_id' => null,
            'encrypted' => false,
            'error' => null
        ];

        try {
            // 取得設定資料
            $settingsRepo = app(\App\Repositories\SettingsRepositoryInterface::class);
            $settingsData = $settingsRepo->exportSettings($categories);

            // 加密敏感設定
            $encryptedData = $this->encryptSensitiveSettingsInBackup($settingsData);

            // 計算校驗碼
            $checksum = $this->calculateBackupChecksum($encryptedData);

            // 建立備份記錄
            $backup = SettingBackup::create([
                'name' => $name,
                'description' => $description,
                'settings_data' => $encryptedData,
                'created_by' => auth()->id(),
                'backup_type' => 'encrypted',
                'settings_count' => count($encryptedData),
                'checksum' => $checksum,
                'is_encrypted' => true,
            ]);

            $result['success'] = true;
            $result['backup_id'] = $backup->id;
            $result['encrypted'] = true;

            // 記錄備份建立事件
            $this->logSecurityEvent('encrypted_backup_created', [
                'backup_id' => $backup->id,
                'backup_name' => $name,
                'settings_count' => count($encryptedData),
                'categories' => $categories,
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('Failed to create encrypted backup', [
                'error' => $e->getMessage(),
                'name' => $name,
                'categories' => $categories
            ]);
        }

        return $result;
    }

    /**
     * 還原加密的設定備份
     */
    public function restoreEncryptedBackup(int $backupId): array
    {
        $result = [
            'success' => false,
            'restored_count' => 0,
            'error' => null
        ];

        try {
            $backup = SettingBackup::find($backupId);
            
            if (!$backup) {
                throw new \Exception('備份不存在');
            }

            // 驗證備份完整性
            if (!$this->verifyBackupIntegrity($backup)) {
                throw new \Exception('備份檔案完整性驗證失敗');
            }

            // 解密設定資料
            $decryptedData = $this->decryptSensitiveSettingsInBackup($backup->settings_data);

            // 還原設定
            $restoredCount = 0;
            foreach ($decryptedData as $settingData) {
                $setting = Setting::where('key', $settingData['key'])->first();
                
                if ($setting) {
                    $setting->value = $settingData['value'];
                    $setting->save();
                    $restoredCount++;
                } else {
                    // 建立新設定
                    Setting::create($settingData);
                    $restoredCount++;
                }
            }

            $result['success'] = true;
            $result['restored_count'] = $restoredCount;

            // 記錄還原事件
            $this->logSecurityEvent('encrypted_backup_restored', [
                'backup_id' => $backupId,
                'backup_name' => $backup->name,
                'restored_count' => $restoredCount,
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('Failed to restore encrypted backup', [
                'error' => $e->getMessage(),
                'backup_id' => $backupId
            ]);
        }

        return $result;
    }

    /**
     * 加密備份中的敏感設定
     */
    protected function encryptSensitiveSettingsInBackup(array $settingsData): array
    {
        $encryptedData = [];

        foreach ($settingsData as $setting) {
            $settingData = $setting;
            
            // 如果是敏感設定，加密其值
            if ($this->shouldEncryptSetting($setting['key'])) {
                try {
                    $settingData['value'] = $this->encryptionService->encrypt(json_encode($setting['value']));
                    $settingData['is_encrypted_in_backup'] = true;
                } catch (\Exception $e) {
                    Log::warning("Failed to encrypt setting in backup: {$setting['key']}", [
                        'error' => $e->getMessage()
                    ]);
                    // 如果加密失敗，保持原值但標記為未加密
                    $settingData['is_encrypted_in_backup'] = false;
                }
            } else {
                $settingData['is_encrypted_in_backup'] = false;
            }

            $encryptedData[] = $settingData;
        }

        return $encryptedData;
    }

    /**
     * 解密備份中的敏感設定
     */
    protected function decryptSensitiveSettingsInBackup(array $encryptedData): array
    {
        $decryptedData = [];

        foreach ($encryptedData as $setting) {
            $settingData = $setting;
            
            // 如果設定在備份中被加密，解密其值
            if (isset($setting['is_encrypted_in_backup']) && $setting['is_encrypted_in_backup']) {
                try {
                    $settingData['value'] = json_decode($this->encryptionService->decrypt($setting['value']), true);
                } catch (\Exception $e) {
                    Log::warning("Failed to decrypt setting from backup: {$setting['key']}", [
                        'error' => $e->getMessage()
                    ]);
                    // 如果解密失敗，跳過此設定
                    continue;
                }
            }

            // 移除備份特定的欄位
            unset($settingData['is_encrypted_in_backup']);
            $decryptedData[] = $settingData;
        }

        return $decryptedData;
    }

    /**
     * 計算備份校驗碼
     */
    protected function calculateBackupChecksum(array $data): string
    {
        // 建立一個穩定的字串表示用於校驗碼計算
        $dataString = json_encode($data, JSON_SORT_KEYS);
        return hash('sha256', $dataString);
    }

    /**
     * 驗證備份完整性
     */
    public function verifyBackupIntegrity(SettingBackup $backup): bool
    {
        try {
            // 重新計算校驗碼
            $calculatedChecksum = $this->calculateBackupChecksum($backup->settings_data);
            
            // 比較校驗碼
            if ($backup->checksum !== $calculatedChecksum) {
                Log::warning('Backup integrity check failed', [
                    'backup_id' => $backup->id,
                    'stored_checksum' => $backup->checksum,
                    'calculated_checksum' => $calculatedChecksum
                ]);
                return false;
            }

            return true;
            
        } catch (\Exception $e) {
            Log::error('Error verifying backup integrity', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 記錄設定變更的詳細審計日誌
     */
    public function logSettingChange(string $settingKey, $oldValue, $newValue, ?string $reason = null): void
    {
        try {
            // 建立變更記錄
            $change = SettingChange::create([
                'setting_key' => $settingKey,
                'old_value' => $this->sanitizeValueForLog($settingKey, $oldValue),
                'new_value' => $this->sanitizeValueForLog($settingKey, $newValue),
                'changed_by' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'reason' => $reason,
            ]);

            // 記錄到安全日誌
            $this->logSecurityEvent('setting_changed', [
                'setting_key' => $settingKey,
                'change_id' => $change->id,
                'is_sensitive' => $this->shouldEncryptSetting($settingKey),
                'reason' => $reason,
            ]);

            // 如果是敏感設定變更，發送通知
            if ($this->shouldEncryptSetting($settingKey)) {
                $this->notifySensitiveSettingChange($settingKey, $change);
            }

        } catch (\Exception $e) {
            Log::error('Failed to log setting change', [
                'setting_key' => $settingKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 清理日誌值（隱藏敏感資訊）
     */
    protected function sanitizeValueForLog(string $settingKey, $value)
    {
        if ($this->shouldEncryptSetting($settingKey)) {
            // 對於敏感設定，只記錄是否有值，不記錄實際值
            return $value ? '[ENCRYPTED_VALUE]' : null;
        }

        return $value;
    }

    /**
     * 通知敏感設定變更
     */
    protected function notifySensitiveSettingChange(string $settingKey, SettingChange $change): void
    {
        try {
            // 取得需要通知的管理員
            $admins = \App\Models\User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->where('is_active', true)->get();

            foreach ($admins as $admin) {
                // 發送敏感設定變更通知
                $admin->notify(new \App\Notifications\SensitiveSettingChanged($settingKey, $change));
            }

        } catch (\Exception $e) {
            Log::error('Failed to notify sensitive setting change', [
                'setting_key' => $settingKey,
                'change_id' => $change->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 檢查設定存取權限
     */
    public function checkSettingAccess(string $settingKey, string $action = 'read'): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // 超級管理員可以存取所有設定
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // 檢查基本設定權限
        if (!$user->hasPermission('settings.view')) {
            return false;
        }

        // 檢查敏感設定權限（需要編輯權限）
        if ($this->shouldEncryptSetting($settingKey)) {
            if (!$user->hasPermission('settings.edit')) {
                return false;
            }
        }

        // 檢查系統設定權限
        if ($this->isSystemSetting($settingKey)) {
            if (!$user->hasRole('super_admin')) {
                return false;
            }
        }

        // 檢查特定動作權限
        switch ($action) {
            case 'write':
            case 'update':
                return $user->hasPermission('settings.edit');
            case 'delete':
                return $user->hasPermission('settings.delete');
            case 'backup':
                return $user->hasPermission('settings.backup');
            case 'read':
            default:
                return true; // 已經通過基本權限檢查
        }
    }

    /**
     * 檢查是否為系統設定
     */
    protected function isSystemSetting(string $settingKey): bool
    {
        $systemSettingPrefixes = [
            'app.',
            'system.',
            'security.force_https',
            'security.session_lifetime',
            'maintenance.maintenance_mode',
        ];

        foreach ($systemSettingPrefixes as $prefix) {
            if (strpos($settingKey, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 記錄安全事件
     */
    protected function logSecurityEvent(string $event, array $context = []): void
    {
        $logData = array_merge([
            'event' => $event,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('security')->info("Settings security event: {$event}", $logData);
    }

    /**
     * 清理過期的審計日誌
     */
    public function cleanupAuditLogs(int $retentionDays = 90): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        $deletedCount = SettingChange::where('created_at', '<', $cutoffDate)->delete();
        
        Log::info('Cleaned up settings audit logs', [
            'retention_days' => $retentionDays,
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toISOString()
        ]);

        return $deletedCount;
    }

    /**
     * 產生設定安全報告
     */
    public function generateSecurityReport(): array
    {
        $report = [
            'generated_at' => now()->toISOString(),
            'total_settings' => Setting::count(),
            'encrypted_settings' => 0,
            'recent_changes' => SettingChange::where('created_at', '>=', now()->subDays(7))->count(),
            'sensitive_changes' => 0,
            'backup_count' => SettingBackup::count(),
            'encrypted_backups' => SettingBackup::where('is_encrypted', true)->count(),
            'security_events' => [],
        ];

        // 計算加密設定數量
        $allSettings = Setting::all();
        foreach ($allSettings as $setting) {
            if ($this->shouldEncryptSetting($setting->key)) {
                $report['encrypted_settings']++;
            }
        }

        // 計算敏感設定變更數量
        $recentChanges = SettingChange::where('created_at', '>=', now()->subDays(7))->get();
        foreach ($recentChanges as $change) {
            if ($this->shouldEncryptSetting($change->setting_key)) {
                $report['sensitive_changes']++;
            }
        }

        return $report;
    }
}