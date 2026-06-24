<?php

namespace Tests\Unit;

use App\Rules\PakistanCnicRule;
use App\Rules\PakistanNtnRule;
use App\Support\PakistanTaxHelper;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PakistanTaxHelperTest extends TestCase
{
    #[Test]
    public function it_normalizes_cnic_and_ntn_values(): void
    {
        $this->assertSame('3520212345671', PakistanTaxHelper::normalizeCnic('35202-1234567-1'));
        $this->assertSame('41749413', PakistanTaxHelper::normalizeNtn('4174941-3'));
        $this->assertSame('F518891', PakistanTaxHelper::normalizeFbrSellerRegistration('F518891-5'));
        $this->assertSame('F518891', PakistanTaxHelper::normalizeFbrSellerRegistration(' f518891 '));
        $this->assertSame('F518891-5', PakistanTaxHelper::normalizeSellerTaxNumber(' f518891-5 '));
    }

    #[Test]
    public function it_validates_cnic_values(): void
    {
        $this->assertTrue(PakistanTaxHelper::isValidCnic('3520212345671'));
        $this->assertTrue(PakistanTaxHelper::isValidCnic('35202-1234567-1'));
        $this->assertFalse(PakistanTaxHelper::isValidCnic('352021234567'));
        $this->assertFalse(PakistanTaxHelper::isValidCnic('abcde12345678'));
    }

    #[Test]
    public function it_validates_ntn_values(): void
    {
        $this->assertTrue(PakistanTaxHelper::isValidNtn('3520212345671'));
        $this->assertTrue(PakistanTaxHelper::isValidNtn('41749413'));
        $this->assertTrue(PakistanTaxHelper::isValidNtn('4174941-3'));
        $this->assertFalse(PakistanTaxHelper::isValidNtn('417494'));
        $this->assertFalse(PakistanTaxHelper::isValidNtn('ABC12345'));
    }

    #[Test]
    public function it_validates_fbr_seller_registration_values(): void
    {
        $this->assertTrue(PakistanTaxHelper::isValidFbrSellerRegistration('35202-1234567-1'));
        $this->assertTrue(PakistanTaxHelper::isValidFbrSellerRegistration('F518891'));
        $this->assertTrue(PakistanTaxHelper::isValidFbrSellerRegistration('F518891-5'));
        $this->assertFalse(PakistanTaxHelper::isValidFbrSellerRegistration('4174941-3'));
        $this->assertFalse(PakistanTaxHelper::isValidFbrSellerRegistration('F51889'));
        $this->assertFalse(PakistanTaxHelper::isValidFbrSellerRegistration('ABC12345'));
        $this->assertTrue(PakistanTaxHelper::isValidSellerTaxNumber('F518891-5'));
        $this->assertTrue(PakistanTaxHelper::isValidSellerTaxNumber('4174941-3'));
        $this->assertFalse(PakistanTaxHelper::isValidSellerTaxNumber('35202-1234567-1'));
    }

    #[Test]
    public function object_rules_return_api_friendly_messages(): void
    {
        $cnicValidator = Validator::make(
            ['cnic' => '352021234567'],
            ['cnic' => ['required', new PakistanCnicRule()]],
        );

        $this->assertTrue($cnicValidator->fails());
        $this->assertSame('The CNIC must be a valid Pakistani CNIC.', $cnicValidator->errors()->first('cnic'));

        $ntnValidator = Validator::make(
            ['ntn' => 'ABC12345'],
            ['ntn' => ['required', new PakistanNtnRule()]],
        );

        $this->assertTrue($ntnValidator->fails());
        $this->assertSame('The NTN must be a valid Pakistani NTN.', $ntnValidator->errors()->first('ntn'));
    }

    #[Test]
    public function string_based_cnic_and_ntn_rules_work(): void
    {
        $validValidator = Validator::make(
            [
                'cnic' => '35202-1234567-1',
                'ntn' => '4174941-3',
            ],
            [
                'cnic' => 'required|cnic',
                'ntn' => 'required|ntn',
            ],
        );

        $this->assertFalse($validValidator->fails());

        $invalidValidator = Validator::make(
            [
                'cnic' => 'abcde12345678',
                'ntn' => '417494',
            ],
            [
                'cnic' => 'required|cnic',
                'ntn' => 'required|ntn',
            ],
        );

        $this->assertTrue($invalidValidator->fails());
        $this->assertSame('The CNIC must be a valid Pakistani CNIC.', $invalidValidator->errors()->first('cnic'));
        $this->assertSame('The NTN must be a valid Pakistani NTN.', $invalidValidator->errors()->first('ntn'));
    }
}
