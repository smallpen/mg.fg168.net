<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use App\Services\SettingsSecurityService;
use App\Http\Middleware\SettingsAccessControl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * 安全設定管理元件
 * 
 * 提供密碼政策、登入安全、Session 管理、雙因子認證等安全設定的管理功能
 */
class SecuritySettings extends AdminComponent
{
    /**
     * 安全設定值
     */
    public array $settings = [];

    /**
     * 原始設定值（用於比較變更）
     */
    public array $originalSettings = [];

    /**
     * 正在儲存
     */
    public bool $saving = false;

    /**
     * 顯示影響範圍警告
     */
    public bool $showImpactWarning = false;

    /**
     * 影響範圍資訊
     */
    public array $impactInfo = [];

    /**
     * 驗證錯誤
     */
    public array $validationErrors = [];

    /**
     * 安全統計資訊
     */
    public array $securityStats = [];

    /**
     * 顯示 IP 管理對話框
     */
    public bool $showIpManagement = false;

    /**
     * 顯示審計日誌清理對話框
     */
    public bool $showAuditCleanup = false;

    /**
     * 成功訊息
     */
    public ?string $successMessage = null;

    /**
     * 錯誤訊息
     */
    public ?string $errorMessage = null;

    /**
     * 安全設定鍵值列表
     */
    protected array $securitySettingKeys = [
        'security.password_min_length',
        'security.password_require_uppercase',
        'security.password_require_lowercase',
        'security.password_require_numbers',
        'security.password_require_symbols',
        'security.password_expiry_days',
        'security.login_max_attempts',
        'security.lockout_duration',
        'security.session_lifetime',
        'security.force_https',
        'security.two_factor_enabled',
        'security.allowed_ips',
        'security.enable_audit_logging',
        'security.audit_log_retention_days',
    ];

    /**
     * 取得設定資料庫
     */
    protected function getSettingsRepository(): SettingsRepositoryInterface
    {
        return app(SettingsRepositoryInterface::class);
    }

    /**
     * 取得配置服務
     */
    protected function getConfigService(): ConfigurationService
    {
        return app(ConfigurationService::class);
    }

    /**
     * 取得安全服務
     */
    protected function getSecurityService(): SettingsSecurityService
    {
        return app(SettingsSecurityService::class);
    }

    /**
     * 初始化元件
     */
    public function mount(): void
    {
        \Log::info('SecuritySettings mount called');
        parent::mount();
        $this->loadSettings();
        $this->loadSecurityStats();
        \Log::info('SecuritySettings mount completed');
    }

    /**
     * 載入設定資料
     */
    public function loadSettings(): void
    {
        \Log::info('SecuritySettings loadSettings called');
        
        // 使用硬編碼預設值進行測試
        $this->settings = [
            'security' => [
                'password_min_length' => 8,
                'password_require_uppercase' => true,
                'password_require_lowercase' => true,
                'password_require_numbers' => true,
                'password_require_symbols' => false,
                'password_expiry_days' => 0,
                'login_max_attempts' => 5,
                'lockout_duration' => 15,
                'session_lifetime' => 120,
                'force_https' => false,
                'two_factor_enabled' => false,
                'allowed_ips' => '',
                'enable_audit_logging' => true,
                'audit_log_retention_days' => 90,
            ]
        ];
        $this->originalSettings = $this->settings;
        
        \Log::info('SecuritySettings loadSettings completed', [
            'settings' => $this->settings
        ]);
    }

