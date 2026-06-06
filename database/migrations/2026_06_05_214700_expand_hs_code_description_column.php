<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite' || ! Schema::hasTable('hs_codes')) {
            return;
        }

        Schema::table('hs_codes', function (Blueprint $table): void {
            $table->text('description')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' || ! Schema::hasTable('hs_codes')) {
            return;
        }

        Schema::table('hs_codes', function (Blueprint $table): void {
            $table->string('description')->change();
        });
    }
};
