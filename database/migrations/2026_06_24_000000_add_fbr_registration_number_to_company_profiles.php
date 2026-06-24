<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            $table->string('fbr_registration_number')->nullable()->after('ntn_cnic');
        });

        \Illuminate\Support\Facades\DB::table('company_profiles')
            ->select(['id', 'ntn_cnic', 'strn'])
            ->orderBy('id')
            ->each(function (object $profile): void {
                $taxNumber = strtoupper(str_replace(' ', '', (string) $profile->ntn_cnic));
                $strn = strtoupper(str_replace(' ', '', (string) $profile->strn));

                if (! preg_match('/^[A-Z]\d{6}$/', $taxNumber)) {
                    return;
                }

                \Illuminate\Support\Facades\DB::table('company_profiles')
                    ->where('id', $profile->id)
                    ->update([
                        'fbr_registration_number' => $taxNumber,
                        'ntn_cnic' => preg_match('/^[A-Z]\d{6}-\d$/', $strn) ? $strn : $profile->ntn_cnic,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            $table->dropColumn('fbr_registration_number');
        });
    }
};
