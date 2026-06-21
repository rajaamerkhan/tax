<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientUserManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function client_admin_can_create_and_update_users_for_own_client(): void
    {
        $admin = $this->clientUser(UserRole::Admin);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Staff Accountant',
            'email' => 'staff@example.test',
            'phone' => '+92-300-1234567',
            'role' => UserRole::Accountant->value,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('users.index'));

        $staff = User::query()->where('email', 'staff@example.test')->firstOrFail();

        $this->assertSame($admin->client_id, $staff->client_id);
        $this->assertSame(UserRole::Accountant, $staff->role);
        $this->assertTrue(Hash::check('secure-password', $staff->password));

        $this->actingAs($admin)->put(route('users.update', $staff), [
            'name' => 'Staff Viewer',
            'email' => 'viewer-staff@example.test',
            'phone' => '+92-300-7654321',
            'role' => UserRole::Viewer->value,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.edit', $staff));

        $staff->refresh();
        $this->assertSame('Staff Viewer', $staff->name);
        $this->assertSame('viewer-staff@example.test', $staff->email);
        $this->assertSame(UserRole::Viewer, $staff->role);
    }

    #[Test]
    public function client_admin_cannot_access_another_clients_users(): void
    {
        $admin = $this->clientUser(UserRole::Admin);
        $otherUser = $this->clientUser(UserRole::Accountant);

        $this->actingAs($admin)
            ->get(route('users.edit', $otherUser))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee($otherUser->email);
    }

    #[Test]
    public function user_email_must_be_unique_across_all_clients(): void
    {
        $admin = $this->clientUser(UserRole::Admin);
        $otherClient = Client::factory()->create();

        User::factory()->create([
            'client_id' => $otherClient->id,
            'email' => 'shared@example.test',
            'role' => UserRole::Viewer,
        ]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate Email',
            'email' => 'shared@example.test',
            'phone' => null,
            'role' => UserRole::Viewer->value,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('email');
    }

    #[Test]
    public function user_email_is_normalized_before_uniqueness_validation(): void
    {
        $admin = $this->clientUser(UserRole::Admin);
        $otherClient = Client::factory()->create();

        User::factory()->create([
            'client_id' => $otherClient->id,
            'email' => 'mixed@example.test',
            'role' => UserRole::Viewer,
        ]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate Mixed Email',
            'email' => '  MIXED@EXAMPLE.TEST  ',
            'phone' => null,
            'role' => UserRole::Viewer->value,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('email');
    }

    #[Test]
    public function accountant_and_viewer_cannot_manage_users(): void
    {
        $accountant = $this->clientUser(UserRole::Accountant);
        $viewer = $this->clientUser(UserRole::Viewer, $accountant->client_id);

        $this->actingAs($accountant)->get(route('users.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('users.index'))->assertForbidden();
    }

    #[Test]
    public function owner_in_managed_client_mode_can_manage_selected_client_users(): void
    {
        $owner = User::factory()->create([
            'client_id' => null,
            'role' => UserRole::Owner,
        ]);
        $client = Client::factory()->create(['name' => 'Managed Client']);
        $clientAdmin = $this->clientUser(UserRole::Admin, $client->id);

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($clientAdmin->email);

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->post(route('users.store'), [
                'name' => 'Managed Viewer',
                'email' => 'managed-viewer@example.test',
                'phone' => null,
                'role' => UserRole::Viewer->value,
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'client_id' => $client->id,
            'email' => 'managed-viewer@example.test',
            'role' => UserRole::Viewer->value,
        ]);
    }

    #[Test]
    public function owner_without_managed_client_cannot_access_client_users(): void
    {
        $owner = User::factory()->create([
            'client_id' => null,
            'role' => UserRole::Owner,
        ]);

        $this->actingAs($owner)->get(route('users.index'))->assertForbidden();
    }

    #[Test]
    public function last_client_admin_cannot_be_deleted_or_demoted(): void
    {
        $admin = $this->clientUser(UserRole::Admin);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertUnprocessable();

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'role' => UserRole::Viewer->value,
            'password' => '',
            'password_confirmation' => '',
        ])->assertUnprocessable();
    }

    private function clientUser(UserRole $role, ?int $clientId = null): User
    {
        return User::factory()->create([
            'client_id' => $clientId ?? Client::factory(),
            'role' => $role,
        ]);
    }
}
