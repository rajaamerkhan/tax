<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CompanyProfile;
use App\Models\FbrApiLog;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FbrEnvironmentScopingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function layout_badge_uses_company_profile_environment(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CompanyProfile::query()->first()->update(['fbr_environment' => 'production']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('PRODUCTION');
    }

    #[Test]
    public function invoice_index_only_shows_current_environment_invoices(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CompanyProfile::query()->first()->update(['fbr_environment' => 'production']);

        Invoice::create([
            'invoice_number' => 'SANDBOX-INV-1',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'status' => 'draft',
        ]);
        Invoice::create([
            'invoice_number' => 'PRODUCTION-INV-1',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'production',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('PRODUCTION-INV-1')
            ->assertDontSee('SANDBOX-INV-1');
    }

    #[Test]
    public function invoice_routes_do_not_open_other_environment_invoices(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CompanyProfile::query()->first()->update(['fbr_environment' => 'production']);

        $sandboxInvoice = Invoice::create([
            'invoice_number' => 'SANDBOX-INV-2',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('invoices.show', $sandboxInvoice))
            ->assertNotFound();
    }

    #[Test]
    public function mock_console_logs_are_scoped_to_current_environment(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CompanyProfile::query()->first()->update(['fbr_environment' => 'production']);

        FbrApiLog::create([
            'endpoint' => '/sandbox',
            'method' => 'POST',
            'environment' => 'sandbox',
            'status' => 'Valid',
        ]);
        FbrApiLog::create([
            'endpoint' => '/production',
            'method' => 'POST',
            'environment' => 'production',
            'status' => 'Valid',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mock-fbr-console'))
            ->assertOk()
            ->assertSee('/production')
            ->assertDontSee('/sandbox');
    }
}
