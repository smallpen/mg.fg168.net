<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\LoadingStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Admin\Layout\LoadingOverlay;
use App\Livewire\Admin\Layout\PageLoadingIndicator;
use App\Livewire\Admin\Layout\OperationFeedback;
use App\Livewire\Admin\Layout\NetworkStatus;
use App\Livewire\Admin\Layout\SkeletonLoader;

class LoadingStateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected LoadingStateService $loadingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadingService = app(LoadingStateService::class);
    }

    /** @test */
    public function loading_overlay_component_can_be_rendered()
    {
        Livewire::test(LoadingOverlay::class)
            ->assertStatus(200)
            ->assertSee('loading-overlay');
    }

    /** @test */
    public function loading_overlay_can_show_and_hide_loading_state()
    {
        Livewire::test(LoadingOverlay::class)
            ->call('showLoading', '測試載入中...', 'spinner')
            ->assertSet('isLoading', true)
            ->assertSet('loadingText', '測試載入中...')
            ->assertSet('loadingType', 'spinner')
            ->call('hideLoading')
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function loading_overlay_can_update_progress()
    {
        Livewire::test(LoadingOverlay::class)
            ->call('showLoading', '載入中...', 'progress')
            ->call('updateProgress', 50, '載入進度 50%')
            ->assertSet('progress', 50)
            ->assertSet('loadingText', '載入進度 50%')
            ->call('updateProgress', 100)
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function page_loading_indicator_can_be_rendered()
    {
        Livewire::test(PageLoadingIndicator::class)
            ->assertStatus(200)
            ->assertSee('page-loading-indicator');
    }

    /** @test */
    public function page_loading_indicator_can_start_and_finish_loading()
    {
        Livewire::test(PageLoadingIndicator::class)
            ->call('startPageLoading')
            ->assertSet('isLoading', true)
            ->assertSet('progress', 0)
            ->call('finishPageLoading')
            ->assertSet('progress', 100);
    }

    /** @test */
    public function operation_feedback_component_can_be_rendered()
    {
        Livewire::test(OperationFeedback::class)
            ->assertStatus(200)
            ->assertSee('operation-feedback-container');
    }

    /** @test */
    public function operation_feedback_can_add_and_remove_feedback()
    {
        Livewire::test(OperationFeedback::class)
            ->call('showSuccess', '操作成功')
            ->assertCount('feedbacks', 1)
            ->call('showError', '操作失敗')
            ->assertCount('feedbacks', 2)
            ->call('clearAllFeedbacks')
            ->assertCount('feedbacks', 0);
    }

    /** @test */
    public function network_status_component_can_be_rendered()
    {
        Livewire::test(NetworkStatus::class)
            ->assertStatus(200)
            ->assertSee('network-status-container');
    }

    /** @test */
    public function network_status_can_handle_online_offline_states()
    {
        Livewire::test(NetworkStatus::class)
            ->assertSet('isOnline', true)
            ->call('setNetworkStatus', false)
            ->assertSet('isOnline', false)
            ->assertSet('showOfflineMessage', true)
            ->call('setNetworkStatus', true)
            ->assertSet('isOnline', true)
            ->assertSet('showOfflineMessage', false);
    }

    /** @test */
    public function skeleton_loader_component_can_be_rendered()
    {
        Livewire::test(SkeletonLoader::class)
            ->assertStatus(200)
            ->assertSee('skeleton-container');
    }

    /** @test */
    public function skeleton_loader_can_show_different_types()
    {
        Livewire::test(SkeletonLoader::class)
            ->call('showSkeleton', 'dashboard', [], 'pulse')
            ->assertSet('isLoading', true)
            ->assertSet('skeletonType', 'dashboard')
            ->assertSet('animationType', 'pulse')
            ->call('hideSkeleton')
            ->assertSet('loadingProgress', 100);
    }

    /** @test */
    public function loading_state_service_can_manage_loading_operations()
    {
        $operationId = $this->loadingService->generateOperationId('test');
        
        // 開始載入
        $this->loadingService->startLoading($operationId, 'test', [
            'message' => '測試載入中...'
        ]);
        
        $this->assertTrue($this->loadingService->isLoading($operationId));
        
        $state = $this->loadingService->getLoadingState($operationId);
        $this->assertEquals('loading', $state['status']);
        $this->assertEquals('測試載入中...', $state['message']);
        
        // 更新進度
        $this->loadingService->updateProgress($operationId, 50, '進度 50%');
        
        $state = $this->loadingService->getLoadingState($operationId);
        $this->assertEquals(50, $state['progress']);
        $this->assertEquals('進度 50%', $state['message']);
        
        // 完成載入
        $this->loadingService->finishLoading($operationId, '載入完成');
        
        $state = $this->loadingService->getLoadingState($operationId);
        $this->assertEquals('completed', $state['status']);
        $this->assertEquals(100, $state['progress']);
        
        $this->assertFalse($this->loadingService->isLoading($operationId));
    }

    /** @test */
    public function loading_state_service_can_handle_steps()
    {
        $operationId = $this->loadingService->generateOperationId('steps');
        
        $steps = [
            '初始化...',
            '載入資料...',
            '渲染介面...',
            '完成'
        ];
        
        $this->loadingService->startLoading($operationId, 'steps');
        $this->loadingService->setLoadingSteps($operationId, $steps);
        
        // 更新到第二步
        $this->loadingService->updateCurrentStep($operationId, 1);
        
        $state = $this->loadingService->getLoadingState($operationId);
        $this->assertEquals(1, $state['current_step']);
        $this->assertEquals(50, $state['progress']); // 2/4 * 100
        $this->assertEquals('載入資料...', $state['message']);
    }

    /** @test */
    public function loading_state_service_can_get_statistics()
    {
        // 建立多個載入操作
        $op1 = $this->loadingService->generateOperationId('test1');
        $op2 = $this->loadingService->generateOperationId('test2');
        
        $this->loadingService->startLoading($op1, 'dashboard');
        $this->loadingService->startLoading($op2, 'table');
        
        $this->loadingService->updateProgress($op1, 30);
        $this->loadingService->updateProgress($op2, 70);
        
        $stats = $this->loadingService->getLoadingStats();
        
        $this->assertEquals(2, $stats['total_active']);
        $this->assertEquals(1, $stats['by_type']['dashboard']);
        $this->assertEquals(1, $stats['by_type']['table']);
        $this->assertEquals(50, $stats['average_progress']); // (30 + 70) / 2
    }

    /** @test */
    public function loading_state_service_can_handle_batch_operations()
    {
        $operations = [
            ['type' => 'dashboard', 'prefix' => 'dash'],
            ['type' => 'table', 'prefix' => 'tbl'],
            ['type' => 'form', 'prefix' => 'frm']
        ];
        
        $operationIds = $this->loadingService->startBatchLoading($operations);
        
        $this->assertCount(3, $operationIds);
        
        foreach ($operationIds as $id) {
            $this->assertTrue($this->loadingService->isLoading($id));
        }
        
        // 批次更新進度
        $progressUpdates = [
            $operationIds[0] => 25,
            $operationIds[1] => ['progress' => 50, 'message' => '表格載入中...'],
            $operationIds[2] => 75
        ];
        
        $this->loadingService->updateBatchProgress($progressUpdates);
        
        $this->assertEquals(25, $this->loadingService->getLoadingState($operationIds[0])['progress']);
        $this->assertEquals(50, $this->loadingService->getLoadingState($operationIds[1])['progress']);
        $this->assertEquals('表格載入中...', $this->loadingService->getLoadingState($operationIds[1])['message']);
        $this->assertEquals(75, $this->loadingService->getLoadingState($operationIds[2])['progress']);
    }

    /** @test */
    public function components_can_handle_events()
    {
        // 測試 LoadingOverlay 事件處理
        Livewire::test(LoadingOverlay::class)
            ->dispatch('start-loading', text: '事件載入中...', type: 'progress')
            ->assertSet('isLoading', true)
            ->assertSet('loadingText', '事件載入中...')
            ->dispatch('stop-loading')
            ->assertSet('isLoading', false);

        // 測試 OperationFeedback 事件處理
        Livewire::test(OperationFeedback::class)
            ->dispatch('operation-success', message: '操作成功完成')
            ->assertCount('feedbacks', 1)
            ->dispatch('operation-error', message: '操作失敗', actions: [])
            ->assertCount('feedbacks', 2);

        // 測試 NetworkStatus 事件處理
        Livewire::test(NetworkStatus::class)
            ->dispatch('network-status-update', isOnline: false, details: [])
            ->assertSet('isOnline', false)
            ->assertSet('showOfflineMessage', true);
    }
}