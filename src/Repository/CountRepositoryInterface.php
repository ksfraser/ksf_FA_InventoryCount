<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Repository;

/**
 * Persistence contract for scanned counts and count history.
 *
 * @since 1.0.0
 */
interface CountRepositoryInterface
{
    /**
     * Persist one scan row.
     *
     * @param string $stockId  Stock identifier.
     * @param string $location Location code.
     * @param float  $qty      Scanned quantity.
     * @return void
     *
     * @since 1.0.0
     */
    public function recordScan(string $stockId, string $location, float $qty): void;

    /**
     * Record (upsert) that an item was counted at a location now.
     *
     * @param string $stockId  Stock identifier.
     * @param string $location Location code.
     * @return void
     *
     * @BABOK Related: FR-IC-001-005
     * @since 1.0.0
     */
    public function recordCountHistory(string $stockId, string $location): void;

    /**
     * Last date the item/location pair was counted, or null.
     *
     * @param string $stockId  Stock identifier.
     * @param string $location Location code.
     * @return string|null Date (Y-m-d H:i:s) when known.
     *
     * @since 1.0.0
     */
    public function lastCountDate(string $stockId, string $location): ?string;
}
