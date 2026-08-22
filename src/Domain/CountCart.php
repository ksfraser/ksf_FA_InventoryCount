<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Domain;

/**
 * In-memory cart of counted lines for a count session.
 *
 * Replaces the legacy SESSION['InventoryItems'] handling with a plain
 * aggregate object that can be serialized into the session by the UI layer.
 * No inheritance from legacy generic_interface - composition only.
 *
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: BR-IC-001
 *
 * @since 1.0.0
 */
class CountCart
{
    /** @var array<string, CountLine> Lines keyed by stock id. */
    protected $lines = [];

    /**
     * Add a scanned item, incrementing quantity when already present.
     *
     * @param string $stockId Master stock identifier.
     * @param float  $qty     Quantity to add (default 1 for a barcode scan).
     * @param string $barcode Scanned code.
     * @return void
     *
     * @BABOK Related: FR-IC-001-001
     * @since 1.0.0
     */
    public function addScan(string $stockId, float $qty = 1.0, string $barcode = ''): void
    {
        if ($this->has($stockId)) {
            $line = $this->get($stockId);
            $line->setCountedQty($line->getCountedQty() + $qty);
            return;
        }
        $line = new CountLine($stockId, $qty, 0.0, $barcode);
        $this->lines[$stockId] = $line;
    }

    /**
     * Whether a stock id is already in the cart.
     *
     * @param string $stockId Stock identifier.
     * @return bool
     *
     * @since 1.0.0
     */
    public function has(string $stockId): bool
    {
        return isset($this->lines[$stockId]);
    }

    /**
     * Fetch a line or null.
     *
     * @param string $stockId Stock identifier.
     * @return CountLine|null
     *
     * @since 1.0.0
     */
    public function get(string $stockId): ?CountLine
    {
        return $this->lines[$stockId] ?? null;
    }

    /**
     * Find the cart line matching any of the given candidate codes.
     *
     * Legacy behaviour: a scan may arrive as stock_id, foreign/item code or
     * barcode; callers resolve candidates first and we match against all,
     * including barcodes recorded on the lines.
     *
     * @param string[] $codes Candidate identifiers.
     * @return CountLine|null
     *
     * @BABOK Related: FR-IC-001-002
     * @since 1.0.0
     */
    public function findByCodes(array $codes): ?CountLine
    {
        foreach ($codes as $code) {
            if ($this->has($code)) {
                return $this->get($code);
            }
        }
        foreach ($this->lines as $line) {
            if (in_array($line->getBarcode(), $codes, true)) {
                return $line;
            }
        }
        return null;
    }

    /**
     * Update the counted quantity of an existing line.
     *
     * @param string $stockId Stock identifier.
     * @param float  $qty     New counted quantity.
     * @return bool False when the line does not exist.
     *
     * @BABOK Related: FR-IC-001-003
     * @since 1.0.0
     */
    public function updateQty(string $stockId, float $qty): bool
    {
        if (!$this->has($stockId)) {
            return false;
        }
        $this->get($stockId)->setCountedQty($qty);
        return true;
    }

    /**
     * Remove a line from the cart.
     *
     * @param string $stockId Stock identifier.
     * @return bool False when the line did not exist.
     *
     * @BABOK Related: FR-IC-001-003
     * @since 1.0.0
     */
    public function delete(string $stockId): bool
    {
        if (!$this->has($stockId)) {
            return false;
        }
        unset($this->lines[$stockId]);
        return true;
    }

    /**
     * All lines in insertion order.
     *
     * @return CountLine[]
     *
     * @since 1.0.0
     */
    public function lines(): array
    {
        return array_values($this->lines);
    }

    /**
     * Number of distinct lines.
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function count(): int
    {
        return count($this->lines);
    }

    /**
     * Remove all lines (e.g. "clear cart" between submissions).
     *
     * @return void
     *
     * @BABOK Related: FR-IC-001-003
     * @since 1.0.0
     */
    public function clear(): void
    {
        $this->lines = [];
    }
}
