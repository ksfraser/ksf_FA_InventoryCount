<?php
declare(strict_types=1);

/**
 * Unit tests for the count cart aggregate.
 *
 * @BABOK Related: UT-IC-001-001-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;

class CountCartTest extends TestCase
{
    /**
     * Repeated scans of the same item increment quantity (legacy scan behaviour).
     *
     * @BABOK Related: UT-IC-001-001-001
     */
    public function testAddScanIncrementsExistingLine(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1', 1.0, '123456789');
        $cart->addScan('ITEM1', 2.0, '123456789');

        $this->assertSame(1, $cart->count());
        $this->assertSame(3.0, $cart->get('ITEM1')->getCountedQty());
    }

    /**
     * @BABOK Related: UT-IC-001-003-001
     */
    public function testUpdateQty(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1');

        $this->assertTrue($cart->updateQty('ITEM1', 7.0));
        $this->assertSame(7.0, $cart->get('ITEM1')->getCountedQty());
        $this->assertFalse($cart->updateQty('MISSING', 1.0));
    }

    /**
     * @BABOK Related: UT-IC-001-003-002
     */
    public function testDeleteRemovesLine(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1');

        $this->assertTrue($cart->delete('ITEM1'));
        $this->assertFalse($cart->has('ITEM1'));
        $this->assertFalse($cart->delete('ITEM1'));
    }

    /**
     * @BABOK Related: UT-IC-001-002-001
     */
    public function testFindByCodesMatchesAnyCandidate(): void
    {
        $cart = new CountCart();
        $cart->addScan('MASTER-SKU', 1.0, 'UPC-999');

        $this->assertNotNull($cart->findByCodes(['OTHER', 'UPC-999']));
        $this->assertNull($cart->findByCodes(['NOPE']));
    }

    /**
     * @BABOK Related: UT-IC-001-003-003
     */
    public function testClearEmptiesCart(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1');
        $cart->addScan('ITEM2');
        $cart->clear();

        $this->assertSame(0, $cart->count());
        $this->assertSame([], $cart->lines());
    }
}
