<?php

use App\Enums\FbrEnvironment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'environment')) {
                $table->string('environment')->default(FbrEnvironment::Sandbox->value)->index()->after('invoice_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (Schema::hasColumn('invoices', 'environment')) {
                $table->dropColumn('environment');
            }
        });
    }
};
