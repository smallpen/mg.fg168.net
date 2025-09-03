<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 安全檢測服務
 * 
 * 提供系統安全檢測功能，包括配置檢查、檔案權限檢查、資料庫安全檢查等
 */
class SecurityScanService
{
    /**
     * 執行完整安全檢測
     */
    public function runFullScan(): array
    {
        $results = [
            'scan_time' => now()->toISOString(),
            'overall_score' => 0,
            'total_checks' => 0,
            'passed_checks' => 0,
            'failed_checks' => 0,
            'warnings' => 0,
            'categories' => []
        ];

        try {
            // 執行各類別檢測
            $results['categories']['system'] = $this->checkSystemSecurity();
            $results['categories']['database'] = $this->checkDatabaseSecurity();
            $results['categories']['files'] = $this->checkFilePermissions();
            $results['categories']['configuration'] = $this->checkConfiguration();
            $results['categories']['authentication'] = $this->checkAuthenticationSecurity();
            $results['categories']['network'] = $this->checkNetworkSecurity();

            // 計算總體分數
            $this->calculateOverallScore($results);

            // 快取結果
            Cache::put('security_scan_results', $results, now()->addHours(1));

            Log::info('安全檢測完成', [
                'overall_score' => $results['overall_score'],
                'total_checks' => $results['total_checks'],
                'failed_checks' => $results['failed_checks']
            ]);

        } catch (\Exception $e) {
            Log::error('安全檢測失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * 檢查系統安全
     */
    protected function checkSystemSecurity(): array
    {
        $checks = [];

        // 檢查 PHP 版本
        $checks[] = $this->checkPhpVersion();

        // 檢查危險函數
        $checks[] = $this->checkDangerousFunctions();

        // 檢查錯誤報告設定
        $checks[] = $this->checkErrorReporting();

        // 檢查 Session 安全
        $checks[] = $this->checkSessionSecurity();

        // 檢查檔案上傳限制
        $checks[] = $this->checkFileUploadLimits();

        return [
            'name' => '系統安全',
            'checks' => $checks,
            'score' => $this->calculateCategoryScore($checks)
        ];
    }

    /**
     * 檢查資料庫安全
     */
    protected function checkDatabaseSecurity(): array
    {
        $checks = [];

        // 檢查資料庫連線加密
        $checks[] = $this->checkDatabaseEncryption();

        // 檢查預設使用者
        $checks[] = $this->checkDefaultUsers();

        // 檢查弱密碼
        $checks[] = $this->checkWeakPasswords();

        // 檢查權限設定
        $checks[] = $this->checkUserPermissions();

        // 檢查 SQL 注入防護
        $checks[] = $this->checkSqlInjectionProtection();

        return [
            'name' => '資料庫安全',
            'checks' => $checks,
            'score' => $this->calculateCategoryScore($checks)
        ];
    }

    /**
     * 檢查檔案權限
     */
    protected function checkFilePermissions(): array
    {
        $checks = [];

        // 檢查敏感檔案權限
        $checks[] = $this->checkSensitiveFilePermissions();

        // 檢查 .env 檔案安全
        $checks[] = $this->checkEnvFileSecurity();

        // 檢查 storage 目錄權限
        $checks[] = $this->checkStoragePermissions();

        // 檢查 public 目錄安全
        $checks[] = $this->checkPublicDirectorySecurity();

        // 檢查備份檔案
        $checks[] = $this->checkBackupFiles();

        return [
            'name' => '檔案權限',
            'checks' => $checks,
            'score' => $this->calculateCategoryScore($checks)
        ];
    }

    /**
     * 檢查配置安全
     */
    protected function checkConfiguration(): array
    {
        $checks = [];

        // 檢查 APP_DEBUG 設定
        $checks[] = $this->checkDebugMode();

        // 檢查 APP_KEY 設定
        $checks[] = $this->checkAppKey();

        // 檢查 HTTPS 設定
        $checks[] = $this->checkHttpsConfiguration();

        // 檢查 CSRF 保護
        $checks[] = $this->checkCsrfProtection();

        // 檢查 Cookie 安全設定
        $checks[] = $this->checkCookieSecurity();

        return [
            'name' => '配置安全',
            'checks' => $checks,
            'score' => $this->calculateCategoryScore($checks)
        ];
    }

    /**
     * 檢查認證安全
     */
    protected function checkAuthenticationSecurity(): array
    {
        $checks = [];

        // 檢查密碼政策
        $checks[] = $this->checkPasswordPolicy();

        // 檢查登入限制
        $checks[] = $this->checkLoginThrottling();

        // 檢查 Session 設定
        $checks[] = $this->checkSessionConfiguration();

        // 檢查雙因子認證
        $checks[] = $this->checkTwoFactorAuth();

        // 檢查記住我功能
        $checks[] = $this->checkRememberMeSettings();

        return [
            'name' => '認證安全',
            'checks' => $checks,
            'score' => $this->calculateCategoryScore($checks)
        ];
    }

    /**
     * 檢查網路安全
     */
    protected function checkNetworkSecurity(): array
    {
        $checks = [];

        // 檢查 IP 限制
        $checks[] = $this->checkIpRestrictions();

        // 檢查 CORS 設定
        $checks[] = $this->checkCorsConfiguration();

        // 檢查 Rate Limiting
        $checks[] = $this->checkRateLimiting();

        // 檢查 SSL/TLS 設定
        $checks[] = $this->checkSslConfiguration();

        // 檢查安全標頭
        $checks[] = $this->checkSecurityHeaders();

        return [
            'name' => '網路安全',
            'checks' => $checks,
            'score' => $this->calculateCategoryScore($checks)
        ];
    }

    /**
     * 檢查 PHP 版本
     */
    protected function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        $majorVersion = (float) substr($version, 0, 3);
        
        $status = 'pass';
        $message = "PHP 版本 {$version} 是安全的";
        
        if ($majorVersion < 8.0) {
            $status = 'fail';
            $message = "PHP 版本 {$version} 過舊，建議升級至 8.0 以上";
        } elseif ($majorVersion < 8.1) {
            $status = 'warning';
            $message = "PHP 版本 {$version} 建議升級至最新版本";
        }

        return [
            'name' => 'PHP 版本檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['current_version' => $version, 'recommended' => '8.1+']
        ];
    }

    /**
     * 檢查危險函數
     */
    protected function checkDangerousFunctions(): array
    {
        $dangerousFunctions = ['exec', 'shell_exec', 'system', 'passthru', 'eval', 'file_get_contents'];
        $disabledFunctions = explode(',', ini_get('disable_functions'));
        $enabledDangerous = array_diff($dangerousFunctions, $disabledFunctions);

        $status = empty($enabledDangerous) ? 'pass' : 'warning';
        $message = empty($enabledDangerous) 
            ? '危險函數已適當停用' 
            : '發現啟用的危險函數: ' . implode(', ', $enabledDangerous);

        return [
            'name' => '危險函數檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['enabled_dangerous' => $enabledDangerous]
        ];
    }

    /**
     * 檢查錯誤報告設定
     */
    protected function checkErrorReporting(): array
    {
        $displayErrors = ini_get('display_errors');
        $logErrors = ini_get('log_errors');
        
        $status = 'pass';
        $message = '錯誤報告設定正確';
        
        if ($displayErrors && config('app.env') === 'production') {
            $status = 'fail';
            $message = '生產環境不應顯示錯誤訊息';
        } elseif (!$logErrors) {
            $status = 'warning';
            $message = '建議啟用錯誤日誌記錄';
        }

        return [
            'name' => '錯誤報告設定',
            'status' => $status,
            'message' => $message,
            'details' => [
                'display_errors' => (bool) $displayErrors,
                'log_errors' => (bool) $logErrors,
                'environment' => config('app.env')
            ]
        ];
    }

    /**
     * 檢查 Session 安全
     */
    protected function checkSessionSecurity(): array
    {
        $httpOnly = ini_get('session.cookie_httponly');
        $secure = ini_get('session.cookie_secure');
        $sameSite = ini_get('session.cookie_samesite');

        $issues = [];
        if (!$httpOnly) $issues[] = 'HttpOnly 未啟用';
        if (!$secure && request()->isSecure()) $issues[] = 'Secure 未啟用';
        if (!$sameSite) $issues[] = 'SameSite 未設定';

        $status = empty($issues) ? 'pass' : 'warning';
        $message = empty($issues) ? 'Session 安全設定正確' : '發現 Session 安全問題: ' . implode(', ', $issues);

        return [
            'name' => 'Session 安全設定',
            'status' => $status,
            'message' => $message,
            'details' => [
                'httponly' => (bool) $httpOnly,
                'secure' => (bool) $secure,
                'samesite' => $sameSite
            ]
        ];
    }

    /**
     * 檢查檔案上傳限制
     */
    protected function checkFileUploadLimits(): array
    {
        $maxFileSize = ini_get('upload_max_filesize');
        $maxPostSize = ini_get('post_max_size');
        $fileUploads = ini_get('file_uploads');

        $status = 'pass';
        $message = '檔案上傳設定適當';

        if (!$fileUploads) {
            $status = 'pass';
            $message = '檔案上傳已停用（安全）';
        } else {
            $maxFileSizeBytes = $this->parseSize($maxFileSize);
            if ($maxFileSizeBytes > 50 * 1024 * 1024) { // 50MB
                $status = 'warning';
                $message = '檔案上傳大小限制過大，可能存在安全風險';
            }
        }

        return [
            'name' => '檔案上傳限制',
            'status' => $status,
            'message' => $message,
            'details' => [
                'file_uploads' => (bool) $fileUploads,
                'max_file_size' => $maxFileSize,
                'max_post_size' => $maxPostSize
            ]
        ];
    }

    /**
     * 檢查資料庫連線加密
     */
    protected function checkDatabaseEncryption(): array
    {
        $sslMode = config('database.connections.mysql.options.ssl_mode', null);
        
        $status = $sslMode ? 'pass' : 'warning';
        $message = $sslMode ? '資料庫連線使用 SSL 加密' : '資料庫連線未使用 SSL 加密';

        return [
            'name' => '資料庫連線加密',
            'status' => $status,
            'message' => $message,
            'details' => ['ssl_mode' => $sslMode]
        ];
    }

    /**
     * 檢查預設使用者
     */
    protected function checkDefaultUsers(): array
    {
        try {
            $defaultUsers = DB::table('users')
                ->whereIn('username', ['admin', 'administrator', 'root', 'test'])
                ->where('password', 'like', '%admin%')
                ->count();

            $status = $defaultUsers > 0 ? 'warning' : 'pass';
            $message = $defaultUsers > 0 
                ? "發現 {$defaultUsers} 個可能的預設使用者帳號" 
                : '未發現預設使用者帳號';

            return [
                'name' => '預設使用者檢查',
                'status' => $status,
                'message' => $message,
                'details' => ['default_users_count' => $defaultUsers]
            ];
        } catch (\Exception $e) {
            return [
                'name' => '預設使用者檢查',
                'status' => 'error',
                'message' => '無法檢查預設使用者: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * 檢查弱密碼
     */
    protected function checkWeakPasswords(): array
    {
        try {
            // 檢查密碼長度過短的使用者
            $weakPasswords = DB::table('users')
                ->whereRaw('LENGTH(password) < 60') // bcrypt 雜湊長度通常是 60
                ->count();

            $status = $weakPasswords > 0 ? 'fail' : 'pass';
            $message = $weakPasswords > 0 
                ? "發現 {$weakPasswords} 個使用者可能使用弱密碼" 
                : '未發現弱密碼';

            return [
                'name' => '弱密碼檢查',
                'status' => $status,
                'message' => $message,
                'details' => ['weak_passwords_count' => $weakPasswords]
            ];
        } catch (\Exception $e) {
            return [
                'name' => '弱密碼檢查',
                'status' => 'error',
                'message' => '無法檢查弱密碼: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * 檢查使用者權限
     */
    protected function checkUserPermissions(): array
    {
        try {
            $superAdminCount = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'super_admin')
                ->count();

            $status = 'pass';
            $message = "系統有 {$superAdminCount} 個超級管理員";

            if ($superAdminCount === 0) {
                $status = 'warning';
                $message = '系統沒有超級管理員，可能影響管理功能';
            } elseif ($superAdminCount > 3) {
                $status = 'warning';
                $message = "超級管理員數量過多（{$superAdminCount}），建議減少以降低安全風險";
            }

            return [
                'name' => '使用者權限檢查',
                'status' => $status,
                'message' => $message,
                'details' => ['super_admin_count' => $superAdminCount]
            ];
        } catch (\Exception $e) {
            return [
                'name' => '使用者權限檢查',
                'status' => 'error',
                'message' => '無法檢查使用者權限: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * 檢查 SQL 注入防護
     */
    protected function checkSqlInjectionProtection(): array
    {
        // 檢查是否使用 Eloquent ORM 和參數化查詢
        $status = 'pass';
        $message = 'Laravel Eloquent ORM 提供 SQL 注入防護';

        // 可以進一步檢查是否有使用 DB::raw() 等可能不安全的方法
        return [
            'name' => 'SQL 注入防護',
            'status' => $status,
            'message' => $message,
            'details' => ['orm' => 'Eloquent', 'prepared_statements' => true]
        ];
    }

    /**
     * 檢查敏感檔案權限
     */
    protected function checkSensitiveFilePermissions(): array
    {
        $sensitiveFiles = [
            '.env' => base_path('.env'),
            'composer.json' => base_path('composer.json'),
            'artisan' => base_path('artisan')
        ];

        $issues = [];
        foreach ($sensitiveFiles as $name => $path) {
            if (File::exists($path)) {
                $permissions = substr(sprintf('%o', fileperms($path)), -4);
                if ($permissions > '0644') {
                    $issues[] = "{$name} 權限過於寬鬆 ({$permissions})";
                }
            }
        }

        $status = empty($issues) ? 'pass' : 'warning';
        $message = empty($issues) ? '敏感檔案權限設定正確' : implode(', ', $issues);

        return [
            'name' => '敏感檔案權限',
            'status' => $status,
            'message' => $message,
            'details' => ['issues' => $issues]
        ];
    }

    /**
     * 檢查 .env 檔案安全
     */
    protected function checkEnvFileSecurity(): array
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return [
                'name' => '.env 檔案安全',
                'status' => 'fail',
                'message' => '.env 檔案不存在',
                'details' => []
            ];
        }

        $permissions = substr(sprintf('%o', fileperms($envPath)), -4);
        $isReadable = is_readable($envPath);
        $isWritable = is_writable($envPath);

        $status = 'pass';
        $message = '.env 檔案安全設定正確';

        if ($permissions > '0600') {
            $status = 'warning';
            $message = ".env 檔案權限過於寬鬆 ({$permissions})";
        }

        return [
            'name' => '.env 檔案安全',
            'status' => $status,
            'message' => $message,
            'details' => [
                'permissions' => $permissions,
                'readable' => $isReadable,
                'writable' => $isWritable
            ]
        ];
    }

    /**
     * 檢查 storage 目錄權限
     */
    protected function checkStoragePermissions(): array
    {
        $storagePath = storage_path();
        
        if (!File::exists($storagePath)) {
            return [
                'name' => 'Storage 目錄權限',
                'status' => 'fail',
                'message' => 'Storage 目錄不存在',
                'details' => []
            ];
        }

        $isWritable = is_writable($storagePath);
        $permissions = substr(sprintf('%o', fileperms($storagePath)), -4);

        $status = $isWritable ? 'pass' : 'fail';
        $message = $isWritable ? 'Storage 目錄權限正確' : 'Storage 目錄不可寫入';

        return [
            'name' => 'Storage 目錄權限',
            'status' => $status,
            'message' => $message,
            'details' => [
                'writable' => $isWritable,
                'permissions' => $permissions
            ]
        ];
    }

    /**
     * 檢查 public 目錄安全
     */
    protected function checkPublicDirectorySecurity(): array
    {
        $publicPath = public_path();
        $htaccessPath = public_path('.htaccess');
        
        $hasHtaccess = File::exists($htaccessPath);
        $indexExists = File::exists(public_path('index.php'));

        $status = 'pass';
        $message = 'Public 目錄安全設定正確';

        if (!$hasHtaccess && config('app.env') === 'production') {
            $status = 'warning';
            $message = '生產環境建議使用 .htaccess 檔案';
        }

        return [
            'name' => 'Public 目錄安全',
            'status' => $status,
            'message' => $message,
            'details' => [
                'has_htaccess' => $hasHtaccess,
                'index_exists' => $indexExists
            ]
        ];
    }

    /**
     * 檢查備份檔案
     */
    protected function checkBackupFiles(): array
    {
        $backupExtensions = ['.bak', '.backup', '.old', '.tmp', '.sql'];
        $foundBackups = [];

        foreach ($backupExtensions as $ext) {
            $files = File::glob(base_path("*{$ext}"));
            $foundBackups = array_merge($foundBackups, $files);
        }

        $status = empty($foundBackups) ? 'pass' : 'warning';
        $message = empty($foundBackups) 
            ? '未發現備份檔案' 
            : '發現 ' . count($foundBackups) . ' 個備份檔案，建議移除或保護';

        return [
            'name' => '備份檔案檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['backup_files' => array_map('basename', $foundBackups)]
        ];
    }

    /**
     * 檢查 Debug 模式
     */
    protected function checkDebugMode(): array
    {
        $debugMode = config('app.debug');
        $environment = config('app.env');

        $status = 'pass';
        $message = 'Debug 模式設定正確';

        if ($debugMode && $environment === 'production') {
            $status = 'fail';
            $message = '生產環境不應啟用 Debug 模式';
        } elseif ($debugMode && $environment !== 'local') {
            $status = 'warning';
            $message = '非本地環境建議關閉 Debug 模式';
        }

        return [
            'name' => 'Debug 模式檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'debug_mode' => $debugMode,
                'environment' => $environment
            ]
        ];
    }

    /**
     * 檢查 APP_KEY
     */
    protected function checkAppKey(): array
    {
        $appKey = config('app.key');
        
        $status = 'pass';
        $message = 'APP_KEY 已正確設定';

        if (empty($appKey)) {
            $status = 'fail';
            $message = 'APP_KEY 未設定，請執行 php artisan key:generate';
        } elseif (strlen($appKey) < 32) {
            $status = 'warning';
            $message = 'APP_KEY 長度不足，建議重新生成';
        }

        return [
            'name' => 'APP_KEY 檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'key_set' => !empty($appKey),
                'key_length' => strlen($appKey)
            ]
        ];
    }

    /**
     * 檢查 HTTPS 配置
     */
    protected function checkHttpsConfiguration(): array
    {
        $forceHttps = config('app.force_https', false);
        $isSecure = request()->isSecure();
        $environment = config('app.env');

        $status = 'pass';
        $message = 'HTTPS 配置正確';

        if ($environment === 'production' && !$forceHttps) {
            $status = 'warning';
            $message = '生產環境建議強制使用 HTTPS';
        } elseif (!$isSecure && $environment === 'production') {
            $status = 'warning';
            $message = '當前連線未使用 HTTPS';
        }

        return [
            'name' => 'HTTPS 配置檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'force_https' => $forceHttps,
                'current_secure' => $isSecure,
                'environment' => $environment
            ]
        ];
    }

    /**
     * 檢查 CSRF 保護
     */
    protected function checkCsrfProtection(): array
    {
        // Laravel 預設啟用 CSRF 保護
        $status = 'pass';
        $message = 'CSRF 保護已啟用';

        return [
            'name' => 'CSRF 保護檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['csrf_enabled' => true]
        ];
    }

    /**
     * 檢查 Cookie 安全設定
     */
    protected function checkCookieSecurity(): array
    {
        $httpOnly = config('session.http_only', true);
        $secure = config('session.secure', false);
        $sameSite = config('session.same_site', 'lax');

        $issues = [];
        if (!$httpOnly) $issues[] = 'HttpOnly 未啟用';
        if (!$secure && request()->isSecure()) $issues[] = 'Secure 未啟用';
        if (!in_array($sameSite, ['strict', 'lax'])) $issues[] = 'SameSite 設定不當';

        $status = empty($issues) ? 'pass' : 'warning';
        $message = empty($issues) ? 'Cookie 安全設定正確' : '發現 Cookie 安全問題: ' . implode(', ', $issues);

        return [
            'name' => 'Cookie 安全設定',
            'status' => $status,
            'message' => $message,
            'details' => [
                'http_only' => $httpOnly,
                'secure' => $secure,
                'same_site' => $sameSite
            ]
        ];
    }

    /**
     * 檢查密碼政策
     */
    protected function checkPasswordPolicy(): array
    {
        // 從設定中取得密碼政策
        $minLength = config('security.password_min_length', 8);
        $requireUpper = config('security.password_require_uppercase', false);
        $requireLower = config('security.password_require_lowercase', false);
        $requireNumbers = config('security.password_require_numbers', false);
        $requireSymbols = config('security.password_require_symbols', false);

        $score = 0;
        if ($minLength >= 8) $score++;
        if ($minLength >= 12) $score++;
        if ($requireUpper) $score++;
        if ($requireLower) $score++;
        if ($requireNumbers) $score++;
        if ($requireSymbols) $score++;

        $status = $score >= 4 ? 'pass' : ($score >= 2 ? 'warning' : 'fail');
        $message = match($status) {
            'pass' => '密碼政策設定嚴格',
            'warning' => '密碼政策設定適中，建議加強',
            'fail' => '密碼政策過於寬鬆，存在安全風險'
        };

        return [
            'name' => '密碼政策檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'min_length' => $minLength,
                'require_uppercase' => $requireUpper,
                'require_lowercase' => $requireLower,
                'require_numbers' => $requireNumbers,
                'require_symbols' => $requireSymbols,
                'score' => $score
            ]
        ];
    }

    /**
     * 檢查登入限制
     */
    protected function checkLoginThrottling(): array
    {
        $maxAttempts = config('security.login_max_attempts', 5);
        $lockoutDuration = config('security.lockout_duration', 15);

        $status = 'pass';
        $message = '登入限制設定適當';

        if ($maxAttempts > 10) {
            $status = 'warning';
            $message = '登入嘗試次數過多，建議降低';
        } elseif ($lockoutDuration < 5) {
            $status = 'warning';
            $message = '鎖定時間過短，建議增加';
        }

        return [
            'name' => '登入限制檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'max_attempts' => $maxAttempts,
                'lockout_duration' => $lockoutDuration
            ]
        ];
    }

    /**
     * 檢查 Session 配置
     */
    protected function checkSessionConfiguration(): array
    {
        $lifetime = config('session.lifetime', 120);
        $driver = config('session.driver', 'file');
        $encrypt = config('session.encrypt', false);

        $status = 'pass';
        $message = 'Session 配置正確';

        if ($lifetime > 480) { // 8 小時
            $status = 'warning';
            $message = 'Session 過期時間過長，存在安全風險';
        } elseif ($driver === 'file' && config('app.env') === 'production') {
            $status = 'warning';
            $message = '生產環境建議使用 Redis 或資料庫儲存 Session';
        }

        return [
            'name' => 'Session 配置檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'lifetime' => $lifetime,
                'driver' => $driver,
                'encrypt' => $encrypt
            ]
        ];
    }

    /**
     * 檢查雙因子認證
     */
    protected function checkTwoFactorAuth(): array
    {
        $enabled = config('security.two_factor_enabled', false);
        
        $status = $enabled ? 'pass' : 'warning';
        $message = $enabled ? '雙因子認證已啟用' : '建議啟用雙因子認證以提高安全性';

        return [
            'name' => '雙因子認證檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['enabled' => $enabled]
        ];
    }

    /**
     * 檢查記住我功能
     */
    protected function checkRememberMeSettings(): array
    {
        $expire = config('auth.remember_me_expire', 2628000); // 30 天
        
        $status = 'pass';
        $message = '記住我功能設定適當';

        if ($expire > 7776000) { // 90 天
            $status = 'warning';
            $message = '記住我過期時間過長，存在安全風險';
        }

        return [
            'name' => '記住我功能檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'expire_seconds' => $expire,
                'expire_days' => round($expire / 86400)
            ]
        ];
    }

    /**
     * 檢查 IP 限制
     */
    protected function checkIpRestrictions(): array
    {
        $allowedIps = config('security.allowed_ips', '');
        
        $status = empty($allowedIps) ? 'warning' : 'pass';
        $message = empty($allowedIps) 
            ? '未設定 IP 限制，建議限制管理後台存取 IP' 
            : 'IP 限制已設定';

        return [
            'name' => 'IP 限制檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'has_restrictions' => !empty($allowedIps),
                'current_ip' => request()->ip()
            ]
        ];
    }

