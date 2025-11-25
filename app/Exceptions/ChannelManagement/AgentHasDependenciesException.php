<?php

namespace App\Exceptions\ChannelManagement;

use Exception;

/**
 * 代理有依賴關係例外類別
 * 
 * 當嘗試刪除有下層代理或玩家的代理時拋出此例外
 * 對應需求: 1.8, 2.9, 17.3
 */
class AgentHasDependenciesException extends Exception
{
    /**
     * 建立新的代理有依賴關係例外實例
     *
     * @param string $message 錯誤訊息
     * @param int $code 錯誤代碼
     * @param \Throwable|null $previous 前一個例外
     */
    public function __construct(
        string $message = '無法刪除代理，因為該代理還有下層代理或玩家',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 建立有下層代理的例外
     *
     * @param string $agentName 代理名稱
     * @param int $childrenCount 下層代理數量
     * @return static
     */
    public static function hasChildren(string $agentName, int $childrenCount): static
    {
        $message = "無法刪除代理「{$agentName}」，因為該代理還有 {$childrenCount} 個下層代理。請先處理或重新指派這些下層代理。";
        return new static($message);
    }

    /**
     * 建立有隸屬玩家的例外
     *
     * @param string $agentName 代理名稱
     * @param int $playersCount 玩家數量
     * @return static
     */
    public static function hasPlayers(string $agentName, int $playersCount): static
    {
        $message = "無法刪除代理「{$agentName}」，因為該代理還有 {$playersCount} 個隸屬玩家。請先處理或重新指派這些玩家。";
        return new static($message);
    }

    /**
     * 建立同時有下層代理和玩家的例外
     *
     * @param string $agentName 代理名稱
     * @param int $childrenCount 下層代理數量
     * @param int $playersCount 玩家數量
     * @return static
     */
    public static function hasBoth(string $agentName, int $childrenCount, int $playersCount): static
    {
        $message = "無法刪除代理「{$agentName}」，因為該代理還有 {$childrenCount} 個下層代理和 {$playersCount} 個隸屬玩家。請先處理或重新指派這些關聯。";
        return new static($message);
    }
}