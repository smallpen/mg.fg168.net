<?php

namespace Tests\Feature\Admin\Layout;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 載入狀態管理簡化測試
 * 
 * 測試載入狀態管理系統的基本功能，不依賴視圖渲染
 */
class LoadingStateManagementSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
    }

    /** @test */
    public function API_ping_端點可以正常回應()
    {
        $response = $this->head('/api/ping');
        $response->assertStatus(200);

        $response = $this->get('/api/ping');
        $response->assertStatus(200);
    }

    /** @test */
    public function 載入狀態管理元件類別存在()
    {
        $this->assertTrue(class_exists(\App\Livewire\Admin\Layout\LoadingOverlay::class));
        $this->assertTrue(class_exists(\App\Livewire\Admin\Layout\PageLoadingIndicator::class));
        $this->assertTrue(class_exists(\App\Livewire\Admin\Layout\OperationFeedback::class));
        $this->assertTrue(class_exists(\App\Livewire\Admin\Layout\NetworkStatus::class));
        $this->assertTrue(class_exists(\App\Livewire\Admin\Layout\SkeletonLoader::class));
    }

    /** @test */
    public function 載入狀態管理元件視圖檔案存在()
    {
        $viewFiles = [
            'livewire.admin.layout.loading-overlay',
            'livewire.admin.layout.page-loading-indicator',
            'livewire.admin.layout.operation-feedback',
            'livewire.admin.layout.network-status',
            'livewire.admin.layout.skeleton-loader',
        ];

        foreach ($viewFiles as $view) {
            $this->assertTrue(view()->exists($view), "視圖檔案 {$view} 不存在");
        }
    }

    /** @test */
    public function LoadingOverlay_元件有正確的屬性()
    {
        $component = new \App\Livewire\Admin\Layout\LoadingOverlay();
        
        $this->assertObjectHasProperty('isLoading', $component);
        $this->assertObjectHasProperty('showProgress', $component);
        $this->assertObjectHasProperty('progress', $component);
        $this->assertObjectHasProperty('loadingText', $component);
        $this->assertObjectHasProperty('loadingType', $component);
        $this->assertObjectHasProperty('showOperationStatus', $component);
        $this->assertObjectHasProperty('operationMessage', $component);
        $this->assertObjectHasProperty('operationType', $component);
        $this->assertObjectHasProperty('isOnline', $component);
        $this->assertObjectHasProperty('showOfflineMessage', $component);
    }

    /** @test */
    public function PageLoadingIndicator_元件有正確的屬性()
    {
        $component = new \App\Livewire\Admin\Layout\PageLoadingIndicator();
        
        $this->assertObjectHasProperty('isLoading', $component);
        $this->assertObjectHasProperty('progress', $component);
        $this->assertObjectHasProperty('currentStep', $component);
        $this->assertObjectHasProperty('loadingSteps', $component);
        $this->assertObjectHasProperty('currentStepIndex', $component);
        $this->assertObjectHasProperty('startTime', $component);
        $this->assertObjectHasProperty('estimatedDuration', $component);
    }

    /** @test */
    public function OperationFeedback_元件有正確的屬性()
    {
        $component = new \App\Livewire\Admin\Layout\OperationFeedback();
        
        $this->assertObjectHasProperty('feedbacks', $component);
        $this->assertObjectHasProperty('maxFeedbacks', $component);
        $this->assertObjectHasProperty('defaultDuration', $component);
    }

    /** @test */
    public function NetworkStatus_元件有正確的屬性()
    {
        $component = new \App\Livewire\Admin\Layout\NetworkStatus();
        
        $this->assertObjectHasProperty('isOnline', $component);
        $this->assertObjectHasProperty('showOfflineMessage', $component);
        $this->assertObjectHasProperty('connectionType', $component);
        $this->assertObjectHasProperty('lastOnlineTime', $component);
        $this->assertObjectHasProperty('offlineDuration', $component);
        $this->assertObjectHasProperty('connectionQuality', $component);
        $this->assertObjectHasProperty('latency', $component);
        $this->assertObjectHasProperty('downloadSpeed', $component);
        $this->assertObjectHasProperty('offlineModeEnabled', $component);
        $this->assertObjectHasProperty('offlineQueue', $component);
        $this->assertObjectHasProperty('maxOfflineActions', $component);
        $this->assertObjectHasProperty('autoReconnect', $component);
        $this->assertObjectHasProperty('reconnectAttempts', $component);
        $this->assertObjectHasProperty('maxReconnectAttempts', $component);
        $this->assertObjectHasProperty('reconnectInterval', $component);
    }

    /** @test */
    public function SkeletonLoader_元件有正確的屬性()
    {
        $component = new \App\Livewire\Admin\Layout\SkeletonLoader();
        
        $this->assertObjectHasProperty('isLoading', $component);
        $this->assertObjectHasProperty('skeletonType', $component);
        $this->assertObjectHasProperty('skeletonConfig', $component);
        $this->assertObjectHasProperty('animationType', $component);
        $this->assertObjectHasProperty('animationDuration', $component);
        $this->assertObjectHasProperty('showProgress', $component);
        $this->assertObjectHasProperty('loadingProgress', $component);
    }

    /** @test */
    public function LoadingOverlay_元件方法可以正常執行()
    {
        $component = new \App\Livewire\Admin\Layout\LoadingOverlay();
        
        // 測試顯示載入
        $component->showLoading('測試載入中...', 'spinner');
        $this->assertTrue($component->isLoading);
        $this->assertEquals('測試載入中...', $component->loadingText);
        $this->assertEquals('spinner', $component->loadingType);
        
        // 測試隱藏載入
        $component->hideLoading();
        $this->assertFalse($component->isLoading);
        $this->assertEquals(0, $component->progress);
        
        // 測試更新進度
        $component->showLoading('載入中...', 'progress');
        $component->updateProgress(50, '載入中... 50%');
        $this->assertEquals(50, $component->progress);
        $this->assertEquals('載入中... 50%', $component->loadingText);
        
        // 測試顯示操作狀態
        $component->showOperationStatus('操作成功', 'success', 3000);
        $this->assertTrue($component->showOperationStatus);
        $this->assertEquals('操作成功', $component->operationMessage);
        $this->assertEquals('success', $component->operationType);
        
        // 測試設定網路狀態
        $component->setNetworkStatus(false);
        $this->assertFalse($component->isOnline);
        $this->assertTrue($component->showOfflineMessage);
    }

    /** @test */
    public function PageLoadingIndicator_元件方法可以正常執行()
    {
        $component = new \App\Livewire\Admin\Layout\PageLoadingIndicator();
        
        // 測試開始頁面載入
        $steps = [
            'initializing' => '初始化...',
            'loading' => '載入資料...',
            'rendering' => '渲染頁面...'
        ];
        $component->startPageLoading($steps, 3000);
        $this->assertTrue($component->isLoading);
        $this->assertEquals(0, $component->progress);
        $this->assertEquals('初始化...', $component->currentStep);
        $this->assertEquals(3000, $component->estimatedDuration);
        
        // 測試更新步驟
        $component->updateStep('loading', 50);
        $this->assertEquals(50, $component->progress);
        $this->assertEquals('載入資料...', $component->currentStep);
        
        // 測試完成載入
        $component->finishPageLoading();
        $this->assertEquals(100, $component->progress);
        $this->assertEquals('載入完成', $component->currentStep);
    }

    /** @test */
    public function OperationFeedback_元件方法可以正常執行()
    {
        $component = new \App\Livewire\Admin\Layout\OperationFeedback();
        
        // 測試新增回饋
        $feedbackId = $component->showSuccess('操作成功完成');
        $this->assertCount(1, $component->feedbacks);
        $this->assertNotEmpty($feedbackId);
        
        // 測試新增錯誤回饋
        $component->showError('發生錯誤');
        $this->assertCount(2, $component->feedbacks);
        
        // 測試移除回饋
        $component->removeFeedback($feedbackId);
        $this->assertCount(1, $component->feedbacks);
        
        // 測試清除所有回饋
        $component->clearAllFeedbacks();
        $this->assertCount(0, $component->feedbacks);
        
        // 測試載入進度
        $loadingId = $component->showLoading('處理中...');
        $this->assertCount(1, $component->feedbacks);
        
        $component->updateProgress($loadingId, 75, '處理中... 75%');
        $feedback = $component->feedbacks[0];
        $this->assertEquals(75, $feedback['progress']);
        
        $component->updateProgress($loadingId, 100);
        $feedback = $component->feedbacks[0];
        $this->assertEquals('success', $feedback['type']);
    }

    /** @test */
    public function NetworkStatus_元件方法可以正常執行()
    {
        $component = new \App\Livewire\Admin\Layout\NetworkStatus();
        
        // 測試設定網路狀態
        $component->setNetworkStatus(true, ['latency' => 50, 'type' => 'wifi']);
        $this->assertTrue($component->isOnline);
        $this->assertEquals(50, $component->latency);
        $this->assertEquals('wifi', $component->connectionType);
        $this->assertFalse($component->showOfflineMessage);
        
        $component->setNetworkStatus(false);
        $this->assertFalse($component->isOnline);
        $this->assertTrue($component->showOfflineMessage);
        
        // 測試離線佇列
        $component->offlineModeEnabled = true;
        $component->addOfflineAction([
            'type' => 'form_submit',
            'data' => ['name' => 'test']
        ]);
        $this->assertCount(1, $component->offlineQueue);
        
        $actionId = $component->offlineQueue[0]['id'];
        $component->removeOfflineAction($actionId);
        $this->assertCount(0, $component->offlineQueue);
        
        // 測試重連
        $component->reconnect();
        $this->assertEquals(1, $component->reconnectAttempts);
    }

    /** @test */
    public function SkeletonLoader_元件方法可以正常執行()
    {
        $component = new \App\Livewire\Admin\Layout\SkeletonLoader();
        
        // 測試顯示骨架屏
        $component->showSkeleton('dashboard', [], 'shimmer');
        $this->assertTrue($component->isLoading);
        $this->assertEquals('dashboard', $component->skeletonType);
        $this->assertEquals('shimmer', $component->animationType);
        $this->assertEquals(0, $component->loadingProgress);
        
        // 測試更新進度
        $component->updateProgress(60);
        $this->assertEquals(60, $component->loadingProgress);
        $this->assertTrue($component->showProgress);
        
        // 測試隱藏骨架屏
        $component->updateProgress(100);
        $this->assertFalse($component->isLoading);
    }

    /** @test */
    public function 載入狀態管理元件具有正確的計算屬性()
    {
        // 測試 LoadingOverlay 計算屬性
        $component = new \App\Livewire\Admin\Layout\LoadingOverlay();
        $component->showLoading('載入中...', 'spinner');
        
        $classes = $component->getLoadingClassesProperty();
        $this->assertStringContainsString('loading-overlay', $classes);
        $this->assertStringContainsString('active', $classes);
        $this->assertStringContainsString('loading-spinner', $classes);
        
        // 測試 SkeletonLoader 計算屬性
        $skeletonComponent = new \App\Livewire\Admin\Layout\SkeletonLoader();
        $skeletonComponent->showSkeleton('table', [], 'wave');
        
        $animationClasses = $skeletonComponent->getAnimationClassesProperty();
        $this->assertStringContainsString('skeleton-animation', $animationClasses);
        $this->assertStringContainsString('skeleton-wave', $animationClasses);
        
        $containerClasses = $skeletonComponent->getSkeletonContainerClassesProperty();
        $this->assertStringContainsString('skeleton-container', $containerClasses);
        $this->assertStringContainsString('skeleton-table', $containerClasses);
        $this->assertStringContainsString('loading', $containerClasses);
    }
}