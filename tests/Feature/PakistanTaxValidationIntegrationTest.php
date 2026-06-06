<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PakistanTaxValidationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function customer_form_rejects_invalid_ntn_cnic_and_normalizes_valid_values(): void
    {
        $this->seed();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('customers.store'), [
                'name' => 'Invalid Buyer',
                'ntn_cnic' => 'ABC12345',
                'strn' => null,
                'buyer_type' => 'registered',
                'province_id' => null,
                'address' => 'Lahore',
                'status' => 'active',
            ])
            ->assertSessionHasErrors([
                'ntn_cnic' => 'The NTN must be a valid Pakistani NTN.',
            ]);

        $this->actingAs($admin)
            ->post(route('customers.store'), [
                'name' => 'Valid Buyer',
                'ntn_cnic' => '4174941-3',
                'strn' => null,
                'buyer_type' => 'registered',
                'province_id' => null,
                'address' => 'Lahore',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'name' => 'Valid Buyer',
            'ntn_cnic' => '41749413',
        ]);
    }
}
