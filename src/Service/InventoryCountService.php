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
                $transNo = $this->fa->getNextTransNo(ST_LOCTRANSFER);
                $reference = 'INV-' . $date . '-' . $transNo;

                foreach ($lines as $line) {
                    $variance = $line->variance();
                    if ($variance === 0.0) {
                        continue;
                    }
                    if ($variance > 0) {
                        // Overage: excess belongs in the holding tank.
                        $this->fa->addStockTransferItem(
                            $transNo,
                            $line->getStockId(),
                            $location,
                            $holdingTank,
                            $date,
                            $reference,
                            $variance
                        );
                        $result->addAdjustment($line->getStockId(), $location, $holdingTank, $variance);
                    } else {
                        // Shortage: pull the missing quantity from the holding tank.
                        $shortQty = abs($variance);
                        $this->fa->addStockTransferItem(
                            $transNo,
                            $line->getStockId(),
                            $holdingTank,
                            $location,
                            $date,
                            $reference,
                            $shortQty
                        );
                        $result->addAdjustment(
                            $line->getStockId(),
                            $holdingTank,
                            $location,
                            $shortQty
                        );
                    }
                    // Quantity changed: notify listening modules (Square, Woocommerce).
                    $this->publisher->publishUpdated(
                        $line->getStockId(),
                        array(
                            'source'         => 'inventory_count',
                            'adjustment_qty' => abs($variance),
                            'direction'      => $variance > 0 ? 'over' : 'short',
                            'from'           => $variance > 0 ? $location : $holdingTank,
                            'to'             => $variance > 0 ? $holdingTank : $location,
                        ),
                        'module'
                    );
                }
                $result->setTransNo((string) $transNo);
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
}
