<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\NotificationTemplate;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * 通知設定管理元件
 * 
 * 提供郵件通知、SMTP 伺服器配置、通知範本管理、通知測試功能和通知頻率限制設定
 */
class NotificationSettings extends AdminComponent
{
    /**
     * 通知設定值
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
     * 正在測試連線
     */
    public bool $testing = false;

    /**
     * 測試結果
     */
    public array $testResult = [];

    /**
     * 顯示測試結果
     */
    public bool $showTestResult = false;

    /**
     * 正在發送測試郵件
     */
    public bool $sendingTestEmail = false;

    /**
     * 測試郵件地址
     */
    public string $testEmailAddress = '';

    /**
     * 顯示通知範本管理
     */
    public bool $showTemplateManager = false;

    /**
     * 通知範本列表
     */
    public array $notificationTemplates = [];

    /**
     * 選中的範本
     */
    public string $selectedTemplate = '';

    /**
     * 範本內容
     */
    public array $templateContent = [];

    /**
     * 驗證錯誤
     */
    public array $validationErrors = [];

    /**
     * 通知設定鍵值列表
     */
    protected array $notificationSettingKeys = [
        'notification.email_enabled',
        'notification.smtp_host',
        'notification.smtp_port',
        'notification.smtp_encryption',
        'notification.smtp_username',
        'notification.smtp_password',
        'notification.from_name',
        'notification.from_email',
        'notification.rate_limit_per_minute',
    ];

    /**
     * 預設通知範本
     */
    protected array $defaultTemplates = [
        'welcome' => [
            'name' => '歡迎郵件',
            'subject' => '歡迎加入 {app_name}',
            'content' => '親愛的 {user_name}，\n\n歡迎加入 {app_name}！\n\n您的帳號已成功建立，現在可以開始使用我們的服務。\n\n如有任何問題，請隨時聯繫我們。\n\n祝好，\n{app_name} 團隊',
        ],
        'password_reset' => [
            'name' => '密碼重設',
            'subject' => '{app_name} - 密碼重設請求',
            'content' => '親愛的 {user_name}，\n\n我們收到您的密碼重設請求。\n\n請點擊以下連結重設您的密碼：\n{reset_link}\n\n此連結將在 {expires_in} 分鐘後失效。\n\n如果您沒有請求重設密碼，請忽略此郵件。\n\n祝好，\n{app_name} 團隊',
        ],
        'account_locked' => [
            'name' => '帳號鎖定通知',
            'subject' => '{app_name} - 帳號安全警告',
            'content' => '親愛的 {user_name}，\n\n您的帳號因多次登入失敗而被暫時鎖定。\n\n鎖定時間：{lockout_duration} 分鐘\n鎖定原因：連續 {max_attempts} 次登入失敗\n\n如果這不是您的操作，請立即聯繫我們。\n\n祝好，\n{app_name} 團隊',
        ],
        'system_maintenance' => [
            'name' => '系統維護通知',
            'subject' => '{app_name} - 系統維護通知',
            'content' => '親愛的用戶，\n\n我們將於 {maintenance_start} 進行系統維護。\n\n維護時間：{maintenance_duration}\n維護內容：{maintenance_description}\n\n維護期間系統將暫時無法使用，造成不便敬請見諒。\n\n祝好，\n{app_name} 團隊',
        ],
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
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
        $this->loadSettings();
        $this->loadNotificationTemplates();
        $this->testEmailAddress = auth()->user()->email ?? '';
    }

    /**
     * 載入設定資料
     */
    public function loadSettings(): void
    {
        $settingsData = $this->getSettingsRepository()->getSettings($this->notificationSettingKeys);
        
        foreach ($this->notificationSettingKeys as $key) {
            $setting = $settingsData->get($key);
            $this->settings[$key] = $setting ? $setting->value : $this->getDefaultValue($key);
        }
        
        $this->originalSettings = $this->settings;
    }

