<?php
declare(strict_types=1);

/**
 * Unit tests for count processing (over/short adjustments via holding tank).
 *
 * @BABOK Related: UT-IC-001-005-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;
use ksfraser\FrontAccounting\InventoryCount\Exception\HoldingTankNotConfiguredException;
use ksfraser\FrontAccounting\InventoryCount\Exception\LocationNotSetException;
use ksfraser\FrontAccounting\InventoryCount\Service\InventoryCountService;
use ksfraser\FrontAccounting\InventoryCount\Tests\Fake\FakeCountRepository;
use ksfraser\FrontAccounting\InventoryCount\Tests\Fake\FakeFaApi;

class InventoryCountServiceTest extends TestCase
{
    /** @var FakeFaApi */
    protected $fa;

    /** @var FakeCountRepository */
    protected $repo;

    /** @var InventoryCountService */
    protected $service;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fa = new FakeFaApi();
        $this->repo = new FakeCountRepository();
        $this->service = new InventoryCountService($this->fa, $this->repo);
    }

    /**
     * Overage moves the excess from the counted location to the holding tank.
     *
     * @BABOK Related: UT-IC-001-005-001
     */
    public function testOverageTransfersToHoldingTank(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1', 12.0);
        $this->fa->seedQoh('ITEM1', 'STORE', 10.0);

        $result = $this->service->process($cart, 'STORE', '2026-08-22', 'HOLD');

        $this->assertNotNull($result->getTransNo());
        $this->assertCount(1, $this->fa->transfers);
        $transfer = $this->fa->transfers[0];
        $this->assertSame('ITEM1', $transfer['stockId']);
        $this->assertSame('STORE', $transfer['fromLoc']);
        $this->assertSame('HOLD', $transfer['toLoc']);
        $this->assertSame(2.0, $transfer['qty']);
        $this->assertCount(1, $result->getAdjustments());
    }

    /**
     * Shortage pulls the missing quantity from the holding tank.
     *
     * @BABOK Related: UT-IC-001-005-002
     */
    public function testShortageTransfersFromHoldingTank(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM2', 8.0);
        $this->fa->seedQoh('ITEM2', 'STORE', 10.0);

        $result = $this->service->process($cart, 'STORE', '2026-08-22', 'HOLD');

        $transfer = $this->fa->transfers[0];
        $this->assertSame('HOLD', $transfer['fromLoc']);
        $this->assertSame('STORE', $transfer['toLoc']);
        $this->assertSame(2.0, $transfer['qty']);
        $this->assertCount(1, $result->getAdjustments());
    }

    /**
     * Matching counts book no transfer batch but still record history.
     *
     * @BABOK Related: UT-IC-001-005-003
     */
    public function testMatchingCountsBookNoTransfer(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM3', 5.0);
        $this->fa->seedQoh('ITEM3', 'STORE', 5.0);

        $result = $this->service->process($cart, 'STORE', '2026-08-22', 'HOLD');

        $this->assertNull($result->getTransNo());
        $this->assertCount(0, $this->fa->transfers);
        $this->assertSame(['ITEM3'], $result->getRecorded());
        $this->assertCount(1, $this->repo->history);
    }

    /**
     * Missing holding tank configuration is rejected before any booking.
     *
     * @BABOK Related: UT-IC-004-001-001
     */
    public function testMissingHoldingTankThrows(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1', 1.0);

        $this->expectException(HoldingTankNotConfiguredException::class);
        $this->service->process($cart, 'STORE', '2026-08-22', '');
    }

    /**
     * Empty location is rejected.
     *
     * @BABOK Related: UT-IC-001-005-004
     */
    public function testEmptyLocationThrows(): void
    {
        $cart = new CountCart();

        $this->expectException(LocationNotSetException::class);
        $this->service->process($cart, '', '2026-08-22', 'HOLD');
    }

    /**
     * QOH is refreshed at process time so variances are current.
     *
     * @BABOK Related: UT-IC-001-005-001
     */
    public function testQohRefreshedBeforeVariance(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM4', 10.0); // line created with qoh 0
        $this->fa->seedQoh('ITEM4', 'STORE', 10.0);

        $result = $this->service->process($cart, 'STORE', '2026-08-22', 'HOLD');

        $this->assertCount(0, $this->fa->transfers, 'Refreshed QOH means no adjustment needed.');
        $this->assertNull($result->getTransNo());
    }
}