    /**
     * 取得設定的預設值
     */
    protected function getDefaultValue(string $key)
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        return $config['default'] ?? '';
    }

    /**
     * 檢查是否有變更
     */
    #[Computed]
    public function hasChanges(): bool
    {
        foreach ($this->securitySettingKeys as $key) {
            $keyParts = explode('.', $key);
            if (count($keyParts) === 2 && $keyParts[0] === 'security') {
                $settingKey = $keyParts[1];
                $currentValue = $this->settings['security'][$settingKey] ?? '';
                $originalValue = $this->originalSettings['security'][$settingKey] ?? '';
                
                if ($currentValue !== $originalValue) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 取得變更的設定
     */
    #[Computed]
    public function changedSettings(): array
    {
        $changed = [];
        foreach ($this->securitySettingKeys as $key) {
            $keyParts = explode('.', $key);
            if (count($keyParts) === 2 && $keyParts[0] === 'security') {
                $settingKey = $keyParts[1];
                $currentValue = $this->settings['security'][$settingKey] ?? '';
                $originalValue = $this->originalSettings['security'][$settingKey] ?? '';
                
                if ($currentValue !== $originalValue) {
                    $changed[$key] = [
                        'old' => $originalValue,
                        'new' => $currentValue,
                    ];
                }
            }
        }
        return $changed;
    }

    /**
     * 取得密碼強度等級
     */
    #[Computed]
    public function passwordStrengthLevel(): string
    {
        $score = 0;
        $minLength = (int) ($this->settings['security.password_min_length'] ?? 8);
        
        if ($minLength >= 8) $score++;
        if ($minLength >= 12) $score++;
        if ($this->settings['security.password_require_uppercase'] ?? false) $score++;
        if ($this->settings['security.password_require_lowercase'] ?? false) $score++;
        if ($this->settings['security.password_require_numbers'] ?? false) $score++;
        if ($this->settings['security.password_require_symbols'] ?? false) $score++;
        
        if ($score <= 2) return 'weak';
        if ($score <= 4) return 'medium';
        return 'strong';
    }

    /**
     * 取得密碼強度描述
     */
    #[Computed]
    public function passwordStrengthDescription(): array
    {
        $level = $this->passwordStrengthLevel;
        
        return match($level) {
            'weak' => [
                'text' => '弱',
                'color' => 'text-red-600',
                'bg' => 'bg-red-100 dark:bg-red-900/20',
                'description' => '密碼政策較寬鬆，建議加強要求'
            ],
            'medium' => [
                'text' => '中等',
                'color' => 'text-yellow-600',
                'bg' => 'bg-yellow-100 dark:bg-yellow-900/20',
                'description' => '密碼政策適中，提供基本安全保護'
            ],
            'strong' => [
                'text' => '強',
                'color' => 'text-green-600',
                'bg' => 'bg-green-100 dark:bg-green-900/20',
                'description' => '密碼政策嚴格，提供高度安全保護'
            ],
        };
    }

    /**
     * 儲存設定
     */
    public function save(): void
    {
        \Log::info('SecuritySettings save method called', [
            'settings' => $this->settings,
            'originalSettings' => $this->originalSettings,
        ]);
        
        $this->saving = true;
        $this->validationErrors = [];

        try {
            // 驗證變更的設定
            $this->validateChangedSettings();

            // 檢查影響範圍
            $this->checkSecurityImpact();

            // 更新設定
            $updateData = [];
            foreach ($this->securitySettingKeys as $key) {
                $keyParts = explode('.', $key);
                if (count($keyParts) === 2 && $keyParts[0] === 'security') {
                    $settingKey = $keyParts[1];
                    $currentValue = $this->settings['security'][$settingKey] ?? '';
                    $originalValue = $this->originalSettings['security'][$settingKey] ?? '';
                    
                    if ($currentValue !== $originalValue) {
                        $updateData[$key] = $currentValue;
                    }
                }
            }

            // 除錯日誌
            \Log::info('SecuritySettings save debug', [
                'updateData' => $updateData,
                'currentSettings' => $this->settings,
                'originalSettings' => $this->originalSettings,
                'hasChanges' => $this->hasChanges,
            ]);

            if (!empty($updateData)) {
                $result = $this->getSettingsRepository()->updateSettings($updateData);
                
                \Log::info('SecuritySettings updateSettings result', [
                    'result' => $result,
                    'updateData' => $updateData,
                ]);
                
                if ($result) {
                    // 即時應用設定
                    $this->applySecuritySettingsImmediately($updateData);
                    
                    // 更新原始值
                    $this->originalSettings = $this->settings;
                    
                    // 清除相關快取
                    $this->clearSecuritySettingsCache();
                    
                    // 記錄安全設定變更日誌
                    $this->logSecurityChanges($updateData);
                    
                    // 觸發設定更新事件
                    $this->dispatch('security-settings-updated', $updateData);
                    
                    $this->successMessage = '安全設定已成功更新';
                    $this->errorMessage = null;
                    
                    // 隱藏影響範圍警告
                    $this->showImpactWarning = false;
                } else {
                    $this->errorMessage = '設定更新失敗';
                    $this->successMessage = null;
                }
            } else {
                $this->successMessage = '沒有設定需要更新';
                $this->errorMessage = null;
            }

        } catch (ValidationException $e) {
            $this->validationErrors = $e->validator->errors()->toArray();
            $this->errorMessage = '設定驗證失敗，請檢查輸入值';
            $this->successMessage = null;
        } catch (\Exception $e) {
            \Log::error('SecuritySettings save error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = "設定更新時發生錯誤：{$e->getMessage()}";
            $this->successMessage = null;
        } finally {
            $this->saving = false;
        }
    }

    /**
     * 重設所有設定為預設值
     */
    public function resetAll(): void
    {
        try {
            foreach ($this->securitySettingKeys as $key) {
                $this->getSettingsRepository()->resetSetting($key);
            }
            
            // 重新載入設定
            $this->loadSettings();
            
            // 準備完整設定資料用於應用和記錄
            $fullSettings = [];
            foreach ($this->securitySettingKeys as $key) {
                $keyParts = explode('.', $key);
                if (count($keyParts) === 2 && $keyParts[0] === 'security') {
                    $settingKey = $keyParts[1];
                    $fullSettings[$key] = $this->settings['security'][$settingKey] ?? '';
                }
            }
            
            // 即時應用設定
            $this->applySecuritySettingsImmediately($fullSettings);
            
            // 清除快取
            $this->clearSecuritySettingsCache();
            
            // 記錄重設操作
            $this->logSecurityChanges($fullSettings, 'reset_all');
            
            $this->dispatch('security-settings-updated', $fullSettings);
            $this->addFlash('success', '所有安全設定已重設為預設值');
            
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 重設單一設定
     */
    public function resetSetting(string $key): void
    {
        if (!in_array($key, $this->securitySettingKeys)) {
            return;
        }

        try {
            $result = $this->getSettingsRepository()->resetSetting($key);
            
            if ($result) {
                // 重新載入該設定
                $setting = $this->getSettingsRepository()->getSetting($key);
                $value = $setting ? $setting->value : $this->getDefaultValue($key);
                
                // 更新巢狀結構
                $keyParts = explode('.', $key);
                if (count($keyParts) === 2 && $keyParts[0] === 'security') {
                    $settingKey = $keyParts[1];
                    $this->settings['security'][$settingKey] = $value;
                    $this->originalSettings['security'][$settingKey] = $value;
                }
                
                // 即時應用設定
                $this->applySecuritySettingsImmediately([$key => $value]);
                
                // 記錄重設操作
                $this->logSecurityChanges([$key => $value], 'reset_single');
                
                $this->dispatch('security-settings-updated', [$key => $value]);
                $this->addFlash('success', "設定 '{$key}' 已重設為預設值");
            } else {
                $this->addFlash('error', "無法重設設定 '{$key}'");
            }
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 檢查安全設定影響範圍
     */
    protected function checkSecurityImpact(): void
    {
        $this->impactInfo = [];
        $changedSettings = $this->changedSettings;
        
        foreach ($changedSettings as $key => $change) {
            switch ($key) {
                case 'security.password_min_length':
                case 'security.password_require_uppercase':
                case 'security.password_require_lowercase':
                case 'security.password_require_numbers':
                case 'security.password_require_symbols':
                    $this->impactInfo[] = [
                        'type' => 'warning',
                        'title' => '密碼政策變更',
                        'message' => '新的密碼政策將在使用者下次修改密碼時生效，現有密碼不受影響。'
                    ];
                    break;
                    
                case 'security.password_expiry_days':
                    if ($change['new'] > 0 && $change['old'] == 0) {
                        $this->impactInfo[] = [
                            'type' => 'warning',
                            'title' => '密碼過期政策啟用',
                            'message' => "所有使用者的密碼將在 {$change['new']} 天後過期，屆時需要重新設定密碼。"
                        ];
                    } elseif ($change['new'] == 0 && $change['old'] > 0) {
                        $this->impactInfo[] = [
                            'type' => 'info',
                            'title' => '密碼過期政策停用',
                            'message' => '使用者密碼將不再有過期限制。'
                        ];
                    }
                    break;
                    
                case 'security.login_max_attempts':
                case 'security.lockout_duration':
                    $this->impactInfo[] = [
                        'type' => 'warning',
                        'title' => '登入安全政策變更',
                        'message' => '新的登入失敗鎖定政策將立即生效，影響所有使用者的登入行為。'
                    ];
                    break;
                    
                case 'security.session_lifetime':
                    $this->impactInfo[] = [
                        'type' => 'warning',
                        'title' => 'Session 過期時間變更',
                        'message' => '新的 Session 過期時間將在使用者下次登入時生效，現有 Session 不受影響。'
                    ];
                    break;
                    
                case 'security.force_https':
                    if ($change['new']) {
                        $this->impactInfo[] = [
                            'type' => 'danger',
                            'title' => '強制 HTTPS 啟用',
                            'message' => '啟用後所有 HTTP 連線將被重新導向至 HTTPS，請確保已正確配置 SSL 憑證。'
                        ];
                    }
                    break;
                    
                case 'security.two_factor_enabled':
                    if ($change['new']) {
                        $this->impactInfo[] = [
                            'type' => 'info',
                            'title' => '雙因子認證啟用',
                            'message' => '使用者可以在個人設定中啟用雙因子認證功能。'
                        ];
                    } else {
                        $this->impactInfo[] = [
                            'type' => 'warning',
                            'title' => '雙因子認證停用',
                            'message' => '所有使用者的雙因子認證將被停用，降低帳號安全性。'
                        ];
                    }
                    break;
            }
        }
        
        if (!empty($this->impactInfo)) {
            $this->showImpactWarning = true;
        }
    }

    /**
     * 驗證變更的設定
     */
    protected function validateChangedSettings(): void
    {
        \Log::info('SecuritySettings validateChangedSettings called');
        
        $rules = [];
        $messages = [];
        $dataToValidate = [];
        
        // 只驗證有變更的設定
        foreach ($this->securitySettingKeys as $key) {
            $keyParts = explode('.', $key);
            if (count($keyParts) === 2 && $keyParts[0] === 'security') {
                $settingKey = $keyParts[1];
                $currentValue = $this->settings['security'][$settingKey] ?? '';
                $originalValue = $this->originalSettings['security'][$settingKey] ?? '';
                
                if ($currentValue !== $originalValue) {
                    $config = $this->getConfigService()->getSettingConfig($key);
                    if (isset($config['validation'])) {
                        $rules[$key] = $config['validation'];
                        
                        // 根據類型轉換資料
                        $convertedValue = $currentValue;
                        if (isset($config['type'])) {
                            switch ($config['type']) {
                                case 'number':
                                case 'integer':
                                    $convertedValue = (int) $currentValue;
                                    break;
                                case 'boolean':
                                    $convertedValue = (bool) $currentValue;
                                    break;
                                case 'float':
                                    $convertedValue = (float) $currentValue;
                                    break;
                            }
                        }
                        
                        $dataToValidate[$key] = $convertedValue;
                    }
                }
            }
        }

        \Log::info('SecuritySettings validation data', [
            'rules' => $rules,
            'dataToValidate' => $dataToValidate,
        ]);
        
        if (!empty($rules)) {
            // 重新組織驗證資料，避免點號鍵值問題
            $flatRules = [];
            $flatData = [];
            
            foreach ($rules as $key => $rule) {
                $flatKey = str_replace('.', '_', $key);
                $flatRules[$flatKey] = $rule;
                $flatData[$flatKey] = $dataToValidate[$key];
            }
            
            \Log::info('SecuritySettings flat validation data', [
                'flatRules' => $flatRules,
                'flatData' => $flatData,
            ]);
            
            $validator = Validator::make($flatData, $flatRules, $messages);
            
            if ($validator->fails()) {
                \Log::error('SecuritySettings validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);
                throw new ValidationException($validator);
            }
        }
        
        \Log::info('SecuritySettings validateChangedSettings completed');
    }

    /**
     * 即時應用安全設定到系統
     */
    protected function applySecuritySettingsImmediately(array $settings): void
    {
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'security.session_lifetime':
                    Config::set('session.lifetime', (int) $value);
                    break;
                    
                case 'security.force_https':
                    if ($value) {
                        Config::set('app.force_https', true);
                    }
                    break;
                    
                case 'security.login_max_attempts':
                    Config::set('auth.throttle.max_attempts', (int) $value);
                    break;
                    
                case 'security.lockout_duration':
                    Config::set('auth.throttle.decay_minutes', (int) $value);
                    break;
            }
        }
    }

    /**
     * 清除安全設定相關快取
     */
    protected function clearSecuritySettingsCache(): void
    {
        $this->getSettingsRepository()->clearCache();
        
        // 清除安全相關快取
        Cache::forget('security.password_policy');
        Cache::forget('security.login_policy');
        Cache::forget('security.session_policy');
        
        // 清除中介軟體快取
        if (class_exists('\App\Http\Middleware\ApplySecuritySettings')) {
            \App\Http\Middleware\ApplySecuritySettings::clearCache();
        }
        
        // 重新載入配置
        $this->getConfigService()->reloadConfig();
    }

    /**
     * 記錄安全設定變更日誌
     */
    protected function logSecurityChanges(array $settings, string $action = 'update'): void
    {
        foreach ($settings as $key => $value) {
            $oldValue = null;
            $keyParts = explode('.', $key);
            if (count($keyParts) === 2 && $keyParts[0] === 'security') {
                $settingKey = $keyParts[1];
                $oldValue = $this->originalSettings['security'][$settingKey] ?? null;
            }
            
            \Log::info('Security setting changed', [
                'setting_key' => $key,
                'new_value' => $value,
                'old_value' => $oldValue,
                'action' => $action,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * 監聽設定值變更
     */
    public function updatedSettings($value, $key): void
    {
        // 將巢狀鍵轉換為完整設定鍵 (例如: security.password_min_length)
        $fullKey = 'security.' . $key;
        
        // 即時驗證
        $this->validateSingleSetting($fullKey, $value);
        
        // 檢查影響範圍
        if ($this->hasChanges) {
            $this->checkSecurityImpact();
        }
    }

    /**
     * 驗證單一設定
     */
    protected function validateSingleSetting(string $key, $value): void
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        
        if (isset($config['validation'])) {
            $validator = Validator::make(
                [$key => $value],
                [$key => $config['validation']]
            );
            
            if ($validator->fails()) {
                $this->validationErrors[$key] = $validator->errors()->first($key);
            } else {
                unset($this->validationErrors[$key]);
            }
        }
    }

    /**
     * 取得設定顯示名稱
     */
    public function getSettingDisplayName(string $key): string
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        return $config['description'] ?? $key;
    }

    /**
     * 取得設定說明
     */
    public function getSettingHelp(string $key): string
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        return $config['help'] ?? '';
    }

    /**
     * 檢查設定是否為必填
     */
    public function isRequired(string $key): bool
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        $validation = $config['validation'] ?? '';
        
        if (is_string($validation)) {
            return str_contains($validation, 'required');
        }
        
        if (is_array($validation)) {
            return in_array('required', $validation);
        }
        
        return false;
    }

    /**
     * 取得設定驗證錯誤
     */
    public function getValidationError(string $key): ?string
    {
        $error = $this->validationErrors[$key] ?? null;
        
        if (is_array($error)) {
            return $error[0] ?? null;
        }
        
        return $error;
    }

    /**
     * 監聽全域設定更新事件
     */
    #[On('setting-updated')]
    public function handleSettingUpdated(string $settingKey): void
    {
        if (in_array($settingKey, $this->securitySettingKeys)) {
            $this->loadSettings();
        }
    }

    /**
     * 載入安全統計資訊
     */
    public function loadSecurityStats(): void
    {
        \Log::info('SecuritySettings loadSecurityStats called');
        $this->securityStats = [
            'test' => 'Security stats loaded'
        ];
        \Log::info('SecuritySettings loadSecurityStats completed');
    }

    /**
     * 顯示 IP 管理對話框
     */
    public function showIpManagementModal(): void
    {
        $this->showIpManagement = true;
    }

    /**
     * 隱藏 IP 管理對話框
     */
    public function hideIpManagementModal(): void
    {
        $this->showIpManagement = false;
    }

    /**
     * 清除 IP 限制快取
     */
    public function clearIpCache(): void
    {
        try {
            SettingsAccessControl::clearIpCache();
            $this->addFlash('success', 'IP 限制快取已清除');
        } catch (\Exception $e) {
            $this->addFlash('error', '清除 IP 快取失敗: ' . $e->getMessage());
        }
    }

    /**
     * 解鎖指定 IP
     */
    public function unlockIp(string $ip): void
    {
        try {
            SettingsAccessControl::unlockIp($ip);
            $this->addFlash('success', "IP {$ip} 已解鎖");
        } catch (\Exception $e) {
            $this->addFlash('error', '解鎖 IP 失敗: ' . $e->getMessage());
        }
    }

    /**
     * 顯示審計日誌清理對話框
     */
    public function showAuditCleanupModal(): void
    {
        $this->showAuditCleanup = true;
    }

    /**
     * 隱藏審計日誌清理對話框
     */
    public function hideAuditCleanupModal(): void
    {
        $this->showAuditCleanup = false;
    }

    /**
     * 清理審計日誌
     */
    public function cleanupAuditLogs(): void
    {
        try {
            $retentionDays = (int) ($this->settings['security.audit_log_retention_days'] ?? 90);
            $deletedCount = $this->getSecurityService()->cleanupAuditLogs($retentionDays);
            
            $this->addFlash('success', "已清理 {$deletedCount} 筆過期的審計日誌");
            $this->loadSecurityStats();
            $this->hideAuditCleanupModal();
            
        } catch (\Exception $e) {
            $this->addFlash('error', '清理審計日誌失敗: ' . $e->getMessage());
        }
    }

    /**
     * 建立加密備份
     */
    public function createEncryptedBackup(): void
    {
        try {
            $backupName = '安全設定加密備份 - ' . now()->format('Y-m-d H:i:s');
            $result = $this->getSecurityService()->createEncryptedBackup($backupName, '系統自動建立的加密備份', ['security']);
            
            if ($result['success']) {
                $this->addFlash('success', '加密備份建立成功');
                $this->loadSecurityStats();
            } else {
                $this->addFlash('error', '建立加密備份失敗: ' . ($result['error'] ?? '未知錯誤'));
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', '建立加密備份失敗: ' . $e->getMessage());
        }
    }

    /**
     * 測試 IP 限制設定
     */
    public function testIpRestriction(): void
    {
        try {
            $allowedIps = $this->settings['security.allowed_ips'] ?? '';
            $currentIp = request()->ip();
            
            if (empty($allowedIps)) {
                $this->addFlash('info', "目前沒有設定 IP 限制，所有 IP 都可以存取。您的 IP: {$currentIp}");
                return;
            }
            
            // 簡單的 IP 檢查邏輯
            $ips = array_map('trim', explode("\n", $allowedIps));
            $isAllowed = in_array($currentIp, $ips);
            
            if ($isAllowed) {
                $this->addFlash('success', "您的 IP ({$currentIp}) 在允許清單中");
            } else {
                $this->addFlash('warning', "警告：您的 IP ({$currentIp}) 不在允許清單中，儲存後您可能無法存取設定管理");
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'IP 限制測試失敗: ' . $e->getMessage());
        }
    }

    /**
     * 取得當前使用者 IP
     */
    #[Computed]
    public function currentUserIp(): string
    {
        return request()->ip();
    }

    /**
     * 取得被鎖定的 IP 清單
     */
    #[Computed]
    public function lockedIps(): array
    {
        try {
            return SettingsAccessControl::getLockedIps();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 檢查是否啟用審計日誌
     */
    #[Computed]
    public function isAuditLoggingEnabled(): bool
    {
        return (bool) ($this->settings['security.enable_audit_logging'] ?? true);
    }

    /**
     * 取得審計日誌統計
     */
    #[Computed]
    public function auditLogStats(): array
    {
        try {
            $totalLogs = \App\Models\SettingChange::count();
            $recentLogs = \App\Models\SettingChange::where('created_at', '>=', now()->subDays(7))->count();
            $retentionDays = (int) ($this->settings['security.audit_log_retention_days'] ?? 90);
            $expiredLogs = \App\Models\SettingChange::where('created_at', '<', now()->subDays($retentionDays))->count();
            
            return [
                'total' => $totalLogs,
                'recent' => $recentLogs,
                'expired' => $expiredLogs,
                'retention_days' => $retentionDays,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }



    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.security-settings');
    }
}