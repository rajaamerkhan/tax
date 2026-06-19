<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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
    }

    #[Test]
    public function it_stores_a_36_character_fbr_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $token = str_repeat('a', 36);

        $response = $this->actingAs($user)->put(route('company.update'), [
            'name' => 'Demo Seller',
            'ntn_cnic' => '1234567-8',
            'strn' => '1234567890123',
            'address' => 'Lahore, Pakistan',
            'phone' => '+92-300-0000000',
            'email' => 'info@example.com',
            'fbr_token' => $token,
            'fbr_environment' => 'sandbox',
            'fbr_business_nature' => 'distributor',
        ]);

        $response->assertRedirect();
        $this->assertSame($token, CompanyProfile::first()->fbr_token);
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
            'fbr_token' => $existingToken,
            'fbr_environment' => 'sandbox',
        ]);

        $response = $this->actingAs($user)->put(route('company.update'), [
            'name' => 'Updated Seller',
            'ntn_cnic' => '1234567-8',
            'strn' => null,
            'province_id' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'fbr_token' => '',
            'fbr_environment' => 'production',
            'fbr_business_nature' => 'distributor',
        ]);

        $response->assertRedirect();

        $company = CompanyProfile::first();
        $this->assertSame('Updated Seller', $company->name);
        $this->assertSame($existingToken, $company->fbr_token);
        $this->assertSame('production', $company->fbr_environment->value);
        $this->assertSame('distributor', $company->fbr_business_nature);
    }
}
