<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use Tests\Traits\DisablesPermissionSecurity;

/**
 * 多語系測試基礎類別
 * 
 * 提供多語系功能測試的基礎設施和輔助方法
 */
abstract class MultilingualTestCase extends TestCase
{
    use RefreshDatabase, DisablesPermissionSecurity;

    /**
     * 支援的語言列表
     */
    protected array $supportedLocales = ['zh_TW', 'en'];

    /**
     * 預設測試語言
     */
    protected string $defaultTestLocale = 'zh_TW';

    /**
     * 當前測試語言
     */
    protected string $currentLocale;

    /**
     * 語言檔案快取
     */
    protected array $languageCache = [];

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 設定預設語言
        $this->setTestLocale($this->defaultTestLocale);
        
        // 載入語言檔案到快取
        $this->loadLanguageFiles();
        
        // 設定測試資料
        $this->setupMultilingualTestData();
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        // 重置語言設定
        App::setLocale(config('app.locale'));
        Session::forget('locale');
        
        parent::tearDown();
    }

    /**
     * 設定測試語言
     *
     * @param string $locale
     * @return $this
     */
    protected function setTestLocale(string $locale): self
    {
        if (!in_array($locale, $this->supportedLocales)) {
            throw new \InvalidArgumentException("不支援的語言: {$locale}");
        }

        $this->currentLocale = $locale;
        App::setLocale($locale);
        Session::put('locale', $locale);
        
        // 設定 Carbon 本地化
        \Carbon\Carbon::setLocale($locale);

        return $this;
    }

    /**
     * 取得當前測試語言
     *
     * @return string
     */
    protected function getCurrentLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * 切換到指定語言並執行測試
     *
     * @param string $locale
     * @param callable $callback
     * @return mixed
     */
    protected function withLocale(string $locale, callable $callback)
    {
        $originalLocale = $this->currentLocale;
        
        try {
            $this->setTestLocale($locale);
            return $callback();
        } finally {
            $this->setTestLocale($originalLocale);
        }
    }

    /**
     * 在所有支援的語言中執行測試
     *
     * @param callable $callback
     * @return array
     */
    protected function runInAllLocales(callable $callback): array
    {
        $results = [];
        
        foreach ($this->supportedLocales as $locale) {
            $results[$locale] = $this->withLocale($locale, $callback);
        }
        
        return $results;
    }

    /**
     * 驗證翻譯鍵是否存在
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    protected function translationExists(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->currentLocale;
        
        return $this->withLocale($locale, function () use ($key) {
            return Lang::has($key);
        });
    }

    /**
     * 取得翻譯內容
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    protected function getTranslation(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->currentLocale;
        
        return $this->withLocale($locale, function () use ($key, $replace) {
            return __($key, $replace);
        });
    }

    /**
     * 驗證翻譯內容是否正確
     *
     * @param string $key
     * @param string $expected
     * @param array $replace
     * @param string|null $locale
     * @return void
     */
    protected function assertTranslation(string $key, string $expected, array $replace = [], ?string $locale = null): void
    {
        $locale = $locale ?? $this->currentLocale;
        $actual = $this->getTranslation($key, $replace, $locale);
        
        $this->assertEquals($expected, $actual, 
            "翻譯鍵 '{$key}' 在語言 '{$locale}' 中的內容不符合預期");
    }

    /**
     * 驗證翻譯鍵在所有語言中都存在
     *
     * @param string $key
     * @return void
     */
    protected function assertTranslationExistsInAllLocales(string $key): void
    {
        foreach ($this->supportedLocales as $locale) {
            $this->assertTrue($this->translationExists($key, $locale),
                "翻譯鍵 '{$key}' 在語言 '{$locale}' 中不存在");
        }
    }

    /**
     * 驗證翻譯內容在不同語言中是不同的
     *
     * @param string $key
     * @param array $replace
     * @return void
     */
    protected function assertTranslationDiffersAcrossLocales(string $key, array $replace = []): void
    {
        $translations = [];
        
        foreach ($this->supportedLocales as $locale) {
            $translations[$locale] = $this->getTranslation($key, $replace, $locale);
        }
        
        // 檢查是否有不同的翻譯內容
        $uniqueTranslations = array_unique($translations);
        
        $this->assertGreaterThan(1, count($uniqueTranslations),
            "翻譯鍵 '{$key}' 在不同語言中的內容應該不同，但實際內容相同: " . 
            json_encode($translations, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 驗證語言檔案完整性
     *
     * @param string $filename
     * @return void
     */
    protected function assertLanguageFileCompleteness(string $filename): void
    {
        $baseKeys = $this->getLanguageFileKeys($filename, $this->defaultTestLocale);
        
        foreach ($this->supportedLocales as $locale) {
            if ($locale === $this->defaultTestLocale) {
                continue;
            }
            
            $localeKeys = $this->getLanguageFileKeys($filename, $locale);
            
            // 檢查缺少的鍵
            $missingKeys = array_diff($baseKeys, $localeKeys);
            $this->assertEmpty($missingKeys,
                "語言檔案 '{$filename}' 在語言 '{$locale}' 中缺少以下翻譯鍵: " . 
                implode(', ', $missingKeys));
            
            // 檢查多餘的鍵
            $extraKeys = array_diff($localeKeys, $baseKeys);
            $this->assertEmpty($extraKeys,
                "語言檔案 '{$filename}' 在語言 '{$locale}' 中有多餘的翻譯鍵: " . 
                implode(', ', $extraKeys));
        }
    }

    /**
     * 取得語言檔案的所有鍵
     *
     * @param string $filename
     * @param string $locale
     * @return array
     */
    protected function getLanguageFileKeys(string $filename, string $locale): array
    {
        $cacheKey = "{$locale}.{$filename}";
        
        if (!isset($this->languageCache[$cacheKey])) {
            $filePath = lang_path("{$locale}/{$filename}.php");
            
            if (!file_exists($filePath)) {
                return [];
            }
            
            $content = include $filePath;
            $this->languageCache[$cacheKey] = $this->flattenArray($content);
        }
        
        return array_keys($this->languageCache[$cacheKey]);
    }

    /**
     * 將多維陣列扁平化為點記法鍵
     *
     * @param array $array
     * @param string $prefix
     * @return array
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }

    /**
     * 載入語言檔案到快取
     *
     * @return void
     */
    protected function loadLanguageFiles(): void
    {
        foreach ($this->supportedLocales as $locale) {
            $langPath = lang_path($locale);
            
            if (!is_dir($langPath)) {
                continue;
            }
            
            $files = glob("{$langPath}/*.php");
            
            foreach ($files as $file) {
                $filename = basename($file, '.php');
                $this->getLanguageFileKeys($filename, $locale);
            }
        }
    }

    /**
     * 設定多語系測試資料
     *
     * @return void
     */
    protected function setupMultilingualTestData(): void
    {
        // 可在子類別中覆寫以設定特定的測試資料
    }

    /**
     * 模擬瀏覽器語言偏好
     *
     * @param string $acceptLanguage
     * @return $this
     */
    protected function withBrowserLanguage(string $acceptLanguage): self
    {
        $this->withHeaders([
            'Accept-Language' => $acceptLanguage
        ]);
        
        return $this;
    }

    /**
     * 模擬使用者語言偏好
     *
     * @param string $locale
     * @return $this
     */
    protected function withUserLanguagePreference(string $locale): self
    {
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
        
        return $this;
    }

    /**
     * 驗證語言切換功能
     *
     * @param string $url
     * @param string $targetLocale
     * @return void
     */
    protected function assertLanguageSwitching(string $url, string $targetLocale): void
    {
        // 發送語言切換請求
        $response = $this->get($url . '?locale=' . $targetLocale);
        
        // 驗證回應成功
        $response->assertSuccessful();
        
        // 驗證語言已切換
        $this->assertEquals($targetLocale, App::getLocale());
        $this->assertEquals($targetLocale, Session::get('locale'));
    }

    /**
     * 驗證語言持久化
     *
     * @param string $locale
     * @return void
     */
    protected function assertLanguagePersistence(string $locale): void
    {
        // 設定語言
        $this->setTestLocale($locale);
        
        // 模擬新的請求
        $this->refreshApplication();
        
        // 驗證語言是否持久化
        $this->assertEquals($locale, Session::get('locale'));
    }

    /**
     * 建立測試用的多語系內容
     *
     * @param array $content
     * @return array
     */
    protected function createMultilingualContent(array $content): array
    {
        $result = [];
        
        foreach ($this->supportedLocales as $locale) {
            $result[$locale] = $content[$locale] ?? $content['default'] ?? '';
        }
        
        return $result;
    }

    /**
     * 驗證多語系內容
     *
     * @param array $expected
     * @param callable $contentGetter
     * @return void
     */
    protected function assertMultilingualContent(array $expected, callable $contentGetter): void
    {
        foreach ($this->supportedLocales as $locale) {
            $this->withLocale($locale, function () use ($expected, $contentGetter, $locale) {
                $actual = $contentGetter();
                $expectedContent = $expected[$locale] ?? null;
                
                if ($expectedContent !== null) {
                    $this->assertEquals($expectedContent, $actual,
                        "多語系內容在語言 '{$locale}' 中不符合預期");
                }
            });
        }
    }

    /**
     * 取得語言檔案統計資訊
     *
     * @return array
     */
    protected function getLanguageFileStats(): array
    {
        $stats = [];
        
        foreach ($this->supportedLocales as $locale) {
            $stats[$locale] = [
                'files' => 0,
                'keys' => 0,
                'files_detail' => []
            ];
            
            $langPath = lang_path($locale);
            
            if (!is_dir($langPath)) {
                continue;
            }
            
            $files = glob("{$langPath}/*.php");
            $stats[$locale]['files'] = count($files);
            
            foreach ($files as $file) {
                $filename = basename($file, '.php');
                $keys = $this->getLanguageFileKeys($filename, $locale);
                $keyCount = count($keys);
                
                $stats[$locale]['keys'] += $keyCount;
                $stats[$locale]['files_detail'][$filename] = $keyCount;
            }
        }
        
        return $stats;
    }

    /**
     * 產生語言檔案完整性報告
     *
     * @return array
     */
    protected function generateLanguageCompletenessReport(): array
    {
        $report = [
            'summary' => [],
            'missing_keys' => [],
            'extra_keys' => [],
            'files' => []
        ];
        
        $baseLocale = $this->defaultTestLocale;
        $langPath = lang_path($baseLocale);
        
        if (!is_dir($langPath)) {
            return $report;
        }
        
        $files = glob("{$langPath}/*.php");
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $baseKeys = $this->getLanguageFileKeys($filename, $baseLocale);
            
            $report['files'][$filename] = [
                'base_keys_count' => count($baseKeys),
                'locales' => []
            ];
            
            foreach ($this->supportedLocales as $locale) {
                if ($locale === $baseLocale) {
                    continue;
                }
                
                $localeKeys = $this->getLanguageFileKeys($filename, $locale);
                $missingKeys = array_diff($baseKeys, $localeKeys);
                $extraKeys = array_diff($localeKeys, $baseKeys);
                
                $report['files'][$filename]['locales'][$locale] = [
                    'keys_count' => count($localeKeys),
                    'missing_count' => count($missingKeys),
                    'extra_count' => count($extraKeys),
                    'completeness' => count($baseKeys) > 0 ? 
                        (count($baseKeys) - count($missingKeys)) / count($baseKeys) * 100 : 100
                ];
                
                if (!empty($missingKeys)) {
                    $report['missing_keys'][$locale][$filename] = $missingKeys;
                }
                
                if (!empty($extraKeys)) {
                    $report['extra_keys'][$locale][$filename] = $extraKeys;
                }
            }
        }
        
        // 計算總體統計
        foreach ($this->supportedLocales as $locale) {
            if ($locale === $baseLocale) {
                continue;
            }
            
            $totalMissing = 0;
            $totalExtra = 0;
            $totalFiles = count($files);
            
            foreach ($report['files'] as $fileData) {
                if (isset($fileData['locales'][$locale])) {
                    $totalMissing += $fileData['locales'][$locale]['missing_count'];
                    $totalExtra += $fileData['locales'][$locale]['extra_count'];
                }
            }
            
            $report['summary'][$locale] = [
                'total_files' => $totalFiles,
                'total_missing_keys' => $totalMissing,
                'total_extra_keys' => $totalExtra,
                'files_with_issues' => count($report['missing_keys'][$locale] ?? []) + 
                                     count($report['extra_keys'][$locale] ?? [])
            ];
        }
        
        return $report;
    }
}