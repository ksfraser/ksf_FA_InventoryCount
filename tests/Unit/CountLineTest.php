<?php
declare(strict_types=1);

/**
 * Unit tests for the CountLine value object.
 *
 * @BABOK Related: UT-IC-001-004-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountLine;

class CountLineTest extends TestCase
{
    /**
     * @BABOK Related: UT-IC-001-004-001
     */
    public function testVariancePositiveWhenOver(): void
    {
        $line = new CountLine('ITEM1', 12.0, 10.0);
        $this->assertSame(2.0, $line->variance());
        $this->assertFalse($line->matches());
    }

    /**
     * @BABOK Related: UT-IC-001-004-002
     */
    public function testVarianceNegativeWhenShort(): void
    {
        $line = new CountLine('ITEM1', 8.0, 10.0);
        $this->assertSame(-2.0, $line->variance());
        $this->assertFalse($line->matches());
    }

    /**
     * @BABOK Related: UT-IC-001-004-003
     */
    public function testMatchesWhenEqual(): void
    {
        $line = new CountLine('ITEM1', 10.0, 10.0);
        $this->assertTrue($line->matches());
    }

    /**
     * @BABOK Related: UT-IC-001-001-002
     */
    public function testBarcodeDefaultsToStockId(): void
    {
        $line = new CountLine('ITEM1', 1.0, 0.0);
        $this->assertSame('ITEM1', $line->getBarcode());
    }

    /**
     * @BABOK Related: UT-IC-001-001-002
     */
    public function testSettersUpdateState(): void
    {
        $line = new CountLine('ITEM1');
        $line->setCountedQty(5.0);
        $line->setQoh(3.0);
        $this->assertSame(5.0, $line->getCountedQty());
        $this->assertSame(3.0, $line->getQoh());
        $this->assertSame(2.0, $line->variance());
    }
}
