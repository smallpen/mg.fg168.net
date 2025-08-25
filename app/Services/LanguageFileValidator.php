<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

/**
 * 語言檔案驗證器
 * 
 * 用於檢查語言檔案的完整性和一致性
 */
class LanguageFileValidator
{
    /**
     * 支援的語言列表
     */
    private array $supportedLocales = ['zh_TW', 'en'];
    
    /**
     * 語言檔案路徑
     */
    private string $langPath;
    
    /**
     * 驗證結果
     */
    private array $validationResults = [];
    
    public function __construct()
    {
        $this->langPath = lang_path();
    }
    
    /**
     * 驗證語言檔案完整性
     */
    public function validateCompleteness(): array
    {
        $this->validationResults = [
            'missing_keys' => [],
            'extra_keys' => [],
            'missing_files' => [],
            'hardcoded_texts' => [],
            'summary' => []
        ];
        
        $allLanguageFiles = $this->getAllLanguageFiles();
        
        foreach ($allLanguageFiles as $filename) {
            $this->validateFileCompleteness($filename);
        }
        
        $this->generateSummary();
        
        return $this->validationResults;
    }
    
    /**
     * 尋找缺少的翻譯鍵
     */
    public function findMissingKeys(): array
    {
        $missingKeys = [];
        $allFiles = $this->getAllLanguageFiles();
        
        foreach ($allFiles as $filename) {
            $keys = [];
            
            foreach ($this->supportedLocales as $locale) {
                $filePath = $this->langPath . "/{$locale}/{$filename}";
                
                if (File::exists($filePath)) {
                    $content = include $filePath;
                    $keys[$locale] = $this->flattenArray($content);
                } else {
                    $keys[$locale] = [];
                }
            }
            
            // 比較不同語言的鍵值
            $allKeys = array_unique(array_merge(...array_values($keys)));
            
            foreach ($this->supportedLocales as $locale) {
                $missing = array_diff($allKeys, array_keys($keys[$locale]));
                if (!empty($missing)) {
                    $missingKeys[$filename][$locale] = $missing;
                }
            }
        }
        
        return $missingKeys;
    }
    
    /**
     * 檢測硬編碼文字
     */
    public function detectHardcodedText(): array
    {
        $hardcodedTexts = [];
        $viewPaths = [
            resource_path('views'),
            app_path('Livewire')
        ];
        
        foreach ($viewPaths as $path) {
            $hardcodedTexts = array_merge(
                $hardcodedTexts,
                $this->scanDirectoryForHardcodedText($path)
            );
        }
        
        return $hardcodedTexts;
    }
    
    /**
     * 生成語言檔案報告
     */
    public function generateReport(): array
    {
        $completenessResult = $this->validateCompleteness();
        $missingKeys = $this->findMissingKeys();
        $hardcodedTexts = $this->detectHardcodedText();
        
        return [
            'timestamp' => now()->toDateTimeString(),
            'supported_locales' => $this->supportedLocales,
            'completeness' => $completenessResult,
            'missing_keys' => $missingKeys,
            'hardcoded_texts' => $hardcodedTexts,
            'recommendations' => $this->generateRecommendations($completenessResult, $missingKeys, $hardcodedTexts)
        ];
    }
    
