<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_import_fbr_sales_format_and_create_missing_customer(): void
    {
        $this->seed();
        $admin = User::factory()->create([
            'client_id' => 1,
            'role' => UserRole::Admin,
        ]);

        $file = $this->salesFormatWorkbook();

        $this->actingAs($admin)
            ->post(route('imports.preview'), ['file' => $file])
            ->assertRedirect();

        $batch = InvoiceImportBatch::query()->latest()->firstOrFail();

        $this->assertSame('previewed', $batch->status);
        $this->assertSame([], $batch->errors);
        $this->assertSame('797', $batch->preview_rows[0]['invoice_number']);
        $this->assertSame('2026-05-01', $batch->preview_rows[0]['invoice_date']);
        $this->assertSame(22.247683, round((float) $batch->preview_rows[0]['unit_price'], 6));

        $this->actingAs($admin)
            ->post(route('imports.store', $batch))
            ->assertRedirect(route('imports.show', $batch));

        $customer = Customer::query()->where('ntn_cnic', '9999999999999')->firstOrFail();
        $this->assertSame('FBR INTERNAL', $customer->name);
        $this->assertSame('unregistered', $customer->buyer_type->value);
        $this->assertSame('Lahore', $customer->address);
        $this->assertSame('Punjab', $customer->province->name);

        $invoice = Invoice::query()->with(['items', 'customer', 'scenario', 'destinationProvince'])->where('invoice_number', '797')->firstOrFail();
        $item = $invoice->items->first();

        $this->assertTrue($invoice->customer->is($customer));
        $this->assertSame('Sale Invoice', $invoice->invoice_type);
        $this->assertSame('SN008', $invoice->scenario->code);
        $this->assertSame('Punjab', $invoice->destinationProvince->name);
        $this->assertSame('2523.2900', $item->hs_code);
        $this->assertSame('CEMENT', $item->description);
        $this->assertSame('3rd Schedule Goods', $item->sale_type);
        $this->assertSame(912155.0, (float) $item->value_excluding_sales_tax);
        $this->assertSame(187014.0, (float) $item->sales_tax);
        $this->assertSame(1038966.67, round((float) $item->fixed_notified_value, 2));
        $this->assertSame(1225980.67, (float) $item->total_value);
        $this->assertSame(1225980.67, (float) $invoice->grand_total);
    }

    #[Test]
    public function admin_can_download_fbr_sales_format_xlsx_template(): void
    {
        $this->seed();
        $admin = User::factory()->create([
            'client_id' => 1,
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('imports.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $path = tempnam(sys_get_temp_dir(), 'invoice-template-').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('Buyer NTN/CNIC', $sheet->getCell('B1')->getValue());
        $this->assertSame('Document Number', $sheet->getCell('H1')->getValue());
        $this->assertSame('Scenario', $sheet->getCell('J1')->getValue());
        $this->assertSame('Sales Tax/ FED in ST Mode', $sheet->getCell('T1')->getValue());
        $this->assertSame('FBR INTERNAL', $sheet->getCell('C2')->getValue());
    }

    #[Test]
    public function preview_reports_duplicate_invoice_numbers_before_importing(): void
    {
        $this->seed();
        $admin = User::factory()->create([
            'client_id' => 1,
            'role' => UserRole::Admin,
        ]);

        Invoice::create([
            'client_id' => $admin->client_id,
            'invoice_number' => '797',
            'invoice_date' => '2026-05-01',
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'buyer_name' => 'FBR INTERNAL',
            'status' => 'draft',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('imports.preview'), ['file' => $this->salesFormatWorkbook()])
            ->assertRedirect();

        $batch = InvoiceImportBatch::query()->latest()->firstOrFail();

        $this->assertSame('has_errors', $batch->status);
        $this->assertSame(['Invoice number 797 already exists in sandbox.'], $batch->errors[2]);
    }

    private function salesFormatWorkbook(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            [
                'Sr.',
                'Buyer NTN/CNIC',
                'Buyer Name',
                'Buyer Type',
                'Buyer Address',
                'Destination of Supply',
                'Document Type',
                'Document Number',
                'Document Date',
                'Scenario',
                'Sale Type',
                'Rate',
                'Hs Code',
                'Description',
                'Quantity',
                'Unit Price',
                'UOM',
                'Value of Sales Excluding Sales Tax',
                'Fixed / notified value or Retail Price',
                'Sales Tax/ FED in ST Mode',
                'Extra Tax',
                'ST Withheld at Source',
                'SRO No. / Schedule No.',
                'Item Sr. No.',
                'Further Tax',
                'Discount',
                'Total Value of Sales',
            ],
            [
                1,
                '9999999999999',
                'FBR INTERNAL',
                'Unregistered',
                'Lahore',
                'PUNJAB',
                'Sale Invoice',
                '797',
                '01-May-2026',
                'SN008',
                ' 3rd Schedule Goods ',
                '18%',
                '2523.2900:-',
                'CEMENT',
                41000,
                null,
                'KG',
                912155,
                1038966.6666666666,
                187014,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'sales-format-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'Sales Format May-2026 Digital Invoice.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
