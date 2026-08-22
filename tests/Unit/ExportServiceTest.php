<?php
declare(strict_types=1);

/**
 * Unit tests for CSV export of the count cart.
 *
 * @BABOK Related: UT-IC-002-002-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;
use ksfraser\FrontAccounting\InventoryCount\Service\ExportService;

class ExportServiceTest extends TestCase
{
    /**
     * @BABOK Related: UT-IC-002-002-001
     */
    public function testToCsvIncludesHeaderAndLines(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1', 12.0, 'UPC-1');
        $cart->get('ITEM1')->setQoh(10.0);

        $csv = (new ExportService())->toCsv($cart);

        $rows = explode("\n", trim($csv));
        $this->assertSame('stock_id,barcode,counted_qty,qoh,variance', $rows[0]);
        $this->assertSame('ITEM1,UPC-1,12,10,2', $rows[1]);
    }

    /**
     * @BABOK Related: UT-IC-002-002-001
     */
    public function testEmptyCartExportsHeaderOnly(): void
    {
        $csv = (new ExportService())->toCsv(new CountCart());

        $this->assertSame('stock_id,barcode,counted_qty,qoh,variance', trim($csv));
    }
}
