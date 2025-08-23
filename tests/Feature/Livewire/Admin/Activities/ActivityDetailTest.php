<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityDetail;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 活動詳情元件測試
 */
class ActivityDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者
        $this->user = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'is_active' => true,
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_activity_detail_component()
    {
        $component = Livewire::test(ActivityDetail::class);
        
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_load_activity_details()
    {
        // 建立測試活動記錄
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'module' => 'auth',
            'user_id' => $this->user->id,
            'properties' => [
                'login_method' => 'username_password',
                'success' => true,
            ],
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('activity.id', $activity->id)
                  ->assertSet('activity.type', 'user_login')
                  ->assertSet('activity.description', '使用者登入系統')
                  ->assertSet('showModal', true);
    }

    /** @test */
    public function it_can_toggle_raw_data_display()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'properties' => ['test' => 'data'],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('showRawData', false);
        
        $component->call('toggleRawData');
        $component->assertSet('showRawData', true);
        
        $component->call('toggleRawData');
        $component->assertSet('showRawData', false);
    }

    /** @test */
    public function it_can_flag_activity_as_suspicious()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 3,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('isSuspicious', false);
        
        $component->call('flagAsSuspicious');
        $component->assertSet('isSuspicious', true);
        
        // 檢查是否建立了標記記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'activity_flagged',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_add_note_to_activity()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->set('note', '這是一個測試註記');
        $component->call('addNote', '這是一個測試註記');
        
        // 檢查是否建立了註記記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'activity_note_added',
            'user_id' => $this->user->id,
        ]);
        
        $component->assertSet('note', '')
                  ->assertSet('showNoteForm', false);
    }

    /** @test */
    public function it_can_export_activity_detail()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'properties' => ['test' => 'data'],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->call('exportDetail');
        
        // 檢查是否建立了匯出記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'export_activity_detail',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_close_modal()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('showModal', true);
        
        $component->call('closeModal');
        
        $component->assertSet('showModal', false)
                  ->assertSet('activity', null);
    }

    /** @test */
    public function it_calculates_security_risk_level_correctly()
    {
        $lowRiskActivity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $highRiskActivity = Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->user->id,
            'result' => 'failed',
            'risk_level' => 8,
        ]);

        // 測試低風險活動
        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $lowRiskActivity->id);
        $component->assertSet('securityRiskLevel', 'low');

        // 測試高風險活動
        $component->call('loadActivity', $highRiskActivity->id);
        $component->assertSet('securityRiskLevel', 'high');
    }

    /** @test */
    public function it_formats_properties_correctly()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'properties' => [
                'username' => 'testuser',
                'login_method' => 'password',
                'remember_me' => true,
                'session_data' => ['key' => 'value'],
            ],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $formattedData = $component->get('formattedData');
        
        $this->assertIsArray($formattedData);
        $this->assertNotEmpty($formattedData);
        
        // 檢查格式化的資料結構
        foreach ($formattedData as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('raw_key', $item);
            $this->assertArrayHasKey('raw_value', $item);
            $this->assertArrayHasKey('type', $item);
        }
    }

    /** @test */
    public function it_handles_activity_not_found()
    {
        $component = Livewire::test(ActivityDetail::class);
        
        $component->call('loadActivity', 99999);
        
        $component->assertSet('activity', null);
    }

    /** @test */
    public function it_can_navigate_between_activities()
    {
        // 建立多個活動記錄
        $activity1 = Activity::create([
            'type' => 'user_login',
            'description' => '第一個活動',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
            'created_at' => now()->subMinutes(10),
        ]);

        $activity2 = Activity::create([
            'type' => 'user_logout',
            'description' => '第二個活動',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        $activity3 = Activity::create([
            'type' => 'user_login',
            'description' => '第三個活動',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
            'created_at' => now(),
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity2->id);
        
        // 檢查導航 ID 是否正確設定
        $component->assertSet('previousActivityId', $activity1->id);
        $component->assertSet('nextActivityId', $activity3->id);
    }
}