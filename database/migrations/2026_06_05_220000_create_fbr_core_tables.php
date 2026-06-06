<?php

use App\Enums\BuyerType;
use App\Enums\CustomerStatus;
use App\Enums\FbrEnvironment;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default(UserRole::Viewer->value)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        Schema::create('provinces', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('fbr_code')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('company_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('ntn_cnic');
            $table->string('strn')->nullable();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('fbr_token')->nullable();
            $table->string('fbr_environment')->default(FbrEnvironment::Sandbox->value);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('ntn_cnic')->nullable()->index();
            $table->string('strn')->nullable()->index();
            $table->string('buyer_type')->default(BuyerType::Unregistered->value);
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('status')->default(CustomerStatus::Active->value)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('uoms', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('fbr_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 8, 2);
            $table->string('fbr_id')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sale_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('fbr_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('scenarios', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('document_type_id')->nullable();
            $table->timestamps();
        });

        Schema::create('sro_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('fbr_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('hs_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('description');
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fbr_item_code')->nullable()->index();
            $table->string('custom_duty_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->string('invoice_type');
            $table->foreignId('scenario_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_origin_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('destination_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_ntn_cnic')->nullable()->index();
            $table->string('buyer_strn')->nullable()->index();
            $table->text('buyer_address')->nullable();
            $table->string('status')->default(InvoiceStatus::Draft->value)->index();
            $table->string('fbr_invoice_id')->nullable()->index();
            $table->timestamp('fbr_submitted_at')->nullable();
            $table->timestamp('editable_until')->nullable()->index();
            $table->timestamp('locked_at')->nullable();
            $table->json('fbr_response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->decimal('value_excluding_sales_tax', 18, 2)->default(0);
            $table->decimal('sales_tax_amount', 18, 2)->default(0);
            $table->decimal('extra_tax_amount', 18, 2)->default(0);
            $table->decimal('further_tax_amount', 18, 2)->default(0);
            $table->decimal('fed_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hs_code_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sro_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hs_code')->nullable();
            $table->string('description');
            $table->string('uom')->nullable();
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('rate_percent', 8, 2)->default(0);
            $table->decimal('value_excluding_sales_tax', 18, 2)->default(0);
            $table->decimal('sales_tax', 18, 2)->default(0);
            $table->decimal('extra_tax', 18, 2)->default(0);
            $table->decimal('further_tax', 18, 2)->default(0);
            $table->decimal('fed_payable', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('fixed_notified_value', 18, 2)->nullable();
            $table->string('sale_type')->nullable();
            $table->string('sro_schedule_number')->nullable();
            $table->string('item_serial_number')->nullable();
            $table->decimal('total_value', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('filename');
            $table->json('preview_rows')->nullable();
            $table->json('errors')->nullable();
            $table->unsignedInteger('imported_count')->default(0);
            $table->string('status')->default('uploaded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_import_batches');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('hs_codes');
        Schema::dropIfExists('sro_schedules');
        Schema::dropIfExists('scenarios');
        Schema::dropIfExists('sale_types');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('provinces');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'role', 'last_login_at']);
        });
    }
};
