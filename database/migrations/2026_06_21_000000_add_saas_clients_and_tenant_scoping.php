<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        $clientId = DB::table('clients')->insertGetId([
            'name' => 'Default Client',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('company_profiles', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique('client_id', 'company_profiles_client_id_unique');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['client_id', 'status']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if ($this->indexExists('invoices', 'invoices_environment_invoice_number_unique')) {
                $table->dropUnique('invoices_environment_invoice_number_unique');
            }

            $table->foreignId('client_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['client_id', 'environment', 'status']);
            $table->unique(['client_id', 'environment', 'invoice_number'], 'invoices_client_environment_number_unique');
        });

        Schema::table('invoice_import_batches', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('fbr_api_logs', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['client_id', 'environment', 'status']);
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('users')->where('role', '!=', UserRole::Owner->value)->update(['client_id' => $clientId]);
        DB::table('company_profiles')->whereNull('client_id')->update(['client_id' => $clientId]);
        DB::table('customers')->whereNull('client_id')->update(['client_id' => $clientId]);
        DB::table('invoices')->whereNull('client_id')->update(['client_id' => $clientId]);
        DB::table('invoice_import_batches')->whereNull('client_id')->update(['client_id' => $clientId]);
        DB::table('fbr_api_logs')->whereNull('client_id')->update(['client_id' => $clientId]);
        DB::table('audit_logs')->whereNull('client_id')->update(['client_id' => $clientId]);
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('fbr_api_logs', function (Blueprint $table): void {
            $table->dropIndex(['client_id', 'environment', 'status']);
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('invoice_import_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if ($this->indexExists('invoices', 'invoices_client_environment_number_unique')) {
                $table->dropUnique('invoices_client_environment_number_unique');
            }

            $table->dropIndex(['client_id', 'environment', 'status']);
            $table->dropConstrainedForeignId('client_id');

            if (! $this->indexExists('invoices', 'invoices_environment_invoice_number_unique')) {
                $table->unique(['environment', 'invoice_number'], 'invoices_environment_invoice_number_unique');
            }
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['client_id', 'status']);
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('company_profiles', function (Blueprint $table): void {
            if ($this->indexExists('company_profiles', 'company_profiles_client_id_unique')) {
                $table->dropUnique('company_profiles_client_id_unique');
            }

            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::dropIfExists('clients');
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('{$table}')") as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
