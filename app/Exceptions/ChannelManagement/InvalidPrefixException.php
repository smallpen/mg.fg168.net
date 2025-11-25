<?php

namespace App\Exceptions\ChannelManagement;

use Exception;

/**
 * 無效前置符號例外類別
 * 
 * 當前置符號格式不正確或已被使用時拋出此例外
 * 對應需求: 3.2, 3.7, 17.2
 */
class InvalidPrefixException extends Exception
{
    /**
     * 建立新的無效前置符號例外實例
     *
     * @param string $message 錯誤訊息
     * @param int $code 錯誤代碼
     * @param \Throwable|null $previous 前一個例外
     */
    public function __construct(
        string $message = '前置符號無效',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 建立前置符號格式錯誤例外
     *
     * @param string $prefix 無效的前置符號
     * @return static
     */
    public static function invalidFormat(string $prefix): static
    {
        $message = "前置符號「{$prefix}」格式不正確。前置符號必須是 a-z 的單一小寫字母。";
        return new static($message);
    }

    /**
     * 建立前置符號已存在例外
     *
     * @param string $prefix 已存在的前置符號
     * @return static
     */
    public static function alreadyExists(string $prefix): static
    {
        $message = "前置符號「{$prefix}」已被其他第一層代理使用，請選擇其他前置符號。";
        return new static($message);
    }

    /**
     * 建立前置符號為空例外
     *
     * @return static
     */
    public static function empty(): static
    {
        $message = "第一層代理必須設定前置符號。";
        return new static($message);
    }

    /**
     * 建立前置符號長度錯誤例外
     *
     * @param string $prefix 錯誤長度的前置符號
     * @return static
     */
    public static function invalidLength(string $prefix): static
    {
        $length = mb_strlen($prefix);
        $message = "前置符號「{$prefix}」長度不正確（{$length} 個字元）。前置符號必須是單一字母。";
        return new static($message);
    }
}