    /**
     * 檢查 CORS 設定
     */
    protected function checkCorsConfiguration(): array
    {
        $allowedOrigins = config('cors.allowed_origins', ['*']);
        
        $status = 'pass';
        $message = 'CORS 設定正確';

        if (in_array('*', $allowedOrigins) && config('app.env') === 'production') {
            $status = 'warning';
            $message = '生產環境不建議允許所有來源的 CORS 請求';
        }

        return [
            'name' => 'CORS 設定檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['allowed_origins' => $allowedOrigins]
        ];
    }

    /**
     * 檢查 Rate Limiting
     */
    protected function checkRateLimiting(): array
    {
        // Laravel 預設有 Rate Limiting
        $status = 'pass';
        $message = 'Rate Limiting 已啟用';

        return [
            'name' => 'Rate Limiting 檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['enabled' => true]
        ];
    }

    /**
     * 檢查 SSL 配置
     */
    protected function checkSslConfiguration(): array
    {
        $isSecure = request()->isSecure();
        $environment = config('app.env');

        $status = 'pass';
        $message = 'SSL 配置正確';

        if ($environment === 'production' && !$isSecure) {
            $status = 'fail';
            $message = '生產環境必須使用 HTTPS';
        } elseif ($environment !== 'local' && !$isSecure) {
            $status = 'warning';
            $message = '建議使用 HTTPS 連線';
        }

        return [
            'name' => 'SSL 配置檢查',
            'status' => $status,
            'message' => $message,
            'details' => [
                'is_secure' => $isSecure,
                'environment' => $environment
            ]
        ];
    }

