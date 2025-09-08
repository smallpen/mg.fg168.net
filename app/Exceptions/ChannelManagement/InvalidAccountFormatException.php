<?php

namespace App\Exceptions\ChannelManagement;

use Exception;

/**
 * 無效帳號格式例外類別
 * 
 * 當帳號格式不符合系統要求時拋出此例外
 * 對應需求: 3.7, 7.4, 17.1, 17.2
 */
class InvalidAccountFormatException extends Exception
{
    /**
     * 建立新的無效帳號格式例外實例
     *
     * @param string $message 錯誤訊息
     * @param int $code 錯誤代碼
     * @param \Throwable|null $previous 前一個例外
     */
    public function __construct(
        string $message = '帳號格式不正確',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 建立帳號已存在例外
     *
     * @param string $account 已存在的帳號
     * @return static
     */
    public static function accountExists(string $account): static
    {
        $message = "帳號「{$account}」已存在，請使用其他帳號名稱。";
        return new static($message);
    }

    /**
     * 建立帳號格式不正確例外
     *
     * @param string $account 格式不正確的帳號
     * @return static
     */
    public static function invalidFormat(string $account): static
    {
        $message = "帳號「{$account}」格式不正確。帳號只能包含英文字母、數字和底線，且長度不能超過 50 個字元。";
        return new static($message);
    }

    /**
     * 建立帳號長度不正確例外
     *
     * @param string $account 長度不正確的帳號
     * @param int $maxLength 最大長度限制
     * @return static
     */
    public static function tooLong(string $account, int $maxLength = 50): static
    {
        $length = mb_strlen($account);
        $message = "帳號「{$account}」長度過長（{$length} 個字元），最多只能 {$maxLength} 個字元。";
        return new static($message);
    }

    /**
     * 建立帳號為空例外
     *
     * @return static
     */
    public static function empty(): static
    {
        $message = "帳號不能為空。";
        return new static($message);
    }

    /**
     * 建立前置符號與帳號組合衝突例外
     *
     * @param string $prefix 前置符號
     * @param string $username 原始帳號
     * @param string $fullAccount 完整帳號
     * @return static
     */
    public static function prefixConflict(string $prefix, string $username, string $fullAccount): static
    {
        $message = "前置符號「{$prefix}」與帳號「{$username}」組合後的完整帳號「{$fullAccount}」已存在衝突。";
        return new static($message);
    }
}