<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_open_mock_console_and_reference_data_pages(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.mock-fbr-console'))
            ->assertOk()
            ->assertSee('Mock FBR Request / Response Console');

        $this->actingAs($admin)
            ->get(route('reference-data.index'))
            ->assertOk()
            ->assertSee('Import HS / Custom Duty Data');
    }
}
