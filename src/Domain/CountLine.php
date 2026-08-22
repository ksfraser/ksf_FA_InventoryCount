<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Domain;

/**
 * A single counted stock item line within a count session.
 *
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-IC-001-001
 *
 * @since 1.0.0
 */
class CountLine
{
    /** @var string */
    protected $stockId;

    /** @var string Scanned barcode / item code as entered (may differ from master SKU). */
    protected $barcode;

    /** @var float Quantity counted on the shelf. */
    protected $countedQty;

    /** @var float Quantity on hand per FrontAccounting at time of count. */
    protected $qoh;

    /**
     * Constructor.
     *
     * @param string $stockId    Master stock identifier.
     * @param float  $countedQty Counted quantity.
     * @param float  $qoh        System quantity on hand.
     * @param string $barcode    Original scanned code, defaults to stock id.
     *
     * @since 1.0.0
     */
    public function __construct(
        string $stockId,
        float $countedQty = 0.0,
        float $qoh = 0.0,
        string $barcode = ''
    ) {
        $this->stockId = $stockId;
        $this->countedQty = $countedQty;
        $this->qoh = $qoh;
        $this->barcode = $barcode !== '' ? $barcode : $stockId;
    }

    /**
     * Master stock identifier.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getStockId(): string
    {
        return $this->stockId;
    }

    /**
     * Barcode as originally scanned.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getBarcode(): string
    {
        return $this->barcode;
    }

    /**
     * Set the counted quantity.
     *
     * @param float $qty Counted quantity.
     * @return void
     *
     * @since 1.0.0
     */
    public function setCountedQty(float $qty): void
    {
        $this->countedQty = $qty;
    }

    /**
     * Quantity counted.
     *
     * @return float
     *
     * @since 1.0.0
     */
    public function getCountedQty(): float
    {
        return $this->countedQty;
    }

    /**
     * Refresh system quantity on hand (e.g. before processing).
     *
     * @param float $qoh Current system QOH.
     * @return void
     *
     * @since 1.0.0
     */
    public function setQoh(float $qoh): void
    {
        $this->qoh = $qoh;
    }

    /**
     * System quantity on hand at count time.
     *
     * @return float
     *
     * @since 1.0.0
     */
    public function getQoh(): float
    {
        return $this->qoh;
    }

    /**
     * Variance between count and system QOH.
     *
     * Positive means overage (more on shelf than system), negative means short.
     *
     * @return float
     *
     * @BABOK Related: FR-IC-001-004
     * @since 1.0.0
     */
    public function variance(): float
    {
        return round($this->countedQty - $this->qoh, 6);
    }

    /**
     * True when the count matches system QOH (no adjustment needed).
     *
     * @return bool
     *
     * @BABOK Related: FR-IC-001-004
     * @since 1.0.0
     */
    public function matches(): bool
    {
        return $this->variance() === 0.0;
    }
}
