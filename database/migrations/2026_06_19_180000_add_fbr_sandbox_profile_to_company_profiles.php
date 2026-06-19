<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_profiles', 'fbr_business_nature')) {
                $table->string('fbr_business_nature')->nullable()->after('fbr_environment');
            }

            if (! Schema::hasColumn('company_profiles', 'fbr_sector')) {
                $table->string('fbr_sector')->nullable()->after('fbr_business_nature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('company_profiles', 'fbr_business_nature')) {
                $table->dropColumn('fbr_business_nature');
            }

            if (Schema::hasColumn('company_profiles', 'fbr_sector')) {
                $table->dropColumn('fbr_sector');
            }
        });
    }
};
