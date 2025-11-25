<?php

namespace App\Rules\ChannelManagement;

use App\Models\Agent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 前置符號驗證規則
 * 
 * 驗證前置符號是否符合系統要求：
 * - 必須是 a-z 的單一小寫字母
 * - 不能與現有第一層代理的前置符號重複
 * 
 * 對應需求: 3.1, 3.2, 17.2
 */
class PrefixValidationRule implements ValidationRule
{
    /**
     * 要排除的代理 ID（用於編輯時排除自己）
     */
    private ?int $excludeAgentId;

    /**
     * 建立新的前置符號驗證規則實例
     *
     * @param int|null $excludeAgentId 要排除的代理 ID
     */
    public function __construct(?int $excludeAgentId = null)
    {
        $this->excludeAgentId = $excludeAgentId;
    }

    /**
     * 執行驗證規則
     *
     * @param string $attribute 屬性名稱
     * @param mixed $value 要驗證的值
     * @param Closure $fail 失敗回調函數
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 檢查是否為空
        if (empty($value)) {
            $fail('前置符號不能為空。');
            return;
        }

        // 檢查是否為字串
        if (!is_string($value)) {
            $fail('前置符號必須是字串。');
            return;
        }

        // 檢查長度是否為 1
        if (mb_strlen($value) !== 1) {
            $fail('前置符號必須是單一字母。');
            return;
        }

        // 檢查是否為小寫字母 a-z
        if (!preg_match('/^[a-z]$/', $value)) {
            $fail('前置符號必須是 a-z 的小寫字母。');
            return;
        }

        // 檢查是否已被其他第一層代理使用
        $query = Agent::where('prefix', $value)
            ->where('level', 1)
            ->whereNull('deleted_at');

        // 如果是編輯模式，排除當前代理
        if ($this->excludeAgentId) {
            $query->where('id', '!=', $this->excludeAgentId);
        }

        if ($query->exists()) {
            $fail("前置符號「{$value}」已被其他代理使用，請選擇其他前置符號。");
            return;
        }
    }

    /**
     * 取得可用的前置符號列表
     *
     * @param int|null $excludeAgentId 要排除的代理 ID
     * @return array
     */
    public static function getAvailablePrefixes(?int $excludeAgentId = null): array
    {
        $allPrefixes = range('a', 'z');
        
        $query = Agent::where('level', 1)
            ->whereNotNull('prefix')
            ->whereNull('deleted_at');

        if ($excludeAgentId) {
            $query->where('id', '!=', $excludeAgentId);
        }

        $usedPrefixes = $query->pluck('prefix')->toArray();
        
        return array_diff($allPrefixes, $usedPrefixes);
    }

    /**
     * 檢查前置符號是否可用
     *
     * @param string $prefix 前置符號
     * @param int|null $excludeAgentId 要排除的代理 ID
     * @return bool
     */
    public static function isAvailable(string $prefix, ?int $excludeAgentId = null): bool
    {
        // 檢查格式
        if (!preg_match('/^[a-z]$/', $prefix)) {
            return false;
        }

        // 檢查是否已被使用
        $query = Agent::where('prefix', $prefix)
            ->where('level', 1)
            ->whereNull('deleted_at');

        if ($excludeAgentId) {
            $query->where('id', '!=', $excludeAgentId);
        }

        return !$query->exists();
    }
}