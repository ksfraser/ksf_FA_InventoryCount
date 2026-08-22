<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Service;

use ksfraser\FrontAccounting\InventoryCount\Domain\ProcessResult;
use ksfraser\FrontAccounting\InventoryCount\Exception\LocationNotSetException;
use ksfraser\FrontAccounting\InventoryCount\Fa\FaApiInterface;

/**
 * Transfers ALL stock from one location to another (or the holding tank).
 *
 * Used when decommissioning a temporary location (e.g. a trade-fair shop):
 * move everything back to a permanent location, then count later.
 *
 * @BABOK Related: FR-IC-003-001
 *
 * @since 1.0.0
 */
class TransferAllService
{
    /** @var FaApiInterface */
    protected $fa;

    /**
     * Constructor.
     *
     * @param FaApiInterface $fa FrontAccounting API wrapper.
     * @since 1.0.0
     */
    public function __construct(FaApiInterface $fa)
    {
        $this->fa = $fa;
    }

    /**
     * Move every stocked item from one location to another.
     *
     * @param string $fromLocation Source location code.
     * @param string $toLocation   Destination location code.
     * @param string $date         Document date (Y-m-d).
     * @return ProcessResult One adjustment entry per moved item.
     * @throws LocationNotSetException When either location is empty or equal.
     *
     * @since 1.0.0
     */
    public function transferAll(string $fromLocation, string $toLocation, string $date): ProcessResult
    {
        if ($fromLocation === '' || $toLocation === '') {
            throw new LocationNotSetException('Both source and destination locations are required.');
        }
        if ($fromLocation === $toLocation) {
            throw new LocationNotSetException('Source and destination locations must differ.');
        }

        $result = new ProcessResult();
        $stock = $this->fa->getStockAtLocation($fromLocation);
        if (count($stock) === 0) {
            return $result;
        }

        $transNo = $this->fa->getNextTransNo(ST_LOCTRANSFER);
        foreach ($stock as $stockId => $qoh) {
            if ($qoh == 0.0) {
                continue;
            }
            $this->fa->addStockTransferItem(
                $transNo,
                $stockId,
                $fromLocation,
                $toLocation,
                $date,
                'XFERALL-' . $date . '-' . $transNo,
                $qoh
            );
            $result->addAdjustment($stockId, $fromLocation, $toLocation, $qoh);
        }
        $result->setTransNo((string) $transNo);

        return $result;
    }
}
