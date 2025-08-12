<?php

namespace Tests\Feature\Admin\Layout;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 載入狀態管理測試
 * 
 * 測試載入狀態管理系統的各個元件功能
 */
class LoadingStateManagementTest extends TestCase
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
    public function 可以顯示載入覆蓋層()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.loading-overlay')
            ->call('showLoading', '測試載入中...', 'spinner')
            ->assertSet('isLoading', true)
            ->assertSet('loadingText', '測試載入中...')
            ->assertSet('loadingType', 'spinner')
            ->assertSee('測試載入中...');
    }

    /** @test */
    public function 可以隱藏載入覆蓋層()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.loading-overlay')
            ->call('showLoading', '載入中...', 'spinner')
            ->assertSet('isLoading', true)
            ->call('hideLoading')
            ->assertSet('isLoading', false)
            ->assertSet('progress', 0);
    }

    /** @test */
    public function 可以更新載入進度()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.loading-overlay')
            ->call('showLoading', '載入中...', 'progress')
            ->assertSet('showProgress', true)
            ->call('updateProgress', 50, '載入中... 50%')
            ->assertSet('progress', 50)
            ->assertSet('loadingText', '載入中... 50%')
            ->call('updateProgress', 100)
            ->assertSet('isLoading', false); // 100% 時自動隱藏
    }

    /** @test */
    public function 可以顯示操作狀態訊息()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.loading-overlay')
            ->call('showOperationStatus', '操作成功', 'success', 3000)
            ->assertSet('showOperationStatus', true)
            ->assertSet('operationMessage', '操作成功')
            ->assertSet('operationType', 'success')
            ->assertSee('操作成功');
    }

    /** @test */
    public function 可以設定網路狀態()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.loading-overlay')
            ->call('setNetworkStatus', false)
            ->assertSet('isOnline', false)
            ->assertSet('showOfflineMessage', true)
            ->call('setNetworkStatus', true)
            ->assertSet('isOnline', true)
            ->assertSet('showOfflineMessage', false);
    }

    /** @test */
    public function 頁面載入指示器可以正常工作()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.page-loading-indicator')
            ->call('startPageLoading', [
                'initializing' => '初始化...',
                'loading' => '載入資料...',
                'rendering' => '渲染頁面...'
            ], 3000)
            ->assertSet('isLoading', true)
            ->assertSet('progress', 0)
            ->assertSet('currentStep', '初始化...')
            ->call('updateStep', 'loading', 50)
            ->assertSet('progress', 50)
            ->assertSet('currentStep', '載入資料...')
            ->call('finishPageLoading')
            ->assertSet('progress', 100)
            ->assertSet('currentStep', '載入完成');
    }

    /** @test */
    public function 操作回饋系統可以新增和移除回饋()
    {
        $this->actingAs($this->user);

        $component = Livewire::test('admin.layout.operation-feedback')
            ->call('showSuccess', '操作成功完成')
            ->assertCount('feedbacks', 1);

        $feedbackId = $component->get('feedbacks')[0]['id'];

        $component
            ->call('showError', '發生錯誤')
            ->assertCount('feedbacks', 2)
            ->call('removeFeedback', $feedbackId)
            ->assertCount('feedbacks', 1);
    }

    /** @test */
    public function 操作回饋系統可以更新載入進度()
    {
        $this->actingAs($this->user);

        $component = Livewire::test('admin.layout.operation-feedback')
            ->call('showLoading', '處理中...');

        $feedbackId = $component->get('feedbacks')[0]['id'];

        $component
            ->call('updateProgress', $feedbackId, 75, '處理中... 75%')
            ->call('updateProgress', $feedbackId, 100)
            ->assertCount('feedbacks', 1);

        // 檢查是否轉換為成功狀態
        $feedback = $component->get('feedbacks')[0];
        $this->assertEquals('success', $feedback['type']);
    }

    /** @test */
    public function 網路狀態元件可以檢測連線狀態()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.network-status')
            ->call('setNetworkStatus', true, ['latency' => 50, 'type' => 'wifi'])
            ->assertSet('isOnline', true)
            ->assertSet('latency', 50)
            ->assertSet('connectionType', 'wifi')
            ->call('setNetworkStatus', false)
            ->assertSet('isOnline', false)
            ->assertSet('showOfflineMessage', true);
    }

    /** @test */
    public function 網路狀態元件可以管理離線佇列()
    {
        $this->actingAs($this->user);

        $component = Livewire::test('admin.layout.network-status')
            ->set('offlineModeEnabled', true)
            ->call('addOfflineAction', [
                'type' => 'form_submit',
                'data' => ['name' => 'test']
            ])
            ->assertCount('offlineQueue', 1);

        $actionId = $component->get('offlineQueue')[0]['id'];

        $component
            ->call('removeOfflineAction', $actionId)
            ->assertCount('offlineQueue', 0);
    }

    /** @test */
    public function 骨架屏載入器可以顯示不同類型的骨架屏()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.skeleton-loader')
            ->call('showSkeleton', 'dashboard', [], 'shimmer')
            ->assertSet('isLoading', true)
            ->assertSet('skeletonType', 'dashboard')
            ->assertSet('animationType', 'shimmer')
            ->call('hideSkeleton')
            ->assertSet('loadingProgress', 100);
    }

    /** @test */
    public function 骨架屏載入器可以更新載入進度()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.skeleton-loader')
            ->call('showSkeleton', 'table')
            ->call('updateProgress', 60)
            ->assertSet('loadingProgress', 60)
            ->assertSet('showProgress', true)
            ->call('updateProgress', 100)
            ->assertSet('isLoading', false); // 100% 時自動隱藏
    }

    /** @test */
    public function 載入狀態管理元件可以監聽事件()
    {
        $this->actingAs($this->user);

        // 測試 LoadingOverlay 事件監聽
        Livewire::test('admin.layout.loading-overlay')
            ->dispatch('start-loading', text: '事件觸發載入', type: 'progress')
            ->assertSet('isLoading', true)
            ->assertSet('loadingText', '事件觸發載入')
            ->assertSet('loadingType', 'progress')
            ->dispatch('stop-loading')
            ->assertSet('isLoading', false);

        // 測試 OperationFeedback 事件監聽
        Livewire::test('admin.layout.operation-feedback')
            ->dispatch('operation-success', message: '事件觸發成功訊息')
            ->assertCount('feedbacks', 1);

        // 測試 NetworkStatus 事件監聽
        Livewire::test('admin.layout.network-status')
            ->dispatch('network-status-update', isOnline: false)
            ->assertSet('isOnline', false);
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
    public function 載入狀態管理元件已整合到主佈局()
    {
        $this->actingAs($this->user);

        $component = Livewire::test('admin.layout.admin-layout');
        
        // 檢查主佈局是否包含載入狀態管理元件
        $html = $component->render();
        
        $this->assertStringContainsString('livewire:admin.layout.page-loading-indicator', $html);
        $this->assertStringContainsString('livewire:admin.layout.loading-overlay', $html);
        $this->assertStringContainsString('livewire:admin.layout.operation-feedback', $html);
        $this->assertStringContainsString('livewire:admin.layout.network-status', $html);
        $this->assertStringContainsString('livewire:admin.layout.skeleton-loader', $html);
    }

    /** @test */
    public function 載入狀態管理元件具有正確的CSS類別()
    {
        $this->actingAs($this->user);

        // 測試 LoadingOverlay CSS 類別
        $component = Livewire::test('admin.layout.loading-overlay')
            ->call('showLoading', '載入中...', 'spinner');
        
        $classes = $component->get('loadingClasses');
        $this->assertStringContainsString('loading-overlay', $classes);
        $this->assertStringContainsString('active', $classes);
        $this->assertStringContainsString('loading-spinner', $classes);

        // 測試 OperationFeedback CSS 類別
        $component = Livewire::test('admin.layout.operation-feedback')
            ->call('showSuccess', '成功訊息');
        
        $feedback = $component->get('feedbacks')[0];
        $classes = $component->call('getFeedbackClasses', $feedback);
        $this->assertStringContainsString('feedback-success', $classes);
    }

    /** @test */
    public function 載入狀態管理元件可以處理長時間操作()
    {
        $this->actingAs($this->user);

        Livewire::test('admin.layout.loading-overlay')
            ->call('showLoading', '長時間操作中...', 'progress')
            ->set('estimatedTime', 60) // 60 秒
            ->assertSet('estimatedTime', 60)
            ->call('updateProgress', 25, '處理中... 25%')
            ->assertSet('progress', 25);

        // 測試預估剩餘時間計算
        $component = Livewire::test('admin.layout.loading-overlay')
            ->set('estimatedTime', 100)
            ->set('progress', 50)
            ->set('elapsedTime', 50);
        
        $remainingTime = $component->get('estimatedRemainingTime');
        $this->assertGreaterThan(0, $remainingTime);
    }
}