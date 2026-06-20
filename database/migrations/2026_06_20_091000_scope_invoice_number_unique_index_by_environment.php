<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if ($this->indexExists('invoices', 'invoices_invoice_number_unique')) {
                $table->dropUnique('invoices_invoice_number_unique');
            }

            if (! $this->indexExists('invoices', 'invoices_environment_invoice_number_unique')) {
                $table->unique(['environment', 'invoice_number'], 'invoices_environment_invoice_number_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if ($this->indexExists('invoices', 'invoices_environment_invoice_number_unique')) {
                $table->dropUnique('invoices_environment_invoice_number_unique');
            }

            if (! $this->indexExists('invoices', 'invoices_invoice_number_unique')) {
                $table->unique('invoice_number', 'invoices_invoice_number_unique');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $row) {
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