    /**
     * 取得所有語言檔案
     */
    private function getAllLanguageFiles(): array
    {
        $files = [];
        
        foreach ($this->supportedLocales as $locale) {
            $localePath = $this->langPath . "/{$locale}";
            
            if (File::isDirectory($localePath)) {
                $localeFiles = File::files($localePath);
                
                foreach ($localeFiles as $file) {
                    $filename = $file->getFilename();
                    if (!in_array($filename, $files)) {
                        $files[] = $filename;
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * 驗證單個檔案的完整性
     */
    private function validateFileCompleteness(string $filename): void
    {
        $fileKeys = [];
        
        foreach ($this->supportedLocales as $locale) {
            $filePath = $this->langPath . "/{$locale}/{$filename}";
            
            if (File::exists($filePath)) {
                try {
                    $content = include $filePath;
                    $fileKeys[$locale] = $this->flattenArray($content);
                } catch (\Exception $e) {
                    $this->validationResults['missing_files'][] = [
                        'file' => $filePath,
                        'error' => $e->getMessage()
                    ];
                }
            } else {
                $this->validationResults['missing_files'][] = [
                    'file' => $filePath,
                    'error' => 'File does not exist'
                ];
            }
        }
        
        // 比較鍵值差異
        if (count($fileKeys) >= 2) {
            $locales = array_keys($fileKeys);
            $baseLocale = $locales[0];
            
            for ($i = 1; $i < count($locales); $i++) {
                $compareLocale = $locales[$i];
                
                $missingInCompare = array_diff(
                    array_keys($fileKeys[$baseLocale]),
                    array_keys($fileKeys[$compareLocale])
                );
                
                $extraInCompare = array_diff(
                    array_keys($fileKeys[$compareLocale]),
                    array_keys($fileKeys[$baseLocale])
                );
                
                if (!empty($missingInCompare)) {
                    $this->validationResults['missing_keys'][$filename][$compareLocale] = $missingInCompare;
                }
                
                if (!empty($extraInCompare)) {
                    $this->validationResults['extra_keys'][$filename][$compareLocale] = $extraInCompare;
                }
            }
        }
    }
    
    /**
     * 將多維陣列扁平化
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * 掃描目錄中的硬編碼文字
     */
    private function scanDirectoryForHardcodedText(string $directory): array
    {
        $hardcodedTexts = [];
        
        if (!File::isDirectory($directory)) {
            return $hardcodedTexts;
        }
        
        $files = File::allFiles($directory);
        
        foreach ($files as $file) {
            if (in_array($file->getExtension(), ['php', 'blade.php'])) {
                $content = File::get($file->getPathname());
                $matches = $this->findHardcodedTextInContent($content);
                
                if (!empty($matches)) {
                    $hardcodedTexts[$file->getRelativePathname()] = $matches;
                }
            }
        }
        
        return $hardcodedTexts;
    }
    
    /**
     * 在內容中尋找硬編碼文字
     */
    private function findHardcodedTextInContent(string $content): array
    {
        $matches = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            // 尋找中文字符但不在 __() 或 trans() 函數中的文字
            if (preg_match('/[\x{4e00}-\x{9fff}]+/u', $line)) {
                // 排除已經使用翻譯函數的行
                if (!preg_match('/__\(|trans\(|@lang\(|{{.*__\(|{{.*trans\(/', $line)) {
                    // 排除註解行
                    if (!preg_match('/^\s*\/\/|^\s*\*|^\s*\/\*/', trim($line))) {
                        $matches[] = [
                            'line' => $lineNumber + 1,
                            'content' => trim($line),
                            'chinese_text' => $this->extractChineseText($line)
                        ];
                    }
                }
            }
        }
        
        return $matches;
    }
    
    /**
     * 提取中文文字
     */
    private function extractChineseText(string $text): array
    {
        preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $text, $matches);
        return $matches[0] ?? [];
    }
    
    /**
     * 生成摘要
     */
    private function generateSummary(): void
    {
        $this->validationResults['summary'] = [
            'total_files_checked' => count($this->getAllLanguageFiles()),
            'missing_files_count' => count($this->validationResults['missing_files']),
            'files_with_missing_keys' => count($this->validationResults['missing_keys']),
            'files_with_extra_keys' => count($this->validationResults['extra_keys']),
            'hardcoded_files_count' => count($this->validationResults['hardcoded_texts']),
            'total_hardcoded_instances' => array_sum(array_map('count', $this->validationResults['hardcoded_texts']))
        ];
    }
    
    /**
     * 生成建議
     */
    private function generateRecommendations(array $completeness, array $missingKeys, array $hardcodedTexts): array
    {
        $recommendations = [];
        
        if (!empty($completeness['missing_files'])) {
            $recommendations[] = '建議建立缺少的語言檔案以確保完整性';
        }
        
        if (!empty($missingKeys)) {
            $recommendations[] = '建議補充缺少的翻譯鍵以保持語言檔案同步';
        }
        
        if (!empty($hardcodedTexts)) {
            $recommendations[] = '建議將硬編碼文字移至語言檔案中';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = '語言檔案狀態良好，無需特別處理';
        }
        
        return $recommendations;
    }
}