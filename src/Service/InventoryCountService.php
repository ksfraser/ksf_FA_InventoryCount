<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Service;

use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountSummary;
use ksfraser\FrontAccounting\InventoryCount\Domain\ProcessResult;
use ksfraser\FrontAccounting\InventoryCount\Exception\HoldingTankNotConfiguredException;
use ksfraser\FrontAccounting\InventoryCount\Exception\LocationNotSetException;
use ksfraser\FrontAccounting\InventoryCount\Fa\FaApiInterface;
use ksfraser\FrontAccounting\InventoryCount\Repository\CountRepositoryInterface;
use ksfraser\FrontAccounting\Common\ItemEvents\ItemEventPublisher;

/**
 * Processes an inventory count: refreshes QOH, computes over/short variances
 * and books compensating stock transfers through the HOLDING tank location.
 *
 * Overages move stock from the counted location into the holding tank;
 * shortages move stock from the holding tank into the counted location.
 *
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-IC-001-005
 *
 * @since 1.0.0
 */
class InventoryCountService
{
    /** @var FaApiInterface */
    protected $fa;

    /** @var CountRepositoryInterface */
    protected $repository;

    /** @var ItemEventPublisher Broadcasts item_updated so Square/Woocommerce sync. */
    protected $publisher;

    /**
     * Constructor (dependency injection).
     *
     * @param FaApiInterface           $fa         FrontAccounting API wrapper.
     * @param CountRepositoryInterface $repository Count persistence.
     * @param ItemEventPublisher|null  $publisher  Item lifecycle event publisher
     *                                             (item_created/item_updated broadcasts);
     *             defaults to the shared ksf_FA_Common publisher.
     *
     * @since 1.0.0
     */
    public function __construct(
        FaApiInterface $fa,
        CountRepositoryInterface $repository,
        ?ItemEventPublisher $publisher = null
    ) {
        $this->fa = $fa;
        $this->repository = $repository;
        $this->publisher = $publisher ?? new ItemEventPublisher();
    }

    /**
     * Process a count cart for a location on a date.
     *
     * @param CountCart $cart          Counted lines.
     * @param string    $location      Location that was counted.
     * @param string    $date          Count document date (Y-m-d).
     * @param string    $holdingTank   HOLDING tank location code.
     * @param bool      $isFullCount   True for full inventory, false for partial.
     * @return ProcessResult Adjustments booked and history recorded.
     * @throws HoldingTankNotConfiguredException When no holding tank configured.
     * @throws LocationNotSetException When the location is empty.
     *
     * @since 1.0.0
     */
    public function process(
        CountCart $cart,
        string $location,
        string $date,
        string $holdingTank,
        bool $isFullCount = false
    ): ProcessResult {
        if ($location === '') {
            throw new LocationNotSetException('A count location must be selected before processing.');
        }
        if ($holdingTank === '') {
            throw new HoldingTankNotConfiguredException(
                'The HOLDING tank location is not configured. Set it in module Configuration.'
            );
        }

        $result = new ProcessResult();
        $lines = $cart->lines();

        // Refresh system QOH so variances reflect reality at process time.
        foreach ($lines as $line) {
            $line->setQoh($this->fa->getQuantityOnHand($line->getStockId(), $location, $date));
        }

        // Only book a transfer batch when something actually needs moving.
        $needsAdjustment = false;
        foreach ($lines as $line) {
            if (!$line->matches()) {
                $needsAdjustment = true;
                break;
            }
        }

        if ($needsAdjustment) {
            // Variance booking is location-scoped stock movement, not counting,
            // so it is delegated to ksf_FA_Warehouse where possible: inventory
            // then has a single writer for 0_stock_moves and serials cannot
            // diverge from it. This module keeps the inline path as a fallback
            // so counts still work when warehouse is inactive.
            $variances = array();

            foreach ($lines as $line) {
                if ($line->variance() !== 0.0) {
                    $variances[] = array(
                        'stock_id' => $line->getStockId(),
                        'variance' => $line->variance(),
                    );
                }
            }

            $result->setTransNo((string)$this->bookVariances($variances, $location, $date, $holdingTank, $result));
        }

        foreach ($lines as $line) {
            $this->repository->recordScan($line->getStockId(), $location, $line->getCountedQty());
            $this->repository->recordCountHistory($line->getStockId(), $location);
            $result->markRecorded($line->getStockId());
        }

        return $result;
    }

