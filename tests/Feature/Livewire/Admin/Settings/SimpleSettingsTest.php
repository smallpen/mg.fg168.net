<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Simple test to verify our test structure works
 */
class SimpleSettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_run_a_simple_test()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_access_database()
    {
        $this->assertDatabaseCount('users', 0);
    }
}