<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Fa;

/**
 * Thin contract over the FrontAccounting API used by the inventory count
 * module. Isolating these calls lets the domain services be unit tested
 * without a running FA instance.
 *
 * @UML Note: Class diagram in ProjectDocs/UML.md
 *
 * @since 1.0.0
 */
interface FaApiInterface
{
    /**
     * Quantity on hand of an item at a location on a date.
     *
     * @param string $stockId  Stock identifier.
     * @param string $location Location code.
     * @param string $date     Document date (Y-m-d).
     * @return float
     *
     * @BABOK Related: FR-IC-001-004
     * @since 1.0.0
     */
    public function getQuantityOnHand(string $stockId, string $location, string $date): float;

    /**
     * Create a stock transfer of one item between locations.
     *
     * @param int    $transNo   Transaction number to book under.
     * @param string $stockId   Stock identifier.
     * @param string $fromLoc   Source location.
     * @param string $toLoc     Destination location.
     * @param string $date      Document date.
     * @param string $reference Reference string.
     * @param float  $qty       Quantity to move.
     * @return void
     *
     * @BABOK Related: FR-IC-001-005
     * @since 1.0.0
     */
    public function addStockTransferItem(
        int $transNo,
        string $stockId,
        string $fromLoc,
        string $toLoc,
        string $date,
        string $reference,
        float $qty
    ): void;

    /**
     * Next transaction number for a transaction type.
     *
     * @param int $transType FA transaction type constant (e.g. ST_LOCTRANSFER).
     * @return int
     *
     * @since 1.0.0
     */
    public function getNextTransNo(int $transType): int;

    /**
     * Resolve a scanned barcode to candidate master stock ids.
     *
     * Candidates are checked in order: exact stock_id, item/foreign code
     * mappings, then any stock id containing the scan.
     *
     * @param string $code Scanned code.
     * @return string[] Master stock ids, empty when unresolvable.
     *
     * @BABOK Related: FR-IC-001-002
     * @since 1.0.0
     */
    public function resolveBarcode(string $code): array;

    /**
     * All stock ids with quantity on hand at a location.
     *
     * @param string $location Location code.
     * @return array<string, float> stock_id => QOH map.
     *
     * @BABOK Related: FR-IC-003-001
     * @since 1.0.0
     */
    public function getStockAtLocation(string $location): array;

    /**
     * All valid location codes.
     *
     * @return string[]
     *
     * @BABOK Related: FR-IC-004-001
     * @since 1.0.0
     */
    public function getLocationCodes(): array;
}
