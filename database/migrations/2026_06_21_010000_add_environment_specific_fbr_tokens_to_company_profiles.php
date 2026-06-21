<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            $table->text('fbr_sandbox_token')->nullable()->after('fbr_token');
            $table->text('fbr_production_token')->nullable()->after('fbr_sandbox_token');
        });

        DB::table('company_profiles')
            ->where('fbr_environment', 'production')
            ->whereNotNull('fbr_token')
            ->update(['fbr_production_token' => DB::raw('fbr_token')]);

        DB::table('company_profiles')
            ->where(function ($query): void {
                $query->where('fbr_environment', '!=', 'production')
                    ->orWhereNull('fbr_environment');
            })
            ->whereNotNull('fbr_token')
            ->update(['fbr_sandbox_token' => DB::raw('fbr_token')]);
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            $table->dropColumn(['fbr_sandbox_token', 'fbr_production_token']);
        });
    }
};
