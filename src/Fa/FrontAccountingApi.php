<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Fa;

/**
 * Default FaApiInterface implementation delegating to global FrontAccounting
 * functions. Kept deliberately thin - all logic lives in services.
 *
 * @since 1.0.0
 */
class FrontAccountingApi implements FaApiInterface
{
    /**
     * @inheritDoc
     */
    public function getQuantityOnHand(string $stockId, string $location, string $date): float
    {
        return (float) get_qoh_on_date($stockId, $location, $date);
    }

    /**
     * @inheritDoc
     */
    public function addStockTransferItem(
        int $transNo,
        string $stockId,
        string $fromLoc,
        string $toLoc,
        string $date,
        string $reference,
        float $qty
    ): void {
        add_stock_transfer_item(
            $transNo,
            $stockId,
            $fromLoc,
            $toLoc,
            $date,
            ST_LOCTRANSFER,
            $reference,
            $qty
        );
    }

    /**
     * @inheritDoc
     */
    public function getNextTransNo(int $transType): int
    {
        return (int) get_next_trans_no($transType);
    }

    /**
     * @inheritDoc
     */
    public function resolveBarcode(string $code): array
    {
        $candidates = [];

        // Exact stock id match.
        $result = db_query(
            "SELECT stock_id FROM " . TB_PREF . "stock_master WHERE stock_id = " . db_escape($code),
            'barcode lookup by stock_id failed'
        );
        if ($row = db_fetch($result)) {
            $candidates[] = $row['stock_id'];
            return $candidates;
        }

        // Foreign / item code mapping (FA item_codes table covers UPCs).
        $result = db_query(
            "SELECT stock_id FROM " . TB_PREF . "item_codes WHERE code = " . db_escape($code),
            'barcode lookup by item code failed'
        );
        while ($row = db_fetch($result)) {
            $candidates[] = $row['stock_id'];
        }
        if (count($candidates) > 0) {
            return array_values(array_unique($candidates));
        }

        // Loose containment as a last resort.
        $result = db_query(
            "SELECT stock_id FROM " . TB_PREF . "stock_master WHERE stock_id LIKE '%"
            . db_escape($code) . "%'",
            'barcode loose lookup failed'
        );
        while ($row = db_fetch($result)) {
            $candidates[] = $row['stock_id'];
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @inheritDoc
     */
    public function getStockAtLocation(string $location): array
    {
        $map = [];
        $result = db_query(
            "SELECT stock_id, SUM(qty) AS qoh FROM " . TB_PREF . "stock_moves"
            . " WHERE loc_code = " . db_escape($location)
            . " GROUP BY stock_id HAVING SUM(qty) <> 0",
            'stock at location query failed'
        );
        while ($row = db_fetch($result)) {
            $map[$row['stock_id']] = (float) $row['qoh'];
        }
        return $map;
    }

    /**
     * @inheritDoc
     */
    public function getLocationCodes(): array
    {
        $codes = [];
        $result = db_query(
            "SELECT loc_code FROM " . TB_PREF . "locations",
            'location list query failed'
        );
        while ($row = db_fetch($result)) {
            $codes[] = $row['loc_code'];
        }
        return $codes;
    }
}
