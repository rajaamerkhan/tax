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

class SaasAccessControlTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_create_client_with_admin_credentials(): void
    {
        $owner = User::factory()->create([
            'client_id' => null,
            'role' => UserRole::Owner,
        ]);

        $response = $this->actingAs($owner)->post(route('owner.clients.store'), [
            'name' => 'Acme Textiles',
            'contact_name' => 'Ayesha Khan',
            'email' => 'accounts@acme.test',
            'phone' => '+92-300-0000000',
            'status' => 'active',
            'admin_name' => 'Acme Admin',
            'admin_email' => 'admin@acme.test',
            'admin_phone' => '+92-300-1111111',
            'admin_password' => 'secure-password',
            'admin_password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect();
        $client = Client::query()->where('name', 'Acme Textiles')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'client_id' => $client->id,
            'email' => 'admin@acme.test',
            'role' => UserRole::Admin->value,
        ]);
    }

    #[Test]
    public function client_users_cannot_view_or_list_another_clients_invoices(): void
    {
        [$clientUser, $ownInvoice] = $this->clientWithInvoice('Client A', 'A-INV-1', 'Own Buyer');
        [, $otherInvoice] = $this->clientWithInvoice('Client B', 'B-INV-1', 'Other Buyer');

        $this->actingAs($clientUser)
            ->get(route('invoices.show', $otherInvoice))
            ->assertNotFound();

        $this->actingAs($clientUser)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee($ownInvoice->invoice_number)
            ->assertDontSee($otherInvoice->invoice_number)
            ->assertDontSee('Other Buyer');
    }

    #[Test]
    public function customer_autocomplete_is_scoped_to_authenticated_client(): void
    {
        [$clientUser] = $this->clientWithInvoice('Client A', 'A-INV-1', 'Own Buyer');
        $otherClient = Client::factory()->create(['name' => 'Client B']);

        Customer::create([
            'client_id' => $otherClient->id,
            'name' => 'Hidden Customer',
            'buyer_type' => 'registered',
            'status' => 'active',
        ]);

        $this->actingAs($clientUser)
            ->getJson(route('invoices.autocomplete', ['resource' => 'customers', 'q' => 'Hidden']))
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    #[Test]
    public function owner_can_manage_a_client_and_see_client_menus(): void
    {
        $owner = User::factory()->create([
            'client_id' => null,
            'role' => UserRole::Owner,
        ]);
        $client = Client::factory()->create(['name' => 'Managed Client']);

        $this->actingAs($owner)
            ->post(route('owner.clients.manage', $client))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Managing Managed Client')
            ->assertSee('Dashboard')
            ->assertSee('Invoices')
            ->assertSee('Customers')
            ->assertSee('Import')
            ->assertSee('Company')
            ->assertSee('Exit Client');
    }

    #[Test]
    public function owner_manage_mode_scopes_client_workflows_to_selected_client(): void
    {
        $owner = User::factory()->create([
            'client_id' => null,
            'role' => UserRole::Owner,
        ]);
        [, $ownInvoice, $client] = $this->clientWithInvoice('Managed Client', 'OWN-INV-1', 'Own Buyer');
        [, $otherInvoice] = $this->clientWithInvoice('Other Client', 'OTHER-INV-1', 'Other Buyer');

        $this->actingAs($owner)
            ->withSession(['managed_client_id' => $client->id, 'managed_client_name' => $client->name])
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee($ownInvoice->invoice_number)
            ->assertDontSee($otherInvoice->invoice_number)
            ->assertDontSee('Other Buyer');
    }

    private function clientWithInvoice(string $clientName, string $invoiceNumber, string $buyerName): array
    {
        $client = Client::factory()->create(['name' => $clientName]);
        $user = User::factory()->create([
            'client_id' => $client->id,
            'role' => UserRole::Admin,
        ]);
        $customer = Customer::create([
            'client_id' => $client->id,
            'name' => $buyerName,
            'buyer_type' => 'registered',
            'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'customer_id' => $customer->id,
            'buyer_name' => $buyerName,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return [$user, $invoice, $client];
    }
}