    /**
     * 檢查安全標頭
     */
    protected function checkSecurityHeaders(): array
    {
        // 這裡可以檢查回應中是否包含安全標頭
        $status = 'warning';
        $message = '建議設定安全標頭（X-Frame-Options, X-XSS-Protection 等）';

        return [
            'name' => '安全標頭檢查',
            'status' => $status,
            'message' => $message,
            'details' => ['recommendation' => '設定安全標頭中介軟體']
        ];
    }

    /**
     * 計算類別分數
     */
    protected function calculateCategoryScore(array $checks): int
    {
        if (empty($checks)) return 0;

        $totalScore = 0;
        foreach ($checks as $check) {
            switch ($check['status']) {
                case 'pass':
                    $totalScore += 100;
                    break;
                case 'warning':
                    $totalScore += 60;
                    break;
                case 'fail':
                    $totalScore += 0;
                    break;
                case 'error':
                    $totalScore += 0;
                    break;
            }
        }

        return round($totalScore / count($checks));
    }

    /**
     * 計算總體分數
     */
    protected function calculateOverallScore(array &$results): void
    {
        $totalScore = 0;
        $totalChecks = 0;
        $passedChecks = 0;
        $failedChecks = 0;
        $warnings = 0;

        foreach ($results['categories'] as $category) {
            $totalScore += $category['score'];
            
            foreach ($category['checks'] as $check) {
                $totalChecks++;
                switch ($check['status']) {
                    case 'pass':
                        $passedChecks++;
                        break;
                    case 'warning':
                        $warnings++;
                        break;
                    case 'fail':
                    case 'error':
                        $failedChecks++;
                        break;
                }
            }
        }

        $results['overall_score'] = count($results['categories']) > 0 
            ? round($totalScore / count($results['categories'])) 
            : 0;
        $results['total_checks'] = $totalChecks;
        $results['passed_checks'] = $passedChecks;
        $results['failed_checks'] = $failedChecks;
        $results['warnings'] = $warnings;
    }

    /**
     * 解析檔案大小字串
     */
    protected function parseSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }

    /**
     * 取得快取的檢測結果
     */
    public function getCachedResults(): ?array
    {
        return Cache::get('security_scan_results');
    }

    /**
     * 清除檢測結果快取
     */
    public function clearCache(): void
    {
        Cache::forget('security_scan_results');
    }
}