    /**
     * Summarize over/short for a cart without booking anything.
     *
     * @param CountCart $cart     Counted lines.
     * @param string    $location Location that was counted.
     * @param string    $date     Count date (Y-m-d).
     * @return CountSummary
     *
     * @BABOK Related: FR-IC-001-004
     * @since 1.0.0
     */
    public function summarize(CountCart $cart, string $location, string $date): CountSummary
    {
        $lines = $cart->lines();
        foreach ($lines as $line) {
            $line->setQoh($this->fa->getQuantityOnHand($line->getStockId(), $location, $date));
        }
        return CountSummary::fromLines($lines);
    }

    /**
     * Book variances for a counted location, preferring ksf_FA_Warehouse.
     *
     * Warehouse owns the HOLDING tank now (moved out of this module in the
     * 2026-10 relocation), so ask it first via hook_invoke_first -- a write
     * capability, because two modules booking stock for the same item must not
     * race. If no provider answers, the original inline booking runs unchanged so
     * counting still works with warehouse inactive.
     *
     * Either path records the adjustment on $result and publishes the same
     * quantity-changed events, so listeners (Square, Woocommerce) cannot tell
     * which one ran.
     *
     * @param array         $variances   Each ['stock_id' => string, 'variance' => float]
     * @param string        $location    Counted location.
     * @param string        $date        'Y-m-d'
     * @param string        $holdingTank HOLDING tank location code.
     * @param ProcessResult $result      Accumulates adjustments.
     * @return int Transfer number, or 0 when nothing was booked.
     */
    private function bookVariances(
        array $variances,
        string $location,
        string $date,
        string $holdingTank,
        ProcessResult $result
    ): int {
        $reply = null;

        if (function_exists('hook_invoke_first')) {
            $payload = array();
            $reply = hook_invoke_first('respondToCapabilityRequest', $payload, array(
                'request'      => 'move_inventory',
                'loc_code'     => $location,
                'on_date'      => $date,
                'variances'    => $variances,
                'holding_tank' => $holdingTank,
                'reference'    => 'INV',
            ));
        }

        if (is_array($reply) && isset($reply['adjustments']) && is_array($reply['adjustments'])) {
            foreach ($reply['adjustments'] as $adjustment) {
                $this->recordAdjustment(
                    $result,
                    $publisher = null,
                    (string)$adjustment['stock_id'],
                    (string)$adjustment['from'],
                    (string)$adjustment['to'],
                    (float)$adjustment['qty'],
                    (string)$adjustment['reason']
                );
            }

            return (int)($reply['trans_no'] ?? 0);
        }

        return $this->bookVariancesInline($variances, $location, $date, $holdingTank, $result);
    }

    /**
     * Original inline booking, retained as the fallback path.
     *
     * @param array         $variances
     * @param string        $location
     * @param string        $date
     * @param string        $holdingTank
     * @param ProcessResult $result
     * @return int
     */
    private function bookVariancesInline(
        array $variances,
        string $location,
        string $date,
        string $holdingTank,
        ProcessResult $result
    ): int {
        if (empty($variances)) {
            return 0;
        }

        $transNo = $this->fa->getNextTransNo(ST_LOCTRANSFER);
        $reference = 'INV-' . $date . '-' . $transNo;

        foreach ($variances as $variance) {
            $stockId = (string)$variance['stock_id'];
            $amount = (float)$variance['variance'];

            if ($amount > 0) {
                // Overage: excess belongs in the holding tank.
                $from = $location;
                $to = $holdingTank;
                $reason = 'over';
            } else {
                // Shortage: pull the missing quantity from the holding tank.
                $from = $holdingTank;
                $to = $location;
                $amount = abs($amount);
                $reason = 'short';
            }

            $this->fa->addStockTransferItem(
                $transNo,
                $stockId,
                $from,
                $to,
                $date,
                $reference,
                $amount
            );

            $this->recordAdjustment($result, null, $stockId, $from, $to, $amount, $reason);
        }

        return $transNo;
    }

    /**
     * Record one adjustment and notify listeners.
     *
     * @param ProcessResult $result
     * @param object|null   $unused Publisher slot, kept for signature symmetry.
     * @param string        $stockId
     * @param string        $from
     * @param string        $to
     * @param float         $qty
     * @param string        $reason 'over' or 'short'
     * @return void
     */
    private function recordAdjustment(
        ProcessResult $result,
        $unused,
        string $stockId,
        string $from,
        string $to,
        float $qty,
        string $reason
    ): void {
        $result->addAdjustment($stockId, $from, $to, $qty);

        // Quantity changed: notify listening modules (Square, Woocommerce).
        $this->publisher->publishUpdated(
            $stockId,
            array(
                'source'         => 'inventory_count',
                'adjustment_qty' => $qty,
                'direction'      => $reason,
                'from'           => $from,
                'to'             => $to,
            ),
            'module'
        );
    }

}
