<?php
declare(strict_types=1);

/**
 * Unit tests for text/CSV import of counts.
 *
 * @BABOK Related: UT-IC-002-001-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;
use ksfraser\FrontAccounting\InventoryCount\Exception\LocationNotSetException;
use ksfraser\FrontAccounting\InventoryCount\Service\ImportService;
use ksfraser\FrontAccounting\InventoryCount\Tests\Fake\FakeFaApi;

class ImportServiceTest extends TestCase
{
    /**
     * @return void
     */
    public function testParseOneCodePerLineDefaultsQtyOne(): void
    {
        $service = new ImportService(new FakeFaApi());

        $tuples = $service->parse("UPC-A\nUPC-B\n");

        $this->assertCount(2, $tuples);
        $this->assertSame('UPC-A', $tuples[0]['code']);
        $this->assertSame(1.0, $tuples[0]['qty']);
    }

    /**
     * @return void
     */
    public function testParseSupportsOptionalQuantityAndComments(): void
    {
        $service = new ImportService(new FakeFaApi());

        $tuples = $service->parse("# comment\nUPC-A, 5\n\nUPC-B,2.5");

        $this->assertCount(2, $tuples);
        $this->assertSame(5.0, $tuples[0]['qty']);
        $this->assertSame(2.5, $tuples[1]['qty']);
    }

    /**
     * @return void
     */
    public function testImportResolvesBarcodesIntoCart(): void
    {
        $fa = new FakeFaApi();
        $fa->barcodeMap['123456789'] = ['MASTER-SKU'];
        $service = new ImportService($fa);
        $cart = new CountCart();

        $unresolved = $service->import("123456789", $cart, 'STORE');

        $this->assertSame([], $unresolved);
        $this->assertTrue($cart->has('MASTER-SKU'));
        $this->assertSame('123456789', $cart->get('MASTER-SKU')->getBarcode());
    }

    /**
     * Unresolvable codes are reported back, not fatal.
     *
     * @BABOK Related: UT-IC-002-001-002
     */
    public function testImportReportsUnresolvedCodes(): void
    {
        $service = new ImportService(new FakeFaApi());
        $cart = new CountCart();

        $unresolved = $service->import("GOODCODE\nUNKNOWN1\n", $cart, 'STORE');
        // GOODCODE resolves to nothing either (empty barcode map) - both reported.
        $this->assertContains('UNKNOWN1', $unresolved);
    }

    /**
     * @BABOK Related: UT-IC-002-001-003
     */
    public function testImportRequiresLocation(): void
    {
        $service = new ImportService(new FakeFaApi());
        $cart = new CountCart();

        $this->expectException(LocationNotSetException::class);
        $service->import("UPC-A", $cart, '');
    }
}
