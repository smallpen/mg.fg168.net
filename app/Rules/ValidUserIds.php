<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

/**
 * 有效使用者 ID 驗證規則
 * 
 * 檢查使用者 ID 是否存在且有效
 */
class ValidUserIds implements ValidationRule
{
    protected int $maxCount;
    protected bool $checkActive;

    public function __construct(int $maxCount = 100, bool $checkActive = false)
    {
        $this->maxCount = $maxCount;
        $this->checkActive = $checkActive;
    }

    /**
     * 執行驗證規則
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('必須是陣列格式');
            return;
        }

        // 檢查數量限制
        if (count($value) > $this->maxCount) {
            $fail("最多只能選擇 {$this->maxCount} 個使用者");
            return;
        }

        // 檢查每個 ID 是否為有效整數
        foreach ($value as $userId) {
            if (!is_numeric($userId) || $userId <= 0) {
                $fail('包含無效的使用者 ID');
                return;
            }
        }

        // 移除重複的 ID
        $uniqueIds = array_unique($value);
        
        // 檢查使用者是否存在
        $query = User::whereIn('id', $uniqueIds);
        
        if ($this->checkActive) {
            $query->where('is_active', true);
        }
        
        $existingCount = $query->count();
        
        if ($existingCount !== count($uniqueIds)) {
            $fail('包含不存在的使用者 ID');
            return;
        }

        // 檢查是否包含當前使用者（某些操作不允許）
        if (in_array(auth()->id(), $uniqueIds)) {
            $fail('不能對自己執行此操作');
            return;
        }
    }
}