<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SoftDeleteManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function managing_site_admin_can_soft_delete_customer_and_hide_it_from_customer_screens(): void
    {
        $this->seed();
        [$owner, $client] = $this->managedOwner();
        $customer = Customer::create([
            'client_id' => $client->id,
            'name' => 'Soft Deleted Customer',
            'buyer_type' => 'registered',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', 'Customer deleted successfully.');

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertSame('inactive', Customer::withTrashed()->findOrFail($customer->id)->status->value);

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('Soft Deleted Customer');

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Soft Deleted Customer');
    }

    #[Test]
    public function managing_site_admin_can_soft_delete_invoice_and_hide_it_from_invoice_screens_and_dashboard(): void
    {
        $this->seed();
        [$owner, $client] = $this->managedOwner();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'SOFT-INV-001',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'buyer_name' => 'Soft Deleted Buyer',
            'status' => 'draft',
            'grand_total' => 2500,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('status', 'Invoice deleted successfully.');

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertDontSee('SOFT-INV-001')
            ->assertDontSee('Soft Deleted Buyer');

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('SOFT-INV-001')
            ->assertDontSee('Soft Deleted Buyer');
    }

    #[Test]
    public function client_admin_and_accountant_cannot_delete_customers_or_invoices(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $accountant = User::factory()->create([
            'client_id' => $admin->client_id,
            'role' => UserRole::Accountant,
        ]);
        $customer = Customer::create([
            'client_id' => $admin->client_id,
            'name' => 'Accountant Customer',
            'buyer_type' => 'registered',
            'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'client_id' => $admin->client_id,
            'invoice_number' => 'ACCOUNTANT-INV-001',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'buyer_name' => 'Accountant Buyer',
            'status' => 'draft',
            'created_by' => $accountant->id,
            'updated_by' => $accountant->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    #[Test]
    public function site_admin_must_be_managing_a_client_to_delete_customers_or_invoices(): void
    {
        $this->seed();
        [$owner, $client] = $this->managedOwner();
        $customer = Customer::create([
            'client_id' => $client->id,
            'name' => 'Managed Customer',
            'buyer_type' => 'registered',
            'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'OWNER-NOT-MANAGING-INV-001',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'buyer_name' => 'Managed Buyer',
            'status' => 'draft',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    private function managedOwner(): array
    {
        $client = Client::factory()->create(['name' => 'Managed Client']);
        $owner = User::factory()->create([
            'client_id' => null,
            'role' => UserRole::Owner,
        ]);

        return [$owner, $client];
    }
}
