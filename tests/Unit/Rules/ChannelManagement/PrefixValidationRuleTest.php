<?php

namespace Tests\Unit\Rules\ChannelManagement;

use App\Models\Agent;
use App\Rules\ChannelManagement\PrefixValidationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 前置符號驗證規則測試
 * 
 * 測試 PrefixValidationRule 的各種驗證情況
 * 對應需求: 3.1, 3.2, 17.2
 */
class PrefixValidationRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試有效前置符號驗證通過
     */
    public function test_valid_prefix_passes_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failCalled = false;
        
        $rule->validate('prefix', 'a', function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
    }

    /**
     * 測試所有有效前置符號
     */
    public function test_all_valid_prefixes_pass_validation(): void
    {
        $rule = new PrefixValidationRule();
        $validPrefixes = range('a', 'z');
        
        foreach ($validPrefixes as $prefix) {
            $failCalled = false;
            
            $rule->validate('prefix', $prefix, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "前置符號 '{$prefix}' 應該通過驗證");
        }
    }

    /**
     * 測試空值驗證失敗
     */
    public function test_empty_value_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', '', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號不能為空。', $failMessage);
    }

    /**
     * 測試 null 值驗證失敗
     */
    public function test_null_value_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', null, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號不能為空。', $failMessage);
    }

    /**
     * 測試非字串值驗證失敗
     */
    public function test_non_string_value_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', 123, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號必須是字串。', $failMessage);
    }

    /**
     * 測試多字元前置符號驗證失敗
     */
    public function test_multiple_character_prefix_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', 'abc', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號必須是單一字母。', $failMessage);
    }

    /**
     * 測試大寫字母前置符號驗證失敗
     */
    public function test_uppercase_prefix_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', 'A', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號必須是 a-z 的小寫字母。', $failMessage);
    }

    /**
     * 測試數字前置符號驗證失敗
     */
    public function test_numeric_prefix_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', '1', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號必須是 a-z 的小寫字母。', $failMessage);
    }

    /**
     * 測試特殊字元前置符號驗證失敗
     */
    public function test_special_character_prefix_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $specialChars = ['@', '#', '$', '%', '&', '*', '!', '?'];
        
        foreach ($specialChars as $char) {
            $failMessage = '';
            
            $rule->validate('prefix', $char, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('前置符號必須是 a-z 的小寫字母。', $failMessage);
        }
    }

    /**
     * 測試已存在前置符號驗證失敗
     */
    public function test_existing_prefix_fails_validation(): void
    {
        // 建立一個使用前置符號 'a' 的第一層代理
        Agent::factory()->create([
            'prefix' => 'a',
            'level' => 1,
            'deleted_at' => null
        ]);

        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', 'a', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號「a」已被其他代理使用，請選擇其他前置符號。', $failMessage);
    }

    /**
     * 測試編輯模式排除自己的前置符號
     */
    public function test_edit_mode_excludes_own_prefix(): void
    {
        // 建立一個使用前置符號 'b' 的第一層代理
        $agent = Agent::factory()->create([
            'prefix' => 'b',
            'level' => 1,
            'deleted_at' => null
        ]);

        $rule = new PrefixValidationRule($agent->id);
        $failCalled = false;
        
        $rule->validate('prefix', 'b', function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
    }

    /**
     * 測試軟刪除代理的前置符號可以重複使用
     */
    public function test_soft_deleted_agent_prefix_can_be_reused(): void
    {
        // 建立一個軟刪除的代理
        Agent::create([
            'name' => '測試代理',
            'username' => 'testuser',
            'account' => 'ctestuser',
            'email' => 'test@example.com',
            'prefix' => 'c',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => 1,
            'deleted_at' => now()
        ]);

        $rule = new PrefixValidationRule();
        $failCalled = false;
        
        $rule->validate('prefix', 'c', function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
    }

    /**
     * 測試非第一層代理的前置符號不影響驗證
     */
    public function test_non_first_level_agent_prefix_does_not_affect_validation(): void
    {
        // 建立一個第二層代理，使用前置符號 'd'（這在實際中不應該發生，但測試邊界情況）
        Agent::factory()->create([
            'prefix' => 'd',
            'level' => 2,
            'deleted_at' => null
        ]);

        $rule = new PrefixValidationRule();
        $failCalled = false;
        
        $rule->validate('prefix', 'd', function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
    }

    /**
     * 測試取得可用前置符號列表
     */
    public function test_get_available_prefixes(): void
    {
        // 建立一些使用前置符號的代理
        Agent::factory()->create(['prefix' => 'a', 'level' => 1]);
        Agent::factory()->create(['prefix' => 'b', 'level' => 1]);
        Agent::factory()->create(['prefix' => 'c', 'level' => 1, 'deleted_at' => now()]); // 軟刪除

        $availablePrefixes = PrefixValidationRule::getAvailablePrefixes();
        
        $this->assertNotContains('a', $availablePrefixes);
        $this->assertNotContains('b', $availablePrefixes);
        $this->assertContains('c', $availablePrefixes); // 軟刪除的可以重複使用
        $this->assertContains('d', $availablePrefixes);
        $this->assertContains('z', $availablePrefixes);
    }

    /**
     * 測試取得可用前置符號列表（排除特定代理）
     */
    public function test_get_available_prefixes_with_exclusion(): void
    {
        $agent = Agent::create([
            'name' => '測試代理E',
            'username' => 'testusere',
            'account' => 'etestusere',
            'email' => 'teste@example.com',
            'prefix' => 'e',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => 1
        ]);
        
        Agent::create([
            'name' => '測試代理F',
            'username' => 'testuserf',
            'account' => 'ftestuserf',
            'email' => 'testf@example.com',
            'prefix' => 'f',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => 1
        ]);

        $availablePrefixes = PrefixValidationRule::getAvailablePrefixes($agent->id);
        
        $this->assertContains('e', $availablePrefixes); // 排除自己，所以可用
        $this->assertNotContains('f', $availablePrefixes);
    }

    /**
     * 測試檢查前置符號是否可用
     */
    public function test_is_available_method(): void
    {
        Agent::create([
            'name' => '測試代理G',
            'username' => 'testuserg',
            'account' => 'gtestuserg',
            'email' => 'testg@example.com',
            'prefix' => 'g',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => 1
        ]);
        
        $this->assertFalse(PrefixValidationRule::isAvailable('g'));
        $this->assertTrue(PrefixValidationRule::isAvailable('h'));
        $this->assertFalse(PrefixValidationRule::isAvailable('G')); // 大寫不可用
        $this->assertFalse(PrefixValidationRule::isAvailable('1')); // 數字不可用
    }

    /**
     * 測試檢查前置符號是否可用（排除特定代理）
     */
    public function test_is_available_method_with_exclusion(): void
    {
        $agent = Agent::factory()->create(['prefix' => 'i', 'level' => 1]);
        
        $this->assertFalse(PrefixValidationRule::isAvailable('i'));
        $this->assertTrue(PrefixValidationRule::isAvailable('i', $agent->id));
    }

    /**
     * 測試多位元組字元前置符號
     */
    public function test_multibyte_character_prefix_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', '中', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號必須是 a-z 的小寫字母。', $failMessage);
    }

    /**
     * 測試陣列值驗證失敗
     */
    public function test_array_value_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', ['a'], function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號必須是字串。', $failMessage);
    }

    /**
     * 測試物件值驗證失敗
     */
    public function test_object_value_fails_validation(): void
    {
        $rule = new PrefixValidationRule();
        $failMessage = '';
        
        $rule->validate('prefix', new \stdClass(), function ($message) use (&$failMessage) {
            $failMessage = $message;
        });
        
        $this->assertEquals('前置符號必須是字串。', $failMessage);
    }
}