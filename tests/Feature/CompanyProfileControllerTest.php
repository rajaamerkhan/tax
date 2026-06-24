<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function fbr_token_column_can_store_encrypted_tokens(): void
    {
        $this->assertSame('text', Schema::getColumnType('company_profiles', 'fbr_token'));
        $this->assertSame('text', Schema::getColumnType('company_profiles', 'fbr_sandbox_token'));
        $this->assertSame('text', Schema::getColumnType('company_profiles', 'fbr_production_token'));
    }

    #[Test]
    public function it_stores_a_36_character_fbr_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $token = str_repeat('a', 36);

        $response = $this->actingAs($user)->put(route('company.update'), [
            'name' => 'Demo Seller',
            'fbr_registration_number' => '3520212345671',
            'ntn_cnic' => '1234567-8',
            'address' => 'Lahore, Pakistan',
            'phone' => '+92-300-0000000',
            'email' => 'info@example.com',
            'fbr_sandbox_token' => $token,
            'fbr_environment' => 'sandbox',
            'fbr_business_nature' => 'distributor',
        ]);

        $response->assertRedirect();
        $this->assertSame($token, CompanyProfile::first()->fbr_sandbox_token);
        $this->assertSame('distributor', CompanyProfile::first()->fbr_business_nature);
    }

    #[Test]
    public function it_keeps_existing_fbr_token_when_token_field_is_blank(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $existingToken = 'existing-secret-token';

        CompanyProfile::create([
            'name' => 'Old Seller',
            'ntn_cnic' => '1234567-8',
            'fbr_sandbox_token' => $existingToken,
            'fbr_production_token' => 'production-secret-token',
            'fbr_environment' => 'sandbox',
        ]);

        $response = $this->actingAs($user)->put(route('company.update'), [
            'name' => 'Updated Seller',
            'fbr_registration_number' => '3520212345671',
            'ntn_cnic' => '1234567-8',
            'province_id' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'fbr_sandbox_token' => '',
            'fbr_production_token' => '',
            'fbr_environment' => 'production',
            'fbr_business_nature' => 'distributor',
        ]);

        $response->assertRedirect();

        $company = CompanyProfile::first();
        $this->assertSame('Updated Seller', $company->name);
        $this->assertSame($existingToken, $company->fbr_sandbox_token);
        $this->assertSame('production-secret-token', $company->fbr_production_token);
        $this->assertSame('production', $company->fbr_environment->value);
        $this->assertSame('distributor', $company->fbr_business_nature);
    }

    #[Test]
    public function company_profile_accepts_cnic_registration_number_and_ntn_separately(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->put(route('company.update'), [
            'name' => 'M/S Saif & Fahad Trader',
            'fbr_registration_number' => 'F518891',
            'ntn_cnic' => 'F518891-5',
            'province_id' => null,
            'address' => 'Lahore, Pakistan',
            'phone' => null,
            'email' => null,
            'fbr_sandbox_token' => 'sandbox-token',
            'fbr_environment' => 'sandbox',
            'fbr_business_nature' => 'distributor',
        ]);

        $response->assertRedirect();
        $company = CompanyProfile::first();
        $this->assertSame('F518891', $company->fbr_registration_number);
        $this->assertSame('F518891-5', $company->ntn_cnic);
    }

    #[Test]
    public function company_profile_normalizes_registration_number_for_api_usage(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($user)->put(route('company.update'), [
            'name' => 'M/S Saif & Fahad Trader',
            'fbr_registration_number' => 'f518891-5',
            'ntn_cnic' => 'F518891-5',
            'province_id' => null,
            'address' => 'Lahore, Pakistan',
            'phone' => null,
            'email' => null,
            'fbr_sandbox_token' => 'sandbox-token',
            'fbr_environment' => 'sandbox',
            'fbr_business_nature' => 'distributor',
        ]);

        $response->assertRedirect();
        $company = CompanyProfile::first();
        $this->assertSame('F518891', $company->fbr_registration_number);
        $this->assertSame('F518891-5', $company->ntn_cnic);
    }

    #[Test]
    public function owner_can_update_a_specific_clients_company_profile(): void
    {
        $owner = User::factory()->create([
            'client_id' => null,
            'role' => UserRole::Owner,
        ]);
        $client = Client::factory()->create(['name' => 'Client One']);
        $otherClient = Client::factory()->create(['name' => 'Client Two']);
        $token = str_repeat('b', 36);

        $this->actingAs($owner)
            ->get(route('owner.clients.company.edit', $client))
            ->assertOk()
            ->assertSee('Client One Company Profile');

        $this->actingAs($owner)->put(route('owner.clients.company.update', $client), [
            'name' => 'Client One Seller',
            'fbr_registration_number' => '3520212345671',
            'ntn_cnic' => '1234567-8',
            'province_id' => null,
            'address' => 'Client One Address',
            'phone' => '+92-300-0000000',
            'email' => 'seller-one@example.test',
            'fbr_sandbox_token' => $token,
            'fbr_environment' => 'sandbox',
            'fbr_business_nature' => 'distributor',
        ])->assertRedirect();

        $this->assertDatabaseHas('company_profiles', [
            'client_id' => $client->id,
            'name' => 'Client One Seller',
            'ntn_cnic' => '12345678',
            'address' => 'Client One Address',
        ]);
        $this->assertDatabaseMissing('company_profiles', [
            'client_id' => $otherClient->id,
            'name' => 'Client One Seller',
        ]);
    }
}
