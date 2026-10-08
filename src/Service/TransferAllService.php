<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Service;

use ksfraser\FrontAccounting\InventoryCount\Domain\ProcessResult;
use ksfraser\FrontAccounting\InventoryCount\Exception\LocationNotSetException;
use ksfraser\FrontAccounting\InventoryCount\Fa\FaApiInterface;

/**
 * Moves ALL stock from one location to another.
 *
 * Used when decommissioning a temporary location (e.g. a trade-fair shop):
 * move everything back to a permanent location, then count later.
 *
 * Location movement is not counting, so the implementation now lives in
 * ksf_FA_Warehouse (TransferAllService there, exposed as the
 * `transfer_all_stock` capability). This class is retained as a thin facade so
 * FR-IC-003-001 keeps a single entry point and the existing callers and
 * requirement traceability stay intact.
 *
 * It delegates via hook_invoke_first -- a WRITE, so two modules must not race to
 * empty the same location -- and falls back to the original inline booking when
 * warehouse is inactive.
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

        $delegated = $this->delegateToWarehouse($fromLocation, $toLocation, $date);

        if ($delegated !== null) {
            return $delegated;
        }

        return $this->transferAllInline($fromLocation, $toLocation, $date);
    }

    /**
     * Ask ksf_FA_Warehouse to book the movement.
     *
     * @param string $fromLocation
     * @param string $toLocation
     * @param string $date
     * @return ProcessResult|null Null when warehouse does not answer.
     */
    private function delegateToWarehouse(string $fromLocation, string $toLocation, string $date): ?ProcessResult
    {
        if (!function_exists('hook_invoke_first')) {
            return null;
        }

        $payload = array();
        $reply = hook_invoke_first('respondToCapabilityRequest', $payload, array(
            'request'  => 'transfer_all_stock',
            'from_loc' => $fromLocation,
            'to_loc'   => $toLocation,
            'on_date'  => $date,
        ));

        if (!is_array($reply) || !isset($reply['adjustments']) || !is_array($reply['adjustments'])) {
            return null;
        }

        $result = new ProcessResult();

        foreach ($reply['adjustments'] as $adjustment) {
            $result->addAdjustment(
                (string)$adjustment['stock_id'],
                (string)$adjustment['from'],
                (string)$adjustment['to'],
                (float)$adjustment['qty']
            );
        }

        if (!empty($reply['trans_no'])) {
            $result->setTransNo((string)$reply['trans_no']);
        }

        return $result;
    }

    /**
     * Original inline booking, retained as the fallback path.
     *
     * @param string $fromLocation
     * @param string $toLocation
     * @param string $date
     * @return ProcessResult
     */
    private function transferAllInline(string $fromLocation, string $toLocation, string $date): ProcessResult
    {
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
