<?php

namespace App\Services\LivewireFormReset\Contracts;

/**
 * 表單重置修復介面
 * 
 * 定義所有修復類別必須實作的方法
 */
interface FormResetFixInterface
{
    /**
     * 識別元件中的問題
     */
    public function identifyIssues(): array;

    /**
     * 應用標準修復
     */
    public function applyStandardFix(): bool;

    /**
     * 驗證修復結果
     */
    public function validateFix(): bool;

    /**
     * 產生修復報告
     */
    public function generateReport(): array;

    /**
     * 回滾修復變更
     */
    public function rollbackFix(): bool;

    /**
     * 取得修復進度
     */
    public function getProgress(): array;

    /**
     * 設定元件資訊
     */
    public function setComponentInfo(array $componentInfo): self;

    /**
     * 取得修復策略名稱
     */
    public function getStrategyName(): string;

    /**
     * 檢查是否支援此元件類型
     */
    public function supports(array $componentInfo): bool;
}