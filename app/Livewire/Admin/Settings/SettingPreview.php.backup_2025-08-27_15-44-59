<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Services\ConfigurationService;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * 設定預覽元件
 * 
 * 提供設定的即時預覽、連線測試和影響分析功能
 */
class SettingPreview extends AdminComponent
{
    /**
     * 預覽設定陣列
     */
    public array $previewSettings = [];

    /**
     * 預覽模式
     */
    public string $previewMode = 'theme';

    /**
     * 顯示預覽面板
     */
    public bool $showPreview = false;

    /**
     * 連線測試結果
     */
    public array $connectionTests = [];

    /**
     * 正在測試的連線類型
     */
    public array $testingConnections = [];

    /**
     * 影響分析結果
     */
    public array $impactAnalysis = [];

    /**
     * 預覽主題設定
     */
    public array $themePreview = [
        'theme' => 'auto',
        'primary_color' => '#3B82F6',
        'secondary_color' => '#6B7280',
        'logo_url' => '',
        'background_url' => '',
    ];

    /**
     * 支援的預覽模式
     */
    protected array $supportedModes = [
        'theme' => '主題預覽',
        'email' => '郵件預覽',
        'layout' => '佈局預覽',
        'integration' => '整合預覽',
    ];

    /**
     * 取得配置服務
     */
    protected function getConfigService(): ConfigurationService
    {
        return app(ConfigurationService::class);
    }

    /**
     * 取得設定資料庫
     */
    protected function getSettingsRepository(): SettingsRepositoryInterface
    {
        return app(SettingsRepositoryInterface::class);
    }

    /**
     * 取得預覽資料
     */
    #[Computed]
    public function previewData(): array
    {
        return $this->getConfigService()->generatePreview($this->previewSettings);
    }

    /**
     * 取得可用的預覽模式
     */
    #[Computed]
    public function availableModes(): array
    {
        return $this->supportedModes;
    }

    /**
     * 取得當前預覽的 CSS 變數
     */
    #[Computed]
    public function previewCssVariables(): string
    {
        $css = '';
        
        if (isset($this->previewSettings['appearance.primary_color'])) {
            $css .= "--primary-color: {$this->previewSettings['appearance.primary_color']};";
        }
        
        if (isset($this->previewSettings['appearance.secondary_color'])) {
            $css .= "--secondary-color: {$this->previewSettings['appearance.secondary_color']};";
        }
        
        return $css;
    }

    /**
     * 取得預覽主題類別
     */
    #[Computed]
    public function previewThemeClass(): string
    {
        $theme = $this->previewSettings['appearance.default_theme'] ?? 'auto';
        
        switch ($theme) {
            case 'light':
                return 'theme-light';
            case 'dark':
                return 'theme-dark';
            default:
                return 'theme-auto';
        }
    }

    /**
     * 開始設定預覽
     */
    #[On('setting-preview-start')]
    public function startPreview(array $data): void
    {
        $this->previewSettings[$data['key']] = $data['value'];
        $this->updatePreviewMode($data['key']);
        $this->showPreview = true;
        
        // 更新主題預覽資料
        $this->updateThemePreview();
        
        // 分析設定變更影響
        $this->analyzeImpact($data['key'], $data['value']);
    }

    /**
     * 更新預覽設定
     */
    #[On('setting-preview-update')]
    public function updatePreview(array $data): void
    {
        $this->previewSettings[$data['key']] = $data['value'];
        
        // 更新主題預覽資料
        $this->updateThemePreview();
        
        // 重新分析影響
        $this->analyzeImpact($data['key'], $data['value']);
    }

    /**
     * 停止預覽
     */
    #[On('setting-preview-stop')]
    public function stopPreview(): void
    {
        $this->showPreview = false;
        $this->previewSettings = [];
        $this->impactAnalysis = [];
        $this->connectionTests = [];
    }

    /**
     * 切換預覽模式
     */
    public function switchPreviewMode(string $mode): void
    {
        if (array_key_exists($mode, $this->supportedModes)) {
            $this->previewMode = $mode;
        }
    }

