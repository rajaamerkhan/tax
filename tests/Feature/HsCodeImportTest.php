<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\HsCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HsCodeImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_import_hs_codes_from_csv(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "hs_code,description,uom,custom_duty_code\n2523.2910,PORTLAND CEMENT,KG,CD-001\n";
        $file = UploadedFile::fake()->createWithContent('hs-codes.csv', $csv);

        $response = $this->actingAs($admin)->post(route('reference-data.hs-codes.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hs_codes', [
            'code' => '2523.2910',
            'description' => 'PORTLAND CEMENT',
            'custom_duty_code' => 'CD-001',
        ]);
    }

    #[Test]
    public function admin_csv_import_preserves_leading_zero_hs_codes(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "hs_code,description,uom,custom_duty_code\n0101.2100,PURE BRED BREEDING ANIMALS,,0\n";
        $file = UploadedFile::fake()->createWithContent('hs-codes-leading-zero.csv', $csv);

        $response = $this->actingAs($admin)->post(route('reference-data.hs-codes.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hs_codes', [
            'code' => '0101.2100',
            'description' => 'PURE BRED BREEDING ANIMALS',
            'custom_duty_code' => '0',
        ]);
    }
}
