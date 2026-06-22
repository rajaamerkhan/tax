<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\CompanyProfile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $punjab = Province::firstOrCreate(['code' => 'PB'], ['name' => 'Punjab', 'fbr_code' => '01']);
        Province::firstOrCreate(['code' => 'SD'], ['name' => 'Sindh', 'fbr_code' => '02']);
        Province::firstOrCreate(['code' => 'KP'], ['name' => 'Khyber Pakhtunkhwa', 'fbr_code' => '03']);
        Province::firstOrCreate(['code' => 'BL'], ['name' => 'Balochistan', 'fbr_code' => '04']);
        Province::firstOrCreate(['code' => 'ICT'], ['name' => 'Islamabad Capital Territory', 'fbr_code' => '05']);
        Province::firstOrCreate(['code' => 'AJK'], ['name' => 'Azad Jammu and Kashmir', 'fbr_code' => '06']);
        Province::firstOrCreate(['code' => 'GB'], ['name' => 'Gilgit-Baltistan', 'fbr_code' => '07']);
        Province::firstOrCreate(['code' => 'EXP'], ['name' => 'Export (Outside Pakistan)', 'fbr_code' => '08']);

        $this->call(UomSeeder::class);
        $this->call(SaleTypeSeeder::class);
        $this->call(TaxRateSeeder::class);
        $this->call(ScenarioSeeder::class);

        $defaultClient = Client::firstOrCreate(
            ['name' => 'Default Client'],
            ['email' => 'client@example.com', 'status' => 'active'],
        );

        CompanyProfile::updateOrCreate(
            ['client_id' => $defaultClient->id],
            [
                'name' => 'MUHAMMAD NAWAZ',
                'ntn_cnic' => '3530176754447',
                'strn' => 'A053307-0',
                'province_id' => $punjab->id,
                'address' => 'AMJAD CHADHAR MARKET, MAIN PIA ROAD, JOHAR TOWN, Lahore',
                'phone' => '+923224612373',
                'email' => 'ahmednawaz24.na@gmail.com',
                'fbr_environment' => 'sandbox',
            ],
        );

        User::updateOrCreate(
            ['email' => 'owner@fbr.local'],
            ['client_id' => null, 'name' => 'Application Owner', 'phone' => '+92-300-0000001', 'role' => UserRole::Owner, 'password' => 'password'],
        );

        User::updateOrCreate(
            ['email' => 'admin@fbr.local'],
            ['client_id' => $defaultClient->id, 'name' => 'System Admin', 'phone' => '+92-300-1111111', 'role' => UserRole::Admin, 'password' => 'password'],
        );

        User::updateOrCreate(
            ['email' => 'accountant@fbr.local'],
            ['client_id' => $defaultClient->id, 'name' => 'Lead Accountant', 'phone' => '+92-300-2222222', 'role' => UserRole::Accountant, 'password' => 'password'],
        );

        User::updateOrCreate(
            ['email' => 'viewer@fbr.local'],
            ['client_id' => $defaultClient->id, 'name' => 'Reporting Viewer', 'phone' => '+92-300-3333333', 'role' => UserRole::Viewer, 'password' => 'password'],
        );

        $this->call(HsCodeTariffSeeder::class);
    }
}
