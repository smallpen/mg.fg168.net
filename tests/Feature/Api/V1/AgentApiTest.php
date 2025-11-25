<?php

namespace Tests\Feature\Api\V1;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者並給予權限
        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'agents.view',
            'agents.create',
            'agents.edit',
            'agents.delete'
        ]);
        
        // 使用 Sanctum 認證
        Sanctum::actingAs($this->user, ['agents:read', 'agents:write']);
    }

    /** @test */
    public function it_can_list_agents()
    {
        // 建立測試代理
        Agent::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/channels/agents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'username',
                        'account',
                        'email',
                        'level',
                        'points' => [
                            'total_points',
                            'allocated_points',
                            'remaining_points',
                            'utilization_rate'
                        ],
                        'links'
                    ]
                ],
                'summary',
                'meta',
                'links'
            ]);
    }

    /** @test */
    public function it_can_create_first_level_agent()
    {
        $agentData = [
            'name' => '測試代理',
            'username' => 'test_agent',
            'email' => 'test@example.com',
            'phone' => '0912345678',
            'prefix' => 'a',
            'initial_points' => 10000,
            'is_active' => true,
            'notes' => '測試用代理'
        ];

        $response = $this->postJson('/api/v1/channels/agents', $agentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'account',
                    'level',
                    'points'
                ]
            ]);

        $this->assertDatabaseHas('agents', [
            'name' => '測試代理',
            'username' => 'test_agent',
            'account' => 'atest_agent',
            'level' => 1,
            'total_points' => 10000
        ]);
    }

    /** @test */
    public function it_can_create_sub_level_agent()
    {
        // 建立父代理
        $parentAgent = Agent::factory()->create([
            'level' => 1,
            'prefix' => 'a',
            'total_points' => 50000,
            'remaining_points' => 50000
        ]);

        $agentData = [
            'name' => '子代理',
            'username' => 'sub_agent',
            'email' => 'sub@example.com',
            'parent_id' => $parentAgent->id,
            'initial_points' => 5000,
            'is_active' => true
        ];

        $response = $this->postJson('/api/v1/channels/agents', $agentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('agents', [
            'name' => '子代理',
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'total_points' => 5000
        ]);
    }

    /** @test */
    public function it_can_show_agent_details()
    {
        $agent = Agent::factory()->create();

        $response = $this->getJson("/api/v1/channels/agents/{$agent->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'points',
                    'links'
                ]
            ]);
    }

    /** @test */
    public function it_can_update_agent()
    {
        $agent = Agent::factory()->create();

        $updateData = [
            'name' => '更新後的代理名稱',
            'phone' => '0987654321'
        ];

        $response = $this->putJson("/api/v1/channels/agents/{$agent->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'name' => '更新後的代理名稱',
            'phone' => '0987654321'
        ]);
    }

    /** @test */
    public function it_can_delete_agent_without_dependencies()
    {
        $agent = Agent::factory()->create();

        $response = $this->deleteJson("/api/v1/channels/agents/{$agent->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('agents', ['id' => $agent->id]);
    }

    /** @test */
    public function it_cannot_delete_agent_with_children()
    {
        $parentAgent = Agent::factory()->create();
        $childAgent = Agent::factory()->create(['parent_id' => $parentAgent->id]);

        $response = $this->deleteJson("/api/v1/channels/agents/{$parentAgent->id}");

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'message',
                'code'
            ]);

        $this->assertDatabaseHas('agents', ['id' => $parentAgent->id]);
    }

    /** @test */
    public function it_can_get_agent_hierarchy()
    {
        $agent = Agent::factory()->create();
        $childAgent = Agent::factory()->create(['parent_id' => $agent->id]);

        $response = $this->getJson("/api/v1/channels/agents/{$agent->id}/hierarchy");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'children',
                    'players'
                ],
                'meta'
            ]);
    }

    /** @test */
    public function it_can_get_agent_stats()
    {
        $agent = Agent::factory()->create();

        $response = $this->getJson("/api/v1/channels/agents/{$agent->id}/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'basic',
                    'hierarchy',
                    'points_distribution',
                    'recent_transactions'
                ],
                'meta'
            ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_agent()
    {
        $response = $this->postJson('/api/v1/channels/agents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'username',
                'email',
                'initial_points'
            ]);
    }

    /** @test */
    public function it_validates_unique_username_and_email()
    {
        $existingAgent = Agent::factory()->create();

        $agentData = [
            'name' => '測試代理',
            'username' => $existingAgent->username,
            'email' => $existingAgent->email,
            'prefix' => 'b',
            'initial_points' => 1000
        ];

        $response = $this->postJson('/api/v1/channels/agents', $agentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email']);
    }

    /** @test */
    public function it_validates_unique_prefix_for_first_level_agents()
    {
        Agent::factory()->create(['level' => 1, 'prefix' => 'a']);

        $agentData = [
            'name' => '測試代理',
            'username' => 'test_agent',
            'email' => 'test@example.com',
            'prefix' => 'a', // 重複的前置符號
            'initial_points' => 1000
        ];

        $response = $this->postJson('/api/v1/channels/agents', $agentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prefix']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/v1/channels/agents');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_proper_permissions()
    {
        // 建立沒有權限的使用者
        $userWithoutPermission = User::factory()->create();
        Sanctum::actingAs($userWithoutPermission);

        $response = $this->getJson('/api/v1/channels/agents');

        $response->assertStatus(403);
    }
}