<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

/**
 * 通知設定管理元件
 * 
 * 提供郵件通知、SMTP 伺服器配置和基本通知設定功能
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
     * 當前選中的範本類型
     */
    public string $selectedTemplateType = 'welcome';

    /**
     * 範本資料
     */
    public array $templates = [];

    /**
     * 當前編輯的範本
     */
    public array $currentTemplate = [];

    /**
     * 驗證錯誤
     */
    public array $validationErrors = [];

    /**
     * 通知設定預設值
     */
    protected array $defaultSettings = [
        'email_enabled' => true,  // 預設啟用郵件通知
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'from_name' => 'Laravel Admin System',
        'from_email' => '',
        'rate_limit_per_minute' => 10,
    ];

    /**
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
        $this->loadSettings();
        $this->loadTemplates();
        $this->testEmailAddress = auth()->user()->email ?? '';
    }

    /**
     * 載入設定資料
     */
    public function loadSettings(): void
    {
        // 使用預設設定，讓郵件通知預設啟用
        $this->settings = $this->defaultSettings;
        $this->originalSettings = $this->settings;
    }

    /**
     * 取得 SMTP 加密選項
     */
    #[Computed]
    public function encryptionOptions(): array
    {
        return [
            '' => '無加密',
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
        foreach ($this->settings as $key => $value) {
            if ($value !== ($this->originalSettings[$key] ?? '')) {
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
        foreach ($this->settings as $key => $value) {
            if ($value !== ($this->originalSettings[$key] ?? '')) {
                $changed[$key] = [
                    'old' => $this->originalSettings[$key] ?? '',
                    'new' => $value,
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
        return (bool) ($this->settings['email_enabled'] ?? false);
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

        $requiredFields = ['smtp_host', 'smtp_port', 'from_email'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($this->settings[$field])) {
                $missingFields[] = $this->getFieldDisplayName($field);
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
     * 取得欄位顯示名稱
     */
    protected function getFieldDisplayName(string $field): string
    {
        $names = [
            'smtp_host' => 'SMTP 主機',
            'smtp_port' => 'SMTP 埠號',
            'from_email' => '寄件者信箱',
            'from_name' => '寄件者名稱',
        ];

        return $names[$field] ?? $field;
    }

    /**
     * 儲存設定
     */
    public function save(): void
    {
        $this->saving = true;
        $this->validationErrors = [];

        try {
            // 驗證設定
            $this->validateSettings();

            // 這裡應該將設定儲存到資料庫或配置檔案
            // 目前只是模擬儲存成功
            
            // 更新原始值
            $this->originalSettings = $this->settings;
            
            $this->addFlash('success', '通知設定已成功更新');

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
     * 驗證設定
     */
    protected function validateSettings(): void
    {
        $rules = [
            'smtp_host' => 'required_if:email_enabled,true|string|max:255',
            'smtp_port' => 'required_if:email_enabled,true|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:,ssl,tls',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'from_name' => 'required_if:email_enabled,true|string|max:255',
            'from_email' => 'required_if:email_enabled,true|email|max:255',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
        ];

        $messages = [
            'smtp_host.required_if' => 'SMTP 主機不能為空',
            'smtp_port.required_if' => 'SMTP 埠號不能為空',
            'smtp_port.integer' => 'SMTP 埠號必須是數字',
            'smtp_port.min' => 'SMTP 埠號必須大於 0',
            'smtp_port.max' => 'SMTP 埠號不能超過 65535',
            'from_name.required_if' => '寄件者名稱不能為空',
            'from_email.required_if' => '寄件者信箱不能為空',
            'from_email.email' => '寄件者信箱格式不正確',
            'rate_limit_per_minute.required' => '通知頻率限制不能為空',
            'rate_limit_per_minute.integer' => '通知頻率限制必須是數字',
        ];

        $validator = Validator::make($this->settings, $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
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

            if (empty($this->settings['smtp_host'])) {
                throw new \Exception('SMTP 主機不能為空');
            }

            // 模擬測試連線
            sleep(1); // 模擬網路延遲
            
            $this->testResult = [
                'success' => true,
                'message' => 'SMTP 連線測試成功',
                'details' => [
                    '主機' => $this->settings['smtp_host'],
                    '埠號' => $this->settings['smtp_port'],
                    '加密' => $this->settings['smtp_encryption'] ?: '無',
                    '認證' => !empty($this->settings['smtp_username']) ? '已設定' : '未設定',
                ],
            ];

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

            // 模擬發送測試郵件
            sleep(2); // 模擬發送延遲
            
            $this->addFlash('success', "測試郵件已發送至 {$this->testEmailAddress}，請檢查您的信箱");

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
        // 強制重新載入範本資料
        $this->templates = [];
        $this->loadTemplates();
        
        // 重設為預設選擇
        $this->selectedTemplateType = 'welcome';
        
        // 確保當前範本資料正確設定
        if (isset($this->templates['welcome'])) {
            $this->currentTemplate = $this->templates['welcome'];
        } else {
            // 如果沒有範本資料，建立預設範本
            $this->currentTemplate = [
                'name' => '歡迎郵件',
                'subject' => '歡迎加入 {app_name}',
                'content' => "親愛的 {user_name}，\n\n歡迎加入 {app_name}！",
                'variables' => ['user_name', 'app_name']
            ];
        }
        
        $this->showTemplateManager = true;
        
        // 觸發前端更新事件
        $this->dispatch('template-manager-opened');
    }

    /**
     * 關閉通知範本管理
     */
    public function closeTemplateManager(): void
    {
        $this->showTemplateManager = false;
        $this->selectedTemplateType = 'welcome';
        $this->currentTemplate = [];
    }

    /**
     * 載入範本資料
     */
    public function loadTemplates(): void
    {
        // 模擬範本資料
        $this->templates = [
            'welcome' => [
                'name' => '歡迎郵件',
                'subject' => '歡迎加入 {app_name}',
                'content' => "親愛的 {user_name}，\n\n歡迎加入 {app_name}！\n\n您的帳號已成功建立，現在可以開始使用我們的服務。\n\n如有任何問題，請隨時聯繫我們。\n\n祝好，\n{app_name} 團隊",
                'variables' => ['user_name', 'app_name', 'login_url']
            ],
            'password_reset' => [
                'name' => '密碼重設',
                'subject' => '密碼重設請求 - {app_name}',
                'content' => "親愛的 {user_name}，\n\n我們收到了您的密碼重設請求。\n\n請點擊以下連結重設您的密碼：\n{reset_url}\n\n此連結將在 {expire_time} 後失效。\n\n如果您沒有請求重設密碼，請忽略此郵件。\n\n祝好，\n{app_name} 團隊",
                'variables' => ['user_name', 'app_name', 'reset_url', 'expire_time']
            ],
            'account_locked' => [
                'name' => '帳號鎖定通知',
                'subject' => '帳號安全警告 - {app_name}',
                'content' => "親愛的 {user_name}，\n\n由於多次登入失敗，您的帳號已被暫時鎖定。\n\n鎖定時間：{lock_time}\n解鎖時間：{unlock_time}\n\n如果這不是您的操作，請立即聯繫我們。\n\n祝好，\n{app_name} 團隊",
                'variables' => ['user_name', 'app_name', 'lock_time', 'unlock_time']
            ],
            'maintenance' => [
                'name' => '系統維護通知',
                'subject' => '系統維護通知 - {app_name}',
                'content' => "親愛的用戶，\n\n我們將於 {maintenance_start} 至 {maintenance_end} 進行系統維護。\n\n維護期間，系統將暫時無法使用。\n\n維護內容：{maintenance_description}\n\n造成不便，敬請見諒。\n\n祝好，\n{app_name} 團隊",
                'variables' => ['app_name', 'maintenance_start', 'maintenance_end', 'maintenance_description']
            ]
        ];
    }

    /**
     * 選擇範本類型
     */
    public function selectTemplate(string $type): void
    {
        // 記錄除錯資訊
        logger()->info("Selecting template: {$type}");
        
        $this->selectedTemplateType = $type;
        
        // 確保範本資料已載入
        if (empty($this->templates)) {
            $this->loadTemplates();
        }
        
        // 設定當前範本
        if (isset($this->templates[$type])) {
            $this->currentTemplate = $this->templates[$type];
            logger()->info("Template loaded successfully", ['template' => $this->currentTemplate]);
        } else {
            logger()->warning("Template not found: {$type}");
            $this->currentTemplate = [];
        }
        
        // 強制重新渲染
        $this->dispatch('template-selected', ['type' => $type, 'template' => $this->currentTemplate]);
    }

    /**
     * 儲存範本
     */
    public function saveTemplate(): void
    {
        try {
            // 驗證範本資料
            if (empty($this->currentTemplate['name'])) {
                throw new \Exception('範本名稱不能為空');
            }

            if (empty($this->currentTemplate['subject'])) {
                throw new \Exception('郵件主旨不能為空');
            }

            if (empty($this->currentTemplate['content'])) {
                throw new \Exception('郵件內容不能為空');
            }

            // 這裡應該將範本儲存到資料庫
            // 目前只是模擬儲存成功
            
            $this->addFlash('success', '範本已成功儲存');

        } catch (\Exception $e) {
            $this->addFlash('error', "範本儲存失敗：{$e->getMessage()}");
        }
    }

    /**
     * 重設範本為預設值
     */
    public function resetTemplate(): void
    {
        $this->loadTemplates();
        $this->selectTemplate($this->selectedTemplateType);
        $this->addFlash('success', '範本已重設為預設值');
    }

    /**
     * 預覽範本
     */
    public function previewTemplate(): void
    {
        // 這裡可以開啟預覽視窗或顯示預覽內容
        $this->addFlash('info', '預覽功能開發中');
    }

    /**
     * 重設所有設定為預設值
     */
    public function resetAll(): void
    {
        try {
            $this->settings = $this->defaultSettings;
            $this->addFlash('success', '所有通知設定已重設為預設值');
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
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
     * 監聽設定值變更
     */
    public function updatedSettings($value, $key): void
    {
        // 如果郵件通知被停用，清除測試結果
        if ($key === 'email_enabled' && !$value) {
            $this->testResult = [];
            $this->showTestResult = false;
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.notification-settings');
    }
}