<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class TestComponent extends Component
{
    public $message = '測試元件載入成功';
    
    /**
     * 元件建構函數 - 修復 ArgumentCountError
     */
    public function __construct()
    {
        // 呼叫父類建構函數
        parent::__construct();
    }
    
    // 基本測試屬性
    public $testSearch = '';
    public $testFilter = 'all';
    
    // 模擬 UserList 的所有篩選屬性
    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public function testAction()
    {
        \Log::info('🧪 TestComponent - testAction 被呼叫了！', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
        ]);
        
        $this->message = '測試動作執行成功！時間：' . now()->format('H:i:s');
        
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '測試動作執行成功！'
        ]);
    }

    public function testResetFilters()
    {
        \Log::info('🧪 TestComponent - testResetFilters 被呼叫了！', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
            'before' => [
                'testSearch' => $this->testSearch,
                'testFilter' => $this->testFilter,
            ]
        ]);
        
        $this->testSearch = '';
        $this->testFilter = 'all';
        $this->message = '篩選已重置！時間：' . now()->format('H:i:s');
        
        \Log::info('🧪 TestComponent - testResetFilters 完成！', [
            'after' => [
                'testSearch' => $this->testSearch,
                'testFilter' => $this->testFilter,
            ]
        ]);
    }
    
    // 模擬 UserList 的 resetFilters 方法
    public function resetFilters()
    {
        try {
            \Log::info('🔄 TestComponent - resetFilters (UserList 風格) 被呼叫了！', [
                'timestamp' => now()->toISOString(),
                'user' => auth()->user()->username ?? 'unknown',
                'before' => [
                    'search' => $this->search,
                    'roleFilter' => $this->roleFilter,
                    'statusFilter' => $this->statusFilter,
                    'sortBy' => $this->sortBy,
                    'sortDirection' => $this->sortDirection,
                    'perPage' => $this->perPage
                ]
            ]);
            
            // 重置所有篩選屬性
            $this->search = '';
            $this->roleFilter = '';
            $this->statusFilter = '';
            $this->sortBy = 'created_at';
            $this->sortDirection = 'desc';
            $this->perPage = 10;
            
            $this->message = 'UserList 風格篩選已重置！時間：' . now()->format('H:i:s');
            
            \Log::info('🔄 TestComponent - resetFilters (UserList 風格) 完成！', [
                'after' => [
                    'search' => $this->search,
                    'roleFilter' => $this->roleFilter,
                    'statusFilter' => $this->statusFilter,
                    'sortBy' => $this->sortBy,
                    'sortDirection' => $this->sortDirection,
                    'perPage' => $this->perPage
                ]
            ]);
            
            // 發送前端刷新事件
            $this->dispatch('refresh-form-inputs');
            
        } catch (\Exception $e) {
            \Log::error('🔄 TestComponent - resetFilters 發生錯誤！', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->message = '重置時發生錯誤：' . $e->getMessage();
        }
    }
    
    // 測試複雜的篩選重置（包含條件邏輯）
    public function complexResetFilters()
    {
        \Log::info('🔄 TestComponent - complexResetFilters 被呼叫了！', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
        ]);
        
        try {
            // 模擬複雜的重置邏輯
            if ($this->search || $this->roleFilter || $this->statusFilter) {
                $this->search = '';
                $this->roleFilter = '';
                $this->statusFilter = '';
                
                // 模擬一些可能導致問題的操作
                $this->sortBy = 'created_at';
                $this->sortDirection = 'desc';
                
                if ($this->perPage != 10) {
                    $this->perPage = 10;
                }
                
                $this->message = '複雜篩選重置成功！時間：' . now()->format('H:i:s');
                
                \Log::info('🔄 TestComponent - complexResetFilters 成功完成！');
                
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => '複雜篩選重置成功！'
                ]);
            } else {
                $this->message = '沒有需要重置的篩選！';
                \Log::info('🔄 TestComponent - complexResetFilters 沒有需要重置的篩選');
            }
        } catch (\Exception $e) {
            \Log::error('🔄 TestComponent - complexResetFilters 發生錯誤！', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->message = '重置時發生錯誤：' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.admin.test-component');
    }
}