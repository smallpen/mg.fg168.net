<?php

namespace Tests\Unit\Exceptions\ChannelManagement;

use App\Exceptions\ChannelManagement\InvalidAccountFormatException;
use PHPUnit\Framework\TestCase;

/**
 * 無效帳號格式例外類別測試
 * 
 * 測試 InvalidAccountFormatException 的各種建立方法和錯誤訊息
 * 對應需求: 3.7, 7.4, 17.1, 17.2
 */
class InvalidAccountFormatExceptionTest extends TestCase
{
    /**
     * 測試預設例外建立
     */
    public function test_default_exception_creation(): void
    {
        $exception = new InvalidAccountFormatException();
        
        $this->assertEquals('帳號格式不正確', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義訊息例外建立
     */
    public function test_custom_message_exception_creation(): void
    {
        $customMessage = '自定義帳號格式錯誤訊息';
        $exception = new InvalidAccountFormatException($customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義代碼例外建立
     */
    public function test_custom_code_exception_creation(): void
    {
        $customCode = 400;
        $exception = new InvalidAccountFormatException('測試訊息', $customCode);
        
        $this->assertEquals($customCode, $exception->getCode());
    }

    /**
     * 測試帳號已存在例外建立
     */
    public function test_account_exists_exception_creation(): void
    {
        $existingAccount = 'existing_user';
        $exception = InvalidAccountFormatException::accountExists($existingAccount);
        
        $expectedMessage = "帳號「{$existingAccount}」已存在，請使用其他帳號名稱。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試帳號格式不正確例外建立
     */
    public function test_invalid_format_exception_creation(): void
    {
        $invalidAccount = 'invalid@account';
        $exception = InvalidAccountFormatException::invalidFormat($invalidAccount);
        
        $expectedMessage = "帳號「{$invalidAccount}」格式不正確。帳號只能包含英文字母、數字和底線，且長度不能超過 50 個字元。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試帳號長度過長例外建立
     */
    public function test_too_long_exception_creation(): void
    {
        $longAccount = str_repeat('a', 60); // 60 個字元
        $maxLength = 50;
        $exception = InvalidAccountFormatException::tooLong($longAccount, $maxLength);
        
        $expectedMessage = "帳號「{$longAccount}」長度過長（60 個字元），最多只能 {$maxLength} 個字元。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試預設最大長度
     */
    public function test_default_max_length(): void
    {
        $longAccount = str_repeat('b', 55);
        $exception = InvalidAccountFormatException::tooLong($longAccount);
        
        $this->assertStringContainsString('最多只能 50 個字元', $exception->getMessage());
    }

    /**
     * 測試帳號為空例外建立
     */
    public function test_empty_exception_creation(): void
    {
        $exception = InvalidAccountFormatException::empty();
        
        $expectedMessage = "帳號不能為空。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試前置符號衝突例外建立
     */
    public function test_prefix_conflict_exception_creation(): void
    {
        $prefix = 'a';
        $username = 'testuser';
        $fullAccount = 'atestuser';
        
        $exception = InvalidAccountFormatException::prefixConflict($prefix, $username, $fullAccount);
        
        $expectedMessage = "前置符號「{$prefix}」與帳號「{$username}」組合後的完整帳號「{$fullAccount}」已存在衝突。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試例外是否繼承自 Exception
     */
    public function test_exception_inheritance(): void
    {
        $exception = new InvalidAccountFormatException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * 測試例外鏈
     */
    public function test_exception_chaining(): void
    {
        $previousException = new \RuntimeException('前一個例外');
        $exception = new InvalidAccountFormatException('帳號格式錯誤', 422, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    /**
     * 測試特殊字元帳號格式錯誤
     */
    public function test_special_character_accounts(): void
    {
        $specialAccounts = [
            'user@domain.com',
            'user-name',
            'user.name',
            'user name',
            'user#123',
            'user$money'
        ];

        foreach ($specialAccounts as $account) {
            $exception = InvalidAccountFormatException::invalidFormat($account);
            $this->assertStringContainsString($account, $exception->getMessage());
            $this->assertStringContainsString('格式不正確', $exception->getMessage());
        }
    }

    /**
     * 測試多位元組字元帳號
     */
    public function test_multibyte_character_accounts(): void
    {
        $multibyteAccount = '中文帳號';
        $exception = InvalidAccountFormatException::invalidFormat($multibyteAccount);
        
        $this->assertStringContainsString($multibyteAccount, $exception->getMessage());
    }

    /**
     * 測試邊界長度帳號
     */
    public function test_boundary_length_accounts(): void
    {
        // 測試剛好 50 個字元的帳號
        $fiftyCharAccount = str_repeat('a', 50);
        $exception = InvalidAccountFormatException::tooLong($fiftyCharAccount, 49); // 設定限制為 49
        
        $this->assertStringContainsString('50 個字元', $exception->getMessage());
        $this->assertStringContainsString('最多只能 49 個字元', $exception->getMessage());
    }

    /**
     * 測試空字串前置符號衝突
     */
    public function test_empty_prefix_conflict(): void
    {
        $exception = InvalidAccountFormatException::prefixConflict('', 'user', 'user');
        
        $this->assertStringContainsString('前置符號「」', $exception->getMessage());
    }

    /**
     * 測試複雜前置符號衝突情況
     */
    public function test_complex_prefix_conflict(): void
    {
        $prefix = 'z';
        $username = 'complex_user_123';
        $fullAccount = 'zcomplex_user_123';
        
        $exception = InvalidAccountFormatException::prefixConflict($prefix, $username, $fullAccount);
        
        $this->assertStringContainsString($prefix, $exception->getMessage());
        $this->assertStringContainsString($username, $exception->getMessage());
        $this->assertStringContainsString($fullAccount, $exception->getMessage());
    }

    /**
     * 測試數字帳號格式
     */
    public function test_numeric_accounts(): void
    {
        $numericAccount = '123456';
        $exception = InvalidAccountFormatException::accountExists($numericAccount);
        
        $this->assertStringContainsString($numericAccount, $exception->getMessage());
    }

    /**
     * 測試底線帳號格式
     */
    public function test_underscore_accounts(): void
    {
        $underscoreAccount = 'user_name_123';
        $exception = InvalidAccountFormatException::accountExists($underscoreAccount);
        
        $this->assertStringContainsString($underscoreAccount, $exception->getMessage());
    }
}