    /**
     * 測試 SMTP 連線
     */
    public function testSmtpConnection(): void
    {
        $this->testingConnections['smtp'] = true;
        
        try {
            // 收集 SMTP 設定
            $smtpConfig = $this->collectSmtpSettings();
            
            if (empty($smtpConfig['host'])) {
                throw new \Exception('SMTP 主機未設定');
            }
            
            // 執行連線測試
            $result = $this->getConfigService()->testConnection('smtp', $smtpConfig);
            
            $this->connectionTests['smtp'] = [
                'success' => $result,
                'message' => $result ? 'SMTP 連線測試成功' : 'SMTP 連線測試失敗',
                'details' => $smtpConfig,
                'tested_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            if ($result) {
                $this->addFlash('success', 'SMTP 連線測試成功');
            } else {
                $this->addFlash('error', 'SMTP 連線測試失敗，請檢查設定');
            }
            
        } catch (\Exception $e) {
            $this->connectionTests['smtp'] = [
                'success' => false,
                'message' => "SMTP 連線測試錯誤：{$e->getMessage()}",
                'details' => [],
                'tested_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            $this->addFlash('error', "SMTP 連線測試錯誤：{$e->getMessage()}");
        } finally {
            $this->testingConnections['smtp'] = false;
        }
    }

    /**
     * 測試 AWS S3 連線
     */
    public function testAwsS3Connection(): void
    {
        $this->testingConnections['aws_s3'] = true;
        
        try {
            // 收集 AWS S3 設定
            $s3Config = $this->collectAwsS3Settings();
            
            if (empty($s3Config['access_key']) || empty($s3Config['secret_key'])) {
                throw new \Exception('AWS 認證資訊未設定');
            }
            
            // 執行連線測試
            $result = $this->getConfigService()->testConnection('aws_s3', $s3Config);
            
            $this->connectionTests['aws_s3'] = [
                'success' => $result,
                'message' => $result ? 'AWS S3 連線測試成功' : 'AWS S3 連線測試失敗',
                'details' => array_merge($s3Config, ['secret_key' => '***']), // 隱藏敏感資訊
                'tested_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            if ($result) {
                $this->addFlash('success', 'AWS S3 連線測試成功');
            } else {
                $this->addFlash('error', 'AWS S3 連線測試失敗，請檢查設定');
            }
            
        } catch (\Exception $e) {
            $this->connectionTests['aws_s3'] = [
                'success' => false,
                'message' => "AWS S3 連線測試錯誤：{$e->getMessage()}",
                'details' => [],
                'tested_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            $this->addFlash('error', "AWS S3 連線測試錯誤：{$e->getMessage()}");
        } finally {
            $this->testingConnections['aws_s3'] = false;
        }
    }

    /**
     * 測試 Google OAuth 連線
     */
    public function testGoogleOAuthConnection(): void
    {
        $this->testingConnections['google_oauth'] = true;
        
        try {
            // 收集 Google OAuth 設定
            $oauthConfig = $this->collectGoogleOAuthSettings();
            
            if (empty($oauthConfig['client_id']) || empty($oauthConfig['client_secret'])) {
                throw new \Exception('Google OAuth 認證資訊未設定');
            }
            
            // 執行連線測試
            $result = $this->getConfigService()->testConnection('google_oauth', $oauthConfig);
            
            $this->connectionTests['google_oauth'] = [
                'success' => $result,
                'message' => $result ? 'Google OAuth 連線測試成功' : 'Google OAuth 連線測試失敗',
                'details' => array_merge($oauthConfig, ['client_secret' => '***']), // 隱藏敏感資訊
                'tested_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            if ($result) {
                $this->addFlash('success', 'Google OAuth 連線測試成功');
            } else {
                $this->addFlash('error', 'Google OAuth 連線測試失敗，請檢查設定');
            }
            
        } catch (\Exception $e) {
            $this->connectionTests['google_oauth'] = [
                'success' => false,
                'message' => "Google OAuth 連線測試錯誤：{$e->getMessage()}",
                'details' => [],
                'tested_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            $this->addFlash('error', "Google OAuth 連線測試錯誤：{$e->getMessage()}");
        } finally {
            $this->testingConnections['google_oauth'] = false;
        }
    }

    /**
     * 測試所有可用連線
     */
    public function testAllConnections(): void
    {
        $this->testSmtpConnection();
        $this->testAwsS3Connection();
        $this->testGoogleOAuthConnection();
    }

    /**
     * 分析設定變更影響
     */
    public function analyzeImpact(string $settingKey, $value): void
    {
        $config = $this->getConfigService()->getSettingConfig($settingKey);
        $impacts = [];
        
        // 分析直接影響
        $impacts[] = [
            'type' => 'direct',
            'title' => '直接影響',
            'description' => $this->getDirectImpactDescription($settingKey, $value, $config),
            'severity' => $this->getImpactSeverity($settingKey),
        ];
        
        // 分析相依設定影響
        $dependentSettings = $this->findDependentSettings($settingKey);
        if (!empty($dependentSettings)) {
            $impacts[] = [
                'type' => 'dependencies',
                'title' => '相依設定影響',
                'description' => "此設定變更將影響 " . count($dependentSettings) . " 個相關設定",
                'details' => $dependentSettings,
                'severity' => 'medium',
            ];
        }
        
        // 分析系統功能影響
        $functionalImpacts = $this->analyzeFunctionalImpact($settingKey, $value);
        if (!empty($functionalImpacts)) {
            $impacts = array_merge($impacts, $functionalImpacts);
        }
        
        // 分析效能影響
        $performanceImpact = $this->analyzePerformanceImpact($settingKey, $value);
        if ($performanceImpact) {
            $impacts[] = $performanceImpact;
        }
        
        $this->impactAnalysis[$settingKey] = $impacts;
    }

    /**
     * 更新預覽模式
     */
    protected function updatePreviewMode(string $settingKey): void
    {
        // 根據設定類型自動切換預覽模式
        if (str_starts_with($settingKey, 'appearance.')) {
            $this->previewMode = 'theme';
        } elseif (str_starts_with($settingKey, 'notification.')) {
            $this->previewMode = 'email';
        } elseif (str_starts_with($settingKey, 'integration.')) {
            $this->previewMode = 'integration';
        } else {
            $this->previewMode = 'layout';
        }
    }

    /**
     * 更新主題預覽資料
     */
    protected function updateThemePreview(): void
    {
        $this->themePreview = [
            'theme' => $this->previewSettings['appearance.default_theme'] ?? $this->themePreview['theme'],
            'primary_color' => $this->previewSettings['appearance.primary_color'] ?? $this->themePreview['primary_color'],
            'secondary_color' => $this->previewSettings['appearance.secondary_color'] ?? $this->themePreview['secondary_color'],
            'logo_url' => $this->previewSettings['appearance.logo_url'] ?? $this->themePreview['logo_url'],
            'background_url' => $this->previewSettings['appearance.login_background_url'] ?? $this->themePreview['background_url'],
        ];
    }

    /**
     * 收集 SMTP 設定
     */
    protected function collectSmtpSettings(): array
    {
        $settings = [
            'host' => $this->getSettingValue('notification.smtp_host'),
            'port' => $this->getSettingValue('notification.smtp_port'),
            'encryption' => $this->getSettingValue('notification.smtp_encryption'),
            'username' => $this->getSettingValue('notification.smtp_username'),
            'password' => $this->getSettingValue('notification.smtp_password'),
        ];
        
        // 添加測試郵件地址
        $settings['test_email'] = 'test@example.com';
        
        return array_filter($settings);
    }

    /**
     * 收集 AWS S3 設定
     */
    protected function collectAwsS3Settings(): array
    {
        return [
            'access_key' => $this->getSettingValue('integration.aws_access_key'),
            'secret_key' => $this->getSettingValue('integration.aws_secret_key'),
            'region' => $this->getSettingValue('integration.aws_region'),
            'bucket' => $this->getSettingValue('integration.aws_bucket'),
        ];
    }

    /**
     * 收集 Google OAuth 設定
     */
    protected function collectGoogleOAuthSettings(): array
    {
        return [
            'client_id' => $this->getSettingValue('integration.google_client_id'),
            'client_secret' => $this->getSettingValue('integration.google_client_secret'),
        ];
    }

    /**
     * 取得設定值（優先使用預覽值）
     */
    protected function getSettingValue(string $key)
    {
        // 優先使用預覽設定
        if (isset($this->previewSettings[$key])) {
            return $this->previewSettings[$key];
        }
        
        // 否則從資料庫取得
        $setting = $this->getSettingsRepository()->getSetting($key);
        return $setting ? $setting->value : null;
    }

    /**
     * 取得直接影響描述
     */
    protected function getDirectImpactDescription(string $settingKey, $value, array $config): string
    {
        $descriptions = [
            'app.name' => "應用程式名稱將變更為「{$value}」，影響系統標題和品牌顯示",
            'app.timezone' => "系統時區將變更為「{$value}」，影響所有時間顯示和記錄",
            'security.password_min_length' => "密碼最小長度將變更為 {$value} 字元，影響新密碼驗證規則",
            'notification.email_enabled' => $value ? '啟用郵件通知功能' : '停用郵件通知功能',
            'appearance.primary_color' => "主要顏色將變更為 {$value}，影響按鈕、連結等元素顏色",
            'maintenance.maintenance_mode' => $value ? '啟用維護模式，一般使用者將無法存取系統' : '停用維護模式',
        ];
        
        return $descriptions[$settingKey] ?? ($config['description'] ?? '設定值將變更');
    }

    /**
     * 取得影響嚴重程度
     */
    protected function getImpactSeverity(string $settingKey): string
    {
        $highImpactSettings = [
            'maintenance.maintenance_mode',
            'security.force_https',
            'app.timezone',
        ];
        
        $mediumImpactSettings = [
            'security.password_min_length',
            'security.login_max_attempts',
            'notification.email_enabled',
        ];
        
        if (in_array($settingKey, $highImpactSettings)) {
            return 'high';
        } elseif (in_array($settingKey, $mediumImpactSettings)) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * 尋找相依設定
     */
    protected function findDependentSettings(string $settingKey): array
    {
        $dependencies = config('system-settings.dependencies', []);
        $dependentSettings = [];
        
        foreach ($dependencies as $dependent => $requiredSettings) {
            if (in_array($settingKey, $requiredSettings)) {
                $config = $this->getConfigService()->getSettingConfig($dependent);
                $dependentSettings[] = [
                    'key' => $dependent,
                    'name' => $config['description'] ?? $dependent,
                ];
            }
        }
        
        return $dependentSettings;
    }

    /**
     * 分析功能影響
     */
    protected function analyzeFunctionalImpact(string $settingKey, $value): array
    {
        $impacts = [];
        
        // 郵件功能影響
        if (str_starts_with($settingKey, 'notification.')) {
            $impacts[] = [
                'type' => 'functional',
                'title' => '郵件功能影響',
                'description' => '此變更將影響系統郵件發送功能',
                'severity' => 'medium',
            ];
        }
        
        // 安全功能影響
        if (str_starts_with($settingKey, 'security.')) {
            $impacts[] = [
                'type' => 'functional',
                'title' => '安全功能影響',
                'description' => '此變更將影響系統安全政策和使用者認證',
                'severity' => 'high',
            ];
        }
        
        // 外觀功能影響
        if (str_starts_with($settingKey, 'appearance.')) {
            $impacts[] = [
                'type' => 'functional',
                'title' => '使用者介面影響',
                'description' => '此變更將影響系統外觀和使用者體驗',
                'severity' => 'low',
            ];
        }
        
        return $impacts;
    }

    /**
     * 分析效能影響
     */
    protected function analyzePerformanceImpact(string $settingKey, $value): ?array
    {
        $performanceImpactSettings = [
            'maintenance.cache_driver' => '快取驅動變更可能影響系統效能',
            'maintenance.log_level' => '日誌等級變更可能影響磁碟使用和效能',
            'security.session_lifetime' => 'Session 時間變更可能影響記憶體使用',
        ];
        
        if (isset($performanceImpactSettings[$settingKey])) {
            return [
                'type' => 'performance',
                'title' => '效能影響',
                'description' => $performanceImpactSettings[$settingKey],
                'severity' => 'medium',
            ];
        }
        
        return null;
    }

    /**
     * 檢查是否正在測試連線
     */
    public function isTestingConnection(string $type): bool
    {
        return $this->testingConnections[$type] ?? false;
    }

    /**
     * 取得連線測試結果
     */
    public function getConnectionTestResult(string $type): ?array
    {
        return $this->connectionTests[$type] ?? null;
    }

    /**
     * 取得設定影響分析
     */
    public function getImpactAnalysis(string $settingKey): array
    {
        return $this->impactAnalysis[$settingKey] ?? [];
    }

    /**
     * 檢查是否有高影響設定
     */
    #[Computed]
    public function hasHighImpactChanges(): bool
    {
        foreach ($this->impactAnalysis as $impacts) {
            foreach ($impacts as $impact) {
                if (($impact['severity'] ?? 'low') === 'high') {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.setting-preview');
    }
}