<?php

namespace Tests\Unit\Rules\ChannelManagement;

use App\Rules\ChannelManagement\AccountFormatValidationRule;
use PHPUnit\Framework\TestCase;

/**
 * 簡化的帳號格式驗證規則測試
 * 
 * 只測試格式驗證，不涉及資料庫操作
 * 對應需求: 1.3, 6.3, 7.4, 17.1
 */
class SimpleAccountFormatValidationRuleTest extends TestCase
{
    /**
     * 測試有效帳號格式驗證通過
     */
    public function test_valid_account_format_passes_validation(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 50, false, null, true);
        $failCalled = false;
        
        $rule->validate('username', 'validuser123', function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
    }

    /**
     * 測試各種有效帳號格式
     */
    public function test_various_valid_account_formats(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 50, false, null, true);
        $validAccounts = [
            'user123',
            'test_user',
            'User_Name_123',
            'a',
            'A',
            '123',
            '_test',
            'test_',
            'user_123_test'
        ];
        
        foreach ($validAccounts as $account) {
            $failCalled = false;
            
            $rule->validate('username', $account, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "帳號 '{$account}' 應該通過驗證");
        }
    }

    /**
     * 測試空值驗證失敗
     */
    public function test_empty_value_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 50, false, null, true);
        $failMessage = '';
        
        $rule->validate('username', '', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號不能為空。', $failMessage);
    }

    /**
     * 測試 null 值驗證失敗
     */
    public function test_null_value_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 50, false, null, true);
        $failMessage = '';
        
        $rule->validate('username', null, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號不能為空。', $failMessage);
    }

    /**
     * 測試非字串值驗證失敗
     */
    public function test_non_string_value_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 50, false, null, true);
        $failMessage = '';
        
        $rule->validate('username', 123, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號必須是字串。', $failMessage);
    }

    /**
     * 測試超長帳號驗證失敗
     */
    public function test_too_long_account_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 10, false, null, true);
        $failMessage = '';
        
        $longAccount = str_repeat('a', 15);
        $rule->validate('username', $longAccount, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號長度不能超過 10 個字元。', $failMessage);
    }

    /**
     * 測試無效字元帳號驗證失敗
     */
    public function test_invalid_characters_fail_validation(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 50, false, null, true);
        $invalidAccounts = [
            'user@domain.com',
            'user-name',
            'user.name',
            'user name',
            'user#123',
            'user$money'
        ];

        foreach ($invalidAccounts as $account) {
            $failMessage = '';
            
            $rule->validate('username', $account, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('帳號只能包含英文字母、數字和底線。', $failMessage, "帳號 '{$account}' 應該驗證失敗");
        }
    }

    /**
     * 測試多位元組字元帳號驗證失敗
     */
    public function test_multibyte_characters_fail_validation(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 50, false, null, true);
        $multibyteAccounts = ['中文帳號', 'ユーザー', '사용자'];

        foreach ($multibyteAccounts as $account) {
            $failMessage = '';
            
            $rule->validate('username', $account, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('帳號只能包含英文字母、數字和底線。', $failMessage, "多位元組帳號 '{$account}' 應該驗證失敗");
        }
    }
}