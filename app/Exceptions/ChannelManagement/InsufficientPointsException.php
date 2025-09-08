<?php

namespace App\Exceptions\ChannelManagement;

use Exception;

/**
 * 點數不足例外類別
 * 
 * 當代理或玩家的點數不足以執行特定操作時拋出此例外
 * 對應需求: 11.4, 12.5, 14.2, 15.5
 */
class InsufficientPointsException extends Exception
{
    /**
     * 建立新的點數不足例外實例
     *
     * @param string $message 錯誤訊息
     * @param int $code 錯誤代碼
     * @param \Throwable|null $previous 前一個例外
     */
    public function __construct(
        string $message = '點數不足，無法執行此操作',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 建立代理點數不足例外
     *
     * @param float $required 需要的點數
     * @param float $available 可用的點數
     * @param string $agentName 代理名稱
     * @return static
     */
    public static function forAgent(float $required, float $available, string $agentName): static
    {
        $message = "代理「{$agentName}」點數不足。需要 {$required} 點，但只有 {$available} 點可用。";
        return new static($message);
    }

    /**
     * 建立玩家點數不足例外
     *
     * @param float $required 需要的點數
     * @param float $available 可用的點數
     * @param string $playerName 玩家名稱
     * @return static
     */
    public static function forPlayer(float $required, float $available, string $playerName): static
    {
        $message = "玩家「{$playerName}」點數不足。需要 {$required} 點，但只有 {$available} 點可用。";
        return new static($message);
    }

    /**
     * 建立點數回收不足例外
     *
     * @param float $requested 請求回收的點數
     * @param float $available 可回收的點數
     * @param string $targetName 目標名稱
     * @return static
     */
    public static function forRecovery(float $requested, float $available, string $targetName): static
    {
        $message = "無法從「{$targetName}」回收 {$requested} 點，最多只能回收 {$available} 點。";
        return new static($message);
    }
}