    /**
     * 載入通知範本
     */
    public function loadNotificationTemplates(): void
    {
        // 從資料庫載入範本
        $templates = NotificationTemplate::active()->get();
        
        $this->notificationTemplates = [];
        foreach ($templates as $template) {
            $this->notificationTemplates[$template->key] = [
                'name' => $template->name,
                'subject' => $template->subject,
                'content' => $template->content,
                'variables' => $template->variables ?? [],
                'category' => $template->category,
                'is_system' => $template->is_system,
            ];
        }
        
        // 如果資料庫中沒有範本，使用預設範本
        if (empty($this->notificationTemplates)) {
            $this->notificationTemplates = $this->defaultTemplates;
        }
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
     * 取得 SMTP 加密選項
     */
    #[Computed]
    public function encryptionOptions(): array
    {
        return [
            'none' => '無加密',
            'ssl' => 'SSL',
            'tls' => 'TLS',
        ];
    }

    /**
     * 取得通知頻率限制選項
     */
    #[Computed]
    public function rateLimitOptions(): array
    {
        return [
            1 => '每分鐘 1 封',
            5 => '每分鐘 5 封',
            10 => '每分鐘 10 封',
            20 => '每分鐘 20 封',
            50 => '每分鐘 50 封',
            100 => '每分鐘 100 封',
        ];
    }

    /**
     * 檢查是否有變更
     */
    #[Computed]
    public function hasChanges(): bool
    {
        foreach ($this->notificationSettingKeys as $key) {
            if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                return true;
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
        foreach ($this->notificationSettingKeys as $key) {
            if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                $changed[$key] = [
                    'old' => $this->originalSettings[$key] ?? '',
                    'new' => $this->settings[$key] ?? '',
                ];
            }
        }
        return $changed;
    }

    /**
     * 檢查郵件通知是否啟用
     */
    #[Computed]
    public function isEmailEnabled(): bool
    {
        return (bool) ($this->settings['notification.email_enabled'] ?? false);
    }

    /**
     * 取得 SMTP 配置狀態
     */
    #[Computed]
    public function smtpConfigStatus(): array
    {
        if (!$this->isEmailEnabled) {
            return [
                'status' => 'disabled',
                'message' => '郵件通知已停用',
                'color' => 'text-gray-500',
            ];
        }

        $requiredFields = [
            'notification.smtp_host',
            'notification.smtp_port',
            'notification.from_email',
        ];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($this->settings[$field])) {
                $missingFields[] = $this->getSettingDisplayName($field);
            }
        }

        if (!empty($missingFields)) {
            return [
                'status' => 'incomplete',
                'message' => '缺少必要設定：' . implode('、', $missingFields),
                'color' => 'text-yellow-600',
            ];
        }

