<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityList;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Repositories\ActivityRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\DisablesPermissionSecurity;
use Mockery;

/**
 * 簡單的活動記錄測試
 */
class SimpleActivityTest extends TestCase
{
    use RefreshDatabase, DisablesPermissionSecurity;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立基本的管理員使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_can_create_basic_test()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_render_activity_list_component_simple()
    {
        // Mock the ActivityRepository
        $mockRepository = Mockery::mock(ActivityRepository::class);
        $mockRepository->shouldReceive('getPaginatedActivities')
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50));
        
        $this->app->instance(ActivityRepository::class, $mockRepository);

        $component = Livewire::test(ActivityList::class);
        $component->assertStatus(200);
    }
}