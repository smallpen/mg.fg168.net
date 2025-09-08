<?php

namespace Tests\Unit\Rules\ChannelManagement;

use App\Rules\ChannelManagement\PointsValidationRule;
use PHPUnit\Framework\TestCase;

/**
 * 簡化的點數驗證規則測試
 * 
 * 只測試基本驗證邏輯，不涉及資料庫操作
 * 對應需求: 11.3, 11.4, 12.3, 12.5, 14.2, 15.3, 15.5
 */
class SimplePointsValidationRuleTest extends TestCase
{
    /**
     * 測試有效點數驗證通過
     */
    public function test_valid_points_pass_validation(): void
    {
        $rule = new PointsValidationRule();
        $failCalled = false;
        
        $rule->validate('points', 100.50, function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
    }

    /**
     * 測試各種有效點數格式
     */
    public function test_various_valid_points_formats(): void
    {
        $rule = new PointsValidationRule();
        $validPoints = [
            1,
            1.0,
            1.5,
            10.25,
            100.99,
            1000,
            9999.99,
            0.01,
            0.1,
            0.99
        ];
        
        foreach ($validPoints as $points) {
            $failCalled = false;
            
            $rule->validate('points', $points, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "點數 '{$points}' 應該通過驗證");
        }
    }

    /**
     * 測試字串數字驗證通過
     */
    public function test_string_numbers_pass_validation(): void
    {
        $rule = new PointsValidationRule();
        $stringNumbers = ['100', '100.50', '0.01', '9999.99'];
        
        foreach ($stringNumbers as $points) {
            $failCalled = false;
            
            $rule->validate('points', $points, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "字串點數 '{$points}' 應該通過驗證");
        }
    }

    /**
     * 測試非數字值驗證失敗
     */
    public function test_non_numeric_values_fail_validation(): void
    {
        $rule = new PointsValidationRule();
        $nonNumericValues = [
            'abc',
            'not_a_number',
            '100abc',
            'abc100',
            '10.5.5',
            '10,50',
            '10 50',
            '',
            null,
            [],
            new \stdClass(),
            true,
            false
        ];

        foreach ($nonNumericValues as $value) {
            $failMessage = '';
            
            $rule->validate('points', $value, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('點數必須是數字。', $failMessage, "非數字值應該驗證失敗");
        }
    }

    /**
     * 測試零和負數驗證失敗
     */
    public function test_zero_and_negative_values_fail_validation(): void
    {
        $rule = new PointsValidationRule();
        $invalidValues = [0, 0.0, -1, -0.01, -100, -999.99, '-1', '-100.50'];

        foreach ($invalidValues as $value) {
            $failMessage = '';
            
            $rule->validate('points', $value, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('點數必須大於 0。', $failMessage, "值 '{$value}' 應該驗證失敗");
        }
    }

    /**
     * 測試過多小數位數驗證失敗
     */
    public function test_too_many_decimal_places_fail_validation(): void
    {
        $rule = new PointsValidationRule();
        $tooManyDecimals = [
            100.123,
            1.999,
            0.001,
            99.9999,
            1.2345,
            10.12345
        ];

        foreach ($tooManyDecimals as $value) {
            $failMessage = '';
            
            $rule->validate('points', $value, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('點數最多只能有 2 位小數。', $failMessage, "值 '{$value}' 應該因小數位數過多而驗證失敗");
        }
    }

    /**
     * 測試正確的小數位數驗證通過
     */
    public function test_correct_decimal_places_pass_validation(): void
    {
        $rule = new PointsValidationRule();
        $correctDecimals = [
            100.12,
            1.99,
            0.01,
            99.99,
            1.23,
            10.10,
            5.00,
            1.0,
            100
        ];

        foreach ($correctDecimals as $value) {
            $failCalled = false;
            
            $rule->validate('points', $value, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "值 '{$value}' 應該通過小數位數驗證");
        }
    }

    /**
     * 測試邊界值：最小有效點數
     */
    public function test_minimum_valid_points(): void
    {
        $rule = new PointsValidationRule();
        $failCalled = false;
        
        $rule->validate('points', 0.01, function ($message) use (&$failCalled) {
            $failCalled = true;
        });
        
        $this->assertFalse($failCalled);
    }

    /**
     * 測試邊界值：接近零但大於零
     */
    public function test_near_zero_but_positive(): void
    {
        $rule = new PointsValidationRule();
        $nearZeroValues = [0.01, 0.02, 0.99];
        
        foreach ($nearZeroValues as $value) {
            $failCalled = false;
            
            $rule->validate('points', $value, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "接近零的正值 '{$value}' 應該通過驗證");
        }
    }

    /**
     * 測試大數值
     */
    public function test_large_numbers(): void
    {
        $rule = new PointsValidationRule();
        $largeNumbers = [
            999999.99,
            1000000,
            9999999.99
        ];
        
        foreach ($largeNumbers as $value) {
            $failCalled = false;
            
            $rule->validate('points', $value, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "大數值 '{$value}' 應該通過驗證");
        }
    }

    /**
     * 測試科學記號
     */
    public function test_scientific_notation(): void
    {
        $rule = new PointsValidationRule();
        $scientificNumbers = [
            1e2,    // 100
            1.5e2,  // 150
            1e-1,   // 0.1
            1.5e-1, // 0.15
            1e0     // 1
        ];
        
        foreach ($scientificNumbers as $value) {
            $failCalled = false;
            
            $rule->validate('points', $value, function ($message) use (&$failCalled) {
                $failCalled = true;
            });
            
            $this->assertFalse($failCalled, "科學記號 '{$value}' 應該通過驗證");
        }
    }

    /**
     * 測試無窮大和 NaN
     */
    public function test_infinity_and_nan(): void
    {
        $rule = new PointsValidationRule();
        $specialValues = [INF, -INF, NAN];
        
        foreach ($specialValues as $value) {
            $failMessage = '';
            
            $rule->validate('points', $value, function ($message) use (&$failMessage) {
                $failMessage = $message;
            });
            
            $this->assertEquals('點數必須是有效的數字。', $failMessage, "特殊值應該驗證失敗");
        }
    }
}