        return [
            'status' => 'configured',
            'message' => 'SMTP 設定完整',
            'color' => 'text-green-600',
        ];
    }

    /**
     * 儲存設定
     */
    public function save(): void
    {
        $this->saving = true;
        $this->validationErrors = [];

        try {
            // 驗證變更的設定
            $this->validateChangedSettings();

            // 更新設定
            $updateData = [];
            foreach ($this->notificationSettingKeys as $key) {
                if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                    $updateData[$key] = $this->settings[$key];
                }
            }

            if (!empty($updateData)) {
                $result = $this->getSettingsRepository()->updateSettings($updateData);
                
                if ($result) {
                    // 即時應用設定
                    $this->applyNotificationSettingsImmediately($updateData);
                    
                    // 更新原始值
                    $this->originalSettings = $this->settings;
                    
                    // 清除相關快取
                    $this->clearNotificationSettingsCache();
                    
                    // 觸發設定更新事件
                    $this->dispatch('notification-settings-updated', $updateData);
                    
                    $this->addFlash('success', '通知設定已成功更新');
                } else {
                    $this->addFlash('error', '設定更新失敗');
                }
            } else {
                $this->addFlash('info', '沒有設定需要更新');
            }

        } catch (ValidationException $e) {
            $this->validationErrors = $e->validator->errors()->toArray();
            $this->addFlash('error', '設定驗證失敗，請檢查輸入值');
        } catch (\Exception $e) {
            $this->addFlash('error', "設定更新時發生錯誤：{$e->getMessage()}");
        } finally {
            $this->saving = false;
        }
    }

    /**
     * 測試 SMTP 連線
     */
    public function testSmtpConnection(): void
    {
        $this->testing = true;
        $this->testResult = [];
        $this->showTestResult = false;

        try {
            if (!$this->isEmailEnabled) {
                throw new \Exception('郵件通知未啟用');
            }

            $config = [
                'host' => $this->settings['notification.smtp_host'] ?? '',
                'port' => $this->settings['notification.smtp_port'] ?? 587,
                'encryption' => $this->settings['notification.smtp_encryption'] ?? 'tls',
                'username' => $this->settings['notification.smtp_username'] ?? '',
                'password' => $this->settings['notification.smtp_password'] ?? '',
            ];

            // 檢查必要欄位
            if (empty($config['host'])) {
                throw new \Exception('SMTP 主機不能為空');
            }

            // 使用 ConfigurationService 測試連線
            $result = $this->getConfigService()->testConnection('smtp', $config);

            if ($result) {
                $this->testResult = [
                    'success' => true,
                    'message' => 'SMTP 連線測試成功',
                    'details' => [
                        '主機' => $config['host'],
                        '埠號' => $config['port'],
                        '加密' => $config['encryption'],
                        '認證' => !empty($config['username']) ? '已設定' : '未設定',
                    ],
                ];
            } else {
                $this->testResult = [
                    'success' => false,
                    'message' => 'SMTP 連線測試失敗',
                    'details' => [
                        '主機' => $config['host'],
                        '埠號' => $config['port'],
                        '錯誤' => '無法連接到 SMTP 伺服器',
                    ],
                ];
            }

        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'message' => 'SMTP 連線測試失敗',
                'details' => [
                    '錯誤' => $e->getMessage(),
                ],
            ];
        } finally {
            $this->testing = false;
            $this->showTestResult = true;
        }
    }

    /**
     * 發送測試郵件
     */
    public function sendTestEmail(): void
    {
        $this->sendingTestEmail = true;

        try {
            if (!$this->isEmailEnabled) {
                throw new \Exception('郵件通知未啟用');
            }

            if (empty($this->testEmailAddress)) {
                throw new \Exception('請輸入測試郵件地址');
            }

            if (!filter_var($this->testEmailAddress, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('測試郵件地址格式不正確');
            }

            // 暫時應用當前設定
            $this->applyNotificationSettingsTemporarily();

            // 使用通知服務發送測試郵件
            $notificationService = app(NotificationService::class);
            $result = $notificationService->testConfiguration($this->testEmailAddress);

            if ($result['success']) {
                $this->addFlash('success', "測試郵件已發送至 {$this->testEmailAddress}，請檢查您的信箱");
            } else {
                $this->addFlash('error', "測試郵件發送失敗：{$result['message']}");
            }

        } catch (\Exception $e) {
            $this->addFlash('error', "測試郵件發送失敗：{$e->getMessage()}");
        } finally {
            $this->sendingTestEmail = false;
        }
    }

    /**
     * 開啟通知範本管理
     */
    public function openTemplateManager(): void
    {
        $this->showTemplateManager = true;
        $this->selectedTemplate = '';
        $this->templateContent = [];
    }

    /**
     * 關閉通知範本管理
     */
    public function closeTemplateManager(): void
    {
        $this->showTemplateManager = false;
        $this->selectedTemplate = '';
        $this->templateContent = [];
    }

    /**
     * 選擇通知範本
     */
    public function selectTemplate(string $templateKey): void
    {
        $this->selectedTemplate = $templateKey;
        $this->templateContent = $this->notificationTemplates[$templateKey] ?? [];
    }

    /**
     * 儲存通知範本
     */
    public function saveTemplate(): void
    {
        try {
            if (empty($this->selectedTemplate)) {
                throw new \Exception('請選擇要編輯的範本');
            }

            // 驗證範本內容
            $validator = Validator::make($this->templateContent, [
                'name' => 'required|string|max:100',
                'subject' => 'required|string|max:200',
                'content' => 'required|string|max:10000',
            ], [
                'name.required' => '範本名稱不能為空',
                'subject.required' => '郵件主旨不能為空',
                'content.required' => '郵件內容不能為空',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // 查找或建立範本
            $template = NotificationTemplate::where('key', $this->selectedTemplate)->first();
            
            if ($template) {
                // 如果是系統範本且有變更，建立副本
                if ($template->is_system && $this->hasTemplateChanges($template)) {
                    $template = $template->duplicate($this->selectedTemplate . '_custom');
                }
                
                // 更新範本
                $template->update([
                    'name' => $this->templateContent['name'],
                    'subject' => $this->templateContent['subject'],
                    'content' => $this->templateContent['content'],
                ]);
            } else {
                // 建立新範本
                $template = NotificationTemplate::create([
                    'key' => $this->selectedTemplate,
                    'name' => $this->templateContent['name'],
                    'subject' => $this->templateContent['subject'],
                    'content' => $this->templateContent['content'],
                    'category' => $this->templateContent['category'] ?? NotificationTemplate::CATEGORY_SYSTEM,
                    'is_system' => false,
                    'is_active' => true,
                ]);
            }

            // 重新載入範本
            $this->loadNotificationTemplates();

            $this->addFlash('success', '通知範本已成功更新');

        } catch (ValidationException $e) {
            $this->addFlash('error', '範本驗證失敗：' . $e->validator->errors()->first());
        } catch (\Exception $e) {
            $this->addFlash('error', "範本儲存失敗：{$e->getMessage()}");
        }
    }

    /**
     * 重設範本為預設值
     */
    public function resetTemplate(): void
    {
        if (empty($this->selectedTemplate)) {
            return;
        }

        // 查找系統預設範本
        $template = NotificationTemplate::where('key', $this->selectedTemplate)
                                       ->where('is_system', true)
                                       ->first();
        
        if ($template) {
            $this->templateContent = [
                'name' => $template->name,
                'subject' => $template->subject,
                'content' => $template->content,
                'category' => $template->category,
            ];
            $this->addFlash('info', '範本已重設為系統預設值');
        } elseif (isset($this->defaultTemplates[$this->selectedTemplate])) {
            $this->templateContent = $this->defaultTemplates[$this->selectedTemplate];
            $this->addFlash('info', '範本已重設為預設值');
        }
    }

    /**
     * 預覽範本
     */
    public function previewTemplate(): array
    {
        if (empty($this->templateContent)) {
            return [];
        }

        // 替換範本變數為示例值
        $variables = [
            '{app_name}' => $this->settings['app.name'] ?? 'Laravel Admin System',
            '{user_name}' => '張三',
            '{reset_link}' => 'https://example.com/reset-password?token=abc123',
            '{expires_in}' => '60',
            '{lockout_duration}' => '15',
            '{max_attempts}' => '5',
            '{maintenance_start}' => '2024-01-15 02:00',
            '{maintenance_duration}' => '2 小時',
            '{maintenance_description}' => '系統升級和安全性更新',
        ];

        return [
            'subject' => str_replace(array_keys($variables), array_values($variables), $this->templateContent['subject'] ?? ''),
            'content' => str_replace(array_keys($variables), array_values($variables), $this->templateContent['content'] ?? ''),
        ];
    }

    /**
     * 重設所有設定為預設值
     */
    public function resetAll(): void
    {
        try {
            foreach ($this->notificationSettingKeys as $key) {
                $this->getSettingsRepository()->resetSetting($key);
            }
            
            // 重新載入設定
            $this->loadSettings();
            
            // 即時應用設定
            $this->applyNotificationSettingsImmediately($this->settings);
            
            // 清除快取
            $this->clearNotificationSettingsCache();
            
            $this->dispatch('notification-settings-updated', $this->settings);
            $this->addFlash('success', '所有通知設定已重設為預設值');
            
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 重設單一設定
     */
    public function resetSetting(string $key): void
    {
        if (!in_array($key, $this->notificationSettingKeys)) {
            return;
        }

        try {
            $result = $this->getSettingsRepository()->resetSetting($key);
            
            if ($result) {
                // 重新載入該設定
                $setting = $this->getSettingsRepository()->getSetting($key);
                $this->settings[$key] = $setting ? $setting->value : $this->getDefaultValue($key);
                $this->originalSettings[$key] = $this->settings[$key];
                
                // 即時應用設定
                $this->applyNotificationSettingsImmediately([$key => $this->settings[$key]]);
                
                $this->dispatch('notification-settings-updated', [$key => $this->settings[$key]]);
                $this->addFlash('success', "設定 '{$key}' 已重設為預設值");
            } else {
                $this->addFlash('error', "無法重設設定 '{$key}'");
            }
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 驗證變更的設定
     */
    protected function validateChangedSettings(): void
    {
        $rules = [];
        $messages = [];
        $dataToValidate = [];
        
        // 只驗證有變更的設定
        foreach ($this->notificationSettingKeys as $key) {
            if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                $config = $this->getConfigService()->getSettingConfig($key);
                if (isset($config['validation'])) {
                    $rules[$key] = $config['validation'];
                    $dataToValidate[$key] = $this->settings[$key] ?? '';
                }
            }
        }

        if (!empty($rules)) {
            $validator = Validator::make($dataToValidate, $rules, $messages);
            
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }
    }

    /**
     * 即時應用通知設定到系統
     */
    protected function applyNotificationSettingsImmediately(array $settings): void
    {
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'notification.smtp_host':
                    Config::set('mail.mailers.smtp.host', $value);
                    break;
                    
                case 'notification.smtp_port':
                    Config::set('mail.mailers.smtp.port', (int) $value);
                    break;
                    
                case 'notification.smtp_encryption':
                    Config::set('mail.mailers.smtp.encryption', $value === 'none' ? null : $value);
                    break;
                    
                case 'notification.smtp_username':
                    Config::set('mail.mailers.smtp.username', $value);
                    break;
                    
                case 'notification.smtp_password':
                    Config::set('mail.mailers.smtp.password', $value);
                    break;
                    
                case 'notification.from_name':
                    Config::set('mail.from.name', $value);
                    break;
                    
                case 'notification.from_email':
                    Config::set('mail.from.address', $value);
                    break;
            }
        }
    }

    /**
     * 暫時應用通知設定（用於測試）
     */
    protected function applyNotificationSettingsTemporarily(): void
    {
        $this->applyNotificationSettingsImmediately($this->settings);
    }

    /**
     * 清除通知設定相關快取
     */
    protected function clearNotificationSettingsCache(): void
    {
        $this->getSettingsRepository()->clearCache();
        
        // 清除通知相關快取
        Cache::forget('notification_settings');
        Cache::forget('smtp_config');
        
        // 重新載入配置
        $this->getConfigService()->reloadConfig();
    }

    /**
     * 監聽設定值變更
     */
    public function updatedSettings($value, $key): void
    {
        // 即時驗證
        $this->validateSingleSetting($key, $value);
        
        // 如果郵件通知被停用，清除測試結果
        if ($key === 'notification.email_enabled' && !$value) {
            $this->testResult = [];
            $this->showTestResult = false;
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
     * 檢查設定是否依賴其他設定
     */
    public function isDependentSetting(string $key): bool
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        return isset($config['depends_on']);
    }

    /**
     * 檢查依賴設定是否滿足
     */
    public function isDependencySatisfied(string $key): bool
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        
        if (!isset($config['depends_on'])) {
            return true;
        }
        
        foreach ($config['depends_on'] as $dependentKey => $expectedValue) {
            if (($this->settings[$dependentKey] ?? null) !== $expectedValue) {
                return false;
            }
        }
        
        return true;
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
        if (in_array($settingKey, $this->notificationSettingKeys)) {
            $this->loadSettings();
        }
    }

    /**
     * 檢查範本是否有變更
     */
    protected function hasTemplateChanges(NotificationTemplate $template): bool
    {
        return $template->name !== ($this->templateContent['name'] ?? '') ||
               $template->subject !== ($this->templateContent['subject'] ?? '') ||
               $template->content !== ($this->templateContent['content'] ?? '');
    }

    /**
     * 取得範本分類選項
     */
    #[Computed]
    public function templateCategories(): array
    {
        return NotificationTemplate::getCategories();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.notification-settings');
    }
}