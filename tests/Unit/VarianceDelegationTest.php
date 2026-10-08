<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\Common\ItemEvents\ItemEventPublisher;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;
use ksfraser\FrontAccounting\InventoryCount\Service\InventoryCountService;
use ksfraser\FrontAccounting\InventoryCount\Tests\Fake\FakeCountRepository;
use ksfraser\FrontAccounting\InventoryCount\Tests\Fake\FakeFaApi;

/**
 * Variance booking delegation.
 *
 * Location-scoped stock movement belongs to ksf_FA_Warehouse now, so
 * InventoryCount asks it first and keeps its inline path as a fallback. Both
 * branches must produce identical observable effects, because listeners
 * (Square, Woocommerce) cannot tell which one ran.
 *
 * @package ksfraser\FrontAccounting\InventoryCount\Tests\Unit
 * @since 1.1.0
 */
class VarianceDelegationTest extends TestCase
{
    /** @var FakeFaApi */
    private $fa;

    /** @var FakeCountRepository */
    private $repository;

    /** @var array<int, array<string, mixed>> Captured item events. */
    private $events = array();

    /** @var InventoryCountService */
    private $service;

    protected function setUp(): void
    {
        $GLOBALS['ksf_test_first_calls'] = array();
        $GLOBALS['ksf_test_first_reply'] = array();

        $this->fa = new FakeFaApi();
        $this->repository = new FakeCountRepository();
        $this->events = array();

        $dispatcher = function (string $hook, array $payload): void {
            $this->events[] = array('hook' => $hook, 'payload' => $payload);
        };

        $this->service = new InventoryCountService(
            $this->fa,
            $this->repository,
            new ItemEventPublisher($dispatcher)
        );
    }

    protected function tearDown(): void
    {
        $GLOBALS['ksf_test_first_calls'] = array();
        $GLOBALS['ksf_test_first_reply'] = array();
    }

    /**
     * A cart with one over and one short line.
     *
     * @return CountCart
     */
    private function mixedCart(): CountCart
    {
        $cart = new CountCart();
        $cart->addScan('OVER', 12.0);
        $cart->addScan('SHORT', 8.0);

        $this->fa->seedQoh('OVER', 'STORE', 10.0);
        $this->fa->seedQoh('SHORT', 'STORE', 10.0);

        return $cart;
    }

    /**
     * @return array[] Calls made through hook_invoke_first.
     */
    private function firstCalls(): array
    {
        return array_values(array_filter(
            $GLOBALS['ksf_test_first_calls'],
            function (array $call) {
                return $call[0] === 'respondToCapabilityRequest'
                    && isset($call[2]['request'])
                    && $call[2]['request'] === 'move_inventory';
            }
        ));
    }

    public function testAsksWarehouseFirstWhenTheHookExists(): void
    {
        $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', 'HOLD');

        $calls = $this->firstCalls();

        $this->assertCount(1, $calls, 'the move_inventory capability must be attempted');
        $opts = $calls[0][2];
        $this->assertSame('STORE', $opts['loc_code']);
        $this->assertSame('2026-03-01', $opts['on_date']);
        $this->assertSame('HOLD', $opts['holding_tank']);
        $this->assertCount(2, $opts['variances']);
    }

    public function testWarehouseReplyIsUsedAndTheInlinePathIsSkipped(): void
    {
        $GLOBALS['ksf_test_first_reply']['move_inventory'] = array(
            'trans_no'   => 555,
            'adjustments' => array(
                array('stock_id' => 'OVER', 'qty' => 2.0, 'from' => 'STORE', 'to' => 'HOLD', 'reason' => 'over'),
                array('stock_id' => 'SHORT', 'qty' => 2.0, 'from' => 'HOLD', 'to' => 'STORE', 'reason' => 'short'),
            ),
        );

        $result = $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', 'HOLD');

        $this->assertSame(
            array(),
            $this->fa->transfers,
            'warehouse owns the booking, so InventoryCount must not also transfer'
        );
        $this->assertSame('555', $result->getTransNo());
    }

    public function testWarehouseReplyStillRecordsAdjustmentsAndPublishesEvents(): void
    {
        $GLOBALS['ksf_test_first_reply']['move_inventory'] = array(
            'trans_no'   => 556,
            'adjustments' => array(
                array('stock_id' => 'OVER', 'qty' => 2.0, 'from' => 'STORE', 'to' => 'HOLD', 'reason' => 'over'),
                array('stock_id' => 'SHORT', 'qty' => 2.0, 'from' => 'HOLD', 'to' => 'STORE', 'reason' => 'short'),
            ),
        );

        $result = $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', 'HOLD');

        $this->assertCount(2, $result->getAdjustments());
        $this->assertCount(
            2,
            $this->events,
            'listeners must be notified regardless of which path booked'
        );
    }

    public function testFallsBackToInlineBookingWhenWarehouseDeclines(): void
    {
        // No scripted reply => the double returns null, i.e. warehouse inactive.
        $result = $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', 'HOLD');

        $this->assertCount(2, $this->fa->transfers, 'fallback must still book both lines');
        $this->assertSame((string)($this->fa->nextTransNo - 1), $result->getTransNo());
        $this->assertCount(2, $result->getAdjustments());
        $this->assertCount(2, $this->events);
    }

    public function testBothPathsProduceTheSameAdjustments(): void
    {
        $inline = $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', 'HOLD');
        $inlineAdjustments = $inline->getAdjustments();

        // Reset and replay through the warehouse path with the same shape.
        $this->setUp();
        $GLOBALS['ksf_test_first_reply']['move_inventory'] = array(
            'trans_no'   => 1,
            'adjustments' => array(
                array('stock_id' => 'OVER', 'qty' => 2.0, 'from' => 'STORE', 'to' => 'HOLD', 'reason' => 'over'),
                array('stock_id' => 'SHORT', 'qty' => 2.0, 'from' => 'HOLD', 'to' => 'STORE', 'reason' => 'short'),
            ),
        );
        $delegated = $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', 'HOLD');

        $this->assertEquals($inlineAdjustments, $delegated->getAdjustments());
    }

    public function testCleanCountDoesNotBookAnythingOnEitherPath(): void
    {
        $cart = new CountCart();
        $cart->addScan('ITEM1', 10.0);
        $this->fa->seedQoh('ITEM1', 'STORE', 10.0);

        $result = $this->service->process($cart, 'STORE', '2026-03-01', 'HOLD');

        $this->assertSame(array(), $this->fa->transfers);
        $this->assertCount(0, $result->getAdjustments());
    }

    public function testMissingHoldingTankIsStillRefusedBeforeAnyBooking(): void
    {
        $this->expectException(\ksfraser\FrontAccounting\InventoryCount\Exception\HoldingTankNotConfiguredException::class);

        $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', '');
    }

    public function testCountsAreStillRecordedWhenBookingIsDelegated(): void
    {
        $GLOBALS['ksf_test_first_reply']['move_inventory'] = array(
            'trans_no'   => 1,
            'adjustments' => array(),
        );

        $this->service->process($this->mixedCart(), 'STORE', '2026-03-01', 'HOLD');

        $this->assertNotEmpty(
            $this->repository->scans,
            'scan recording is InventoryCount\'s own job and must survive delegation'
        );
    }
}