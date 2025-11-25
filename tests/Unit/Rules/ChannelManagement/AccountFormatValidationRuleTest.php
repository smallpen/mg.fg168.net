<?php

namespace Tests\Unit\Rules\ChannelManagement;

use App\Rules\ChannelManagement\AccountFormatValidationRule;
use PHPUnit\Framework\TestCase;

/**
 * 帳號格式驗證規則測試
 * 
 * 測試 AccountFormatValidationRule 的各種驗證情況
 * 對應需求: 1.3, 6.3, 7.4, 17.1
 */
class AccountFormatValidationRuleTest extends TestCase
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
        $rule = new AccountFormatValidationRule();
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
        $rule = new AccountFormatValidationRule('agent', null, 10); // 設定最大長度為 10
        $failMessage = '';
        
        $longAccount = str_repeat('a', 15); // 15 個字元
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
        $rule = new AccountFormatValidationRule();
        $invalidAccounts = [
            'user@domain.com',
            'user-name',
            'user.name',
            'user name',
            'user#123',
            'user$money',
            'user%test',
            'user&co',
            'user*star',
            'user+plus',
            'user=equal',
            'user!exclaim',
            'user?question',
            'user<less',
            'user>greater',
            'user[bracket',
            'user]bracket',
            'user{brace',
            'user}brace',
            'user|pipe',
            'user\\backslash',
            'user/slash',
            'user:colon',
            'user;semicolon',
            'user"quote',
            "user'apostrophe",
            'user`backtick',
            'user~tilde'
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
        $rule = new AccountFormatValidationRule();
        $multibyteAccounts = [
            '中文帳號',
            'ユーザー',
            '사용자',
            'пользователь',
            'utilisateur',
            'usuario',
            'utente',
            'benutzer',
            'gebruiker',
            'användare'
        ];

        foreach ($multibyteAccounts as $account) {
            $failMessage = '';
            
            $rule->validate('username', $account, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('帳號只能包含英文字母、數字和底線。', $failMessage, "多位元組帳號 '{$account}' 應該驗證失敗");
        }
    }

    /**
     * 測試陣列值驗證失敗
     */
    public function test_array_value_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule();
        $failMessage = '';
        
        $rule->validate('username', ['user'], function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號必須是字串。', $failMessage);
    }

    /**
     * 測試物件值驗證失敗
     */
    public function test_object_value_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule();
        $failMessage = '';
        
        $rule->validate('username', new \stdClass(), function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號必須是字串。', $failMessage);
    }

    /**
     * 測試布林值驗證失敗
     */
    public function test_boolean_value_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule();
        $failMessage = '';
        
        $rule->validate('username', true, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號必須是字串。', $failMessage);
    }

    /**
     * 測試浮點數值驗證失敗
     */
    public function test_float_value_fails_validation(): void
    {
        $rule = new AccountFormatValidationRule();
        $failMessage = '';
        
        $rule->validate('username', 123.45, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號必須是字串。', $failMessage);
    }

    /**
     * 測試靜態方法 isUsernameAvailable 格式檢查
     */
    public function test_is_username_available_format_check(): void
    {
        // 測試有效格式
        $this->assertTrue(AccountFormatValidationRule::isUsernameAvailable('valid_user_123', 'agent'));
        
        // 測試無效格式
        $this->assertFalse(AccountFormatValidationRule::isUsernameAvailable('invalid@user', 'agent'));
        $this->assertFalse(AccountFormatValidationRule::isUsernameAvailable('user-name', 'agent'));
        $this->assertFalse(AccountFormatValidationRule::isUsernameAvailable('user.name', 'agent'));
    }

    /**
     * 測試靜態方法 isFullAccountAvailable 格式檢查
     */
    public function test_is_full_account_available_format_check(): void
    {
        // 這個方法主要檢查資料庫，但我們可以測試它不會拋出例外
        $result = AccountFormatValidationRule::isFullAccountAvailable('avaliduser', 'agent');
        $this->assertTrue(is_bool($result));
    }

    /**
     * 測試邊界情況：剛好達到最大長度
     */
    public function test_boundary_max_length(): void
    {
        $rule = new AccountFormatValidationRule('agent', null, 5);
        $failCalled = false;
        
        // 剛好 5 個字元，應該通過
        $rule->validate('username', 'user1', function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
        
        // 6 個字元，應該失敗
        $failMessage = '';
        $rule->validate('username', 'user12', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('帳號長度不能超過 5 個字元。', $failMessage);
    }

    /**
     * 測試單一字元帳號
     */
    public function test_single_character_account(): void
    {
        $rule = new AccountFormatValidationRule();
        $singleCharAccounts = ['a', 'Z', '1', '_'];
        
        foreach ($singleCharAccounts as $account) {
            $failCalled = false;
            
            $rule->validate('username', $account, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "單一字元帳號 '{$account}' 應該通過驗證");
        }
    }
}