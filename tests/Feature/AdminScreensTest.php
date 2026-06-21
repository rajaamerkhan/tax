<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Invoice;
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

    #[Test]
    public function viewer_does_not_see_create_or_import_actions(): void
    {
        $this->seed();
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        Customer::create([
            'name' => 'Viewer Buyer',
            'buyer_type' => 'registered',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'VIEW-INV-1',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'buyer_name' => 'Viewer Buyer',
            'status' => 'draft',
            'created_by' => $viewer->id,
            'updated_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('New Invoice')
            ->assertDontSee('Create Invoice')
            ->assertDontSee('Import Invoices')
            ->assertDontSee('Add Customer')
            ->assertDontSee('>Import<', false);

        $this->actingAs($viewer)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertDontSee('Create Invoice');

        $this->actingAs($viewer)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('New Customer');

        $this->actingAs($viewer)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('Edit Invoice')
            ->assertDontSee('Validate with FBR')
            ->assertDontSee('Submit to FBR');

        $this->actingAs($viewer)
            ->get(route('imports.index'))
            ->assertForbidden();
    }
}
