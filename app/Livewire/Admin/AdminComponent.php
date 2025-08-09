<?php

namespace App\Livewire\Admin;

use Livewire\Component;

/**
 * 管理後台基礎 Livewire 元件
 * 
 * 所有管理後台 Livewire 元件的基礎類別
 */
abstract class AdminComponent extends Component
{
    /**
     * 元件掛載時執行
     * 
     * 初始化基本設定（權限檢查由中介軟體處理）
     */
    public function mount()
    {
        // 權限檢查已由中介軟體處理，這裡可以進行其他初始化工作
    }

    /**
     * 取得當前使用者
     * 
     * @return \App\Models\User|null
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * 檢查使用者是否擁有特定權限
     * 
     * @param string $permission
     * @return bool
     */
    protected function hasPermission(string $permission): bool
    {
        return $this->getCurrentUser()?->hasPermission($permission) ?? false;
    }

    /**
     * 顯示成功訊息
     * 
     * @param string $message
     */
    protected function showSuccess(string $message): void
    {
        session()->flash('success', $message);
    }

    /**
     * 顯示錯誤訊息
     * 
     * @param string $message
     */
    protected function showError(string $message): void
    {
        session()->flash('error', $message);
    }
}