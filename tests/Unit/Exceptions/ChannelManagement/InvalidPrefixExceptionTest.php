<?php

namespace Tests\Unit\Exceptions\ChannelManagement;

use App\Exceptions\ChannelManagement\InvalidPrefixException;
use PHPUnit\Framework\TestCase;

/**
 * 無效前置符號例外類別測試
 * 
 * 測試 InvalidPrefixException 的各種建立方法和錯誤訊息
 * 對應需求: 3.2, 3.7, 17.2
 */
class InvalidPrefixExceptionTest extends TestCase
{
    /**
     * 測試預設例外建立
     */
    public function test_default_exception_creation(): void
    {
        $exception = new InvalidPrefixException();
        
        $this->assertEquals('前置符號無效', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義訊息例外建立
     */
    public function test_custom_message_exception_creation(): void
    {
        $customMessage = '自定義前置符號錯誤訊息';
        $exception = new InvalidPrefixException($customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義代碼例外建立
     */
    public function test_custom_code_exception_creation(): void
    {
        $customCode = 400;
        $exception = new InvalidPrefixException('測試訊息', $customCode);
        
        $this->assertEquals($customCode, $exception->getCode());
    }

    /**
     * 測試無效格式例外建立
     */
    public function test_invalid_format_exception_creation(): void
    {
        $invalidPrefix = 'A'; // 大寫字母
        $exception = InvalidPrefixException::invalidFormat($invalidPrefix);
        
        $expectedMessage = "前置符號「{$invalidPrefix}」格式不正確。前置符號必須是 a-z 的單一小寫字母。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試數字前置符號格式錯誤
     */
    public function test_numeric_prefix_format_error(): void
    {
        $invalidPrefix = '1';
        $exception = InvalidPrefixException::invalidFormat($invalidPrefix);
        
        $expectedMessage = "前置符號「{$invalidPrefix}」格式不正確。前置符號必須是 a-z 的單一小寫字母。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試特殊字元前置符號格式錯誤
     */
    public function test_special_character_prefix_format_error(): void
    {
        $invalidPrefix = '@';
        $exception = InvalidPrefixException::invalidFormat($invalidPrefix);
        
        $expectedMessage = "前置符號「{$invalidPrefix}」格式不正確。前置符號必須是 a-z 的單一小寫字母。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試前置符號已存在例外建立
     */
    public function test_already_exists_exception_creation(): void
    {
        $existingPrefix = 'a';
        $exception = InvalidPrefixException::alreadyExists($existingPrefix);
        
        $expectedMessage = "前置符號「{$existingPrefix}」已被其他第一層代理使用，請選擇其他前置符號。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試前置符號為空例外建立
     */
    public function test_empty_exception_creation(): void
    {
        $exception = InvalidPrefixException::empty();
        
        $expectedMessage = "第一層代理必須設定前置符號。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試前置符號長度錯誤例外建立
     */
    public function test_invalid_length_exception_creation(): void
    {
        $longPrefix = 'abc';
        $exception = InvalidPrefixException::invalidLength($longPrefix);
        
        $expectedMessage = "前置符號「{$longPrefix}」長度不正確（3 個字元）。前置符號必須是單一字母。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試空字串長度錯誤
     */
    public function test_empty_string_length_error(): void
    {
        $emptyPrefix = '';
        $exception = InvalidPrefixException::invalidLength($emptyPrefix);
        
        $expectedMessage = "前置符號「{$emptyPrefix}」長度不正確（0 個字元）。前置符號必須是單一字母。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試單一字元但非字母的長度錯誤
     */
    public function test_single_non_letter_character(): void
    {
        $nonLetterPrefix = '1';
        $exception = InvalidPrefixException::invalidLength($nonLetterPrefix);
        
        $expectedMessage = "前置符號「{$nonLetterPrefix}」長度不正確（1 個字元）。前置符號必須是單一字母。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試例外是否繼承自 Exception
     */
    public function test_exception_inheritance(): void
    {
        $exception = new InvalidPrefixException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * 測試例外鏈
     */
    public function test_exception_chaining(): void
    {
        $previousException = new \RuntimeException('前一個例外');
        $exception = new InvalidPrefixException('前置符號無效', 422, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    /**
     * 測試多位元組字元長度計算
     */
    public function test_multibyte_character_length(): void
    {
        $multibytePrefix = '中';
        $exception = InvalidPrefixException::invalidLength($multibytePrefix);
        
        $expectedMessage = "前置符號「{$multibytePrefix}」長度不正確（1 個字元）。前置符號必須是單一字母。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試所有小寫字母的格式錯誤（模擬已存在情況）
     */
    public function test_all_lowercase_letters_already_exist(): void
    {
        $letters = range('a', 'z');
        
        foreach ($letters as $letter) {
            $exception = InvalidPrefixException::alreadyExists($letter);
            $this->assertStringContainsString($letter, $exception->getMessage());
            $this->assertStringContainsString('已被其他第一層代理使用', $exception->getMessage());
        }
    }
}