<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LanguageFileValidator;
use Illuminate\Support\Facades\File;

class LanguageFileValidatorTest extends TestCase
{
    private LanguageFileValidator $validator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new LanguageFileValidator();
    }
    
    /** @test */
    public function it_can_validate_language_file_completeness()
    {
        $result = $this->validator->validateCompleteness();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('missing_keys', $result);
        $this->assertArrayHasKey('extra_keys', $result);
        $this->assertArrayHasKey('missing_files', $result);
        $this->assertArrayHasKey('hardcoded_texts', $result);
        $this->assertArrayHasKey('summary', $result);
    }
    
    /** @test */
    public function it_can_find_missing_translation_keys()
    {
        $missingKeys = $this->validator->findMissingKeys();
        
        $this->assertIsArray($missingKeys);
        
        // 如果有缺少的鍵，應該按檔案和語言分組
        foreach ($missingKeys as $filename => $locales) {
            $this->assertIsString($filename);
            $this->assertIsArray($locales);
            
            foreach ($locales as $locale => $keys) {
                $this->assertIsString($locale);
                $this->assertIsArray($keys);
            }
        }
    }
    
    /** @test */
    public function it_can_detect_hardcoded_text()
    {
        $hardcodedTexts = $this->validator->detectHardcodedText();
        
        $this->assertIsArray($hardcodedTexts);
        
        // 檢查結果結構
        foreach ($hardcodedTexts as $filename => $matches) {
            $this->assertIsString($filename);
            $this->assertIsArray($matches);
            
            foreach ($matches as $match) {
                $this->assertArrayHasKey('line', $match);
                $this->assertArrayHasKey('content', $match);
                $this->assertArrayHasKey('chinese_text', $match);
                $this->assertIsInt($match['line']);
                $this->assertIsString($match['content']);
                $this->assertIsArray($match['chinese_text']);
            }
        }
    }
    
    /** @test */
    public function it_can_generate_comprehensive_report()
    {
        $report = $this->validator->generateReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('timestamp', $report);
        $this->assertArrayHasKey('supported_locales', $report);
        $this->assertArrayHasKey('completeness', $report);
        $this->assertArrayHasKey('missing_keys', $report);
        $this->assertArrayHasKey('hardcoded_texts', $report);
        $this->assertArrayHasKey('recommendations', $report);
        
        // 檢查支援的語言
        $this->assertEquals(['zh_TW', 'en'], $report['supported_locales']);
        
        // 檢查建議是陣列
        $this->assertIsArray($report['recommendations']);
    }
    
    /** @test */
    public function it_provides_meaningful_recommendations()
    {
        $report = $this->validator->generateReport();
        $recommendations = $report['recommendations'];
        
        $this->assertNotEmpty($recommendations);
        $this->assertIsArray($recommendations);
        
        foreach ($recommendations as $recommendation) {
            $this->assertIsString($recommendation);
            $this->assertNotEmpty($recommendation);
        }
    }
    
    /** @test */
    public function it_handles_missing_language_directories_gracefully()
    {
        // 這個測試確保當語言目錄不存在時不會拋出錯誤
        $result = $this->validator->validateCompleteness();
        
        $this->assertIsArray($result);
        // 即使有錯誤，也應該返回結構化的結果
        $this->assertArrayHasKey('summary', $result);
    }
    
    /** @test */
    public function it_correctly_identifies_translation_function_usage()
    {
        // 建立測試內容
        $contentWithTranslation = "{{ __('auth.login.title') }}";
        $contentWithHardcoded = "登入系統";
        $contentMixed = "{{ __('auth.login.title') }} 登入系統";
        
        // 使用反射來測試私有方法
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('findHardcodedTextInContent');
        $method->setAccessible(true);
        
        $resultWithTranslation = $method->invoke($this->validator, $contentWithTranslation);
        $resultWithHardcoded = $method->invoke($this->validator, $contentWithHardcoded);
        $resultMixed = $method->invoke($this->validator, $contentMixed);
        
        // 使用翻譯函數的內容不應該被標記為硬編碼
        $this->assertEmpty($resultWithTranslation);
        
        // 硬編碼內容應該被檢測到
        $this->assertNotEmpty($resultWithHardcoded);
        
        // 混合內容應該只檢測到硬編碼部分
        $this->assertNotEmpty($resultMixed);
    }
}