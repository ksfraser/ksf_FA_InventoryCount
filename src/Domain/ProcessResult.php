<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Domain;

/**
 * Result of processing a count: adjustments created and history recorded.
 *
 * @BABOK Related: FR-IC-001-005
 *
 * @since 1.0.0
 */
class ProcessResult
{
    /** @var string|null FA transfer number for the adjustment batch, if any. */
    protected $transNo;

    /** @var array<int, array{stock_id:string, from:string, to:string, qty:float}> */
    protected $adjustments = [];

    /** @var string[] Stock ids whose last-count date was recorded. */
    protected $recorded = [];

    /**
     * Attach the FA transaction number.
     *
     * @param string $transNo Transfer transaction number.
     * @return void
     * @since 1.0.0
     */
    public function setTransNo(string $transNo): void
    {
        $this->transNo = $transNo;
    }

    /**
     * @return string|null
     * @since 1.0.0
     */
    public function getTransNo(): ?string
    {
        return $this->transNo;
    }

    /**
     * Record one adjustment movement.
     *
     * @param string $stockId Item.
     * @param string $from    Source location.
     * @param string $to      Destination location.
     * @param float  $qty     Quantity moved.
     * @return void
     * @since 1.0.0
     */
    public function addAdjustment(string $stockId, string $from, string $to, float $qty): void
    {
        $this->adjustments[] = [
            'stock_id' => $stockId,
            'from'     => $from,
            'to'       => $to,
            'qty'      => $qty,
        ];
    }

    /**
     * All adjustments performed.
     *
     * @return array<int, array{stock_id:string, from:string, to:string, qty:float}>
     * @since 1.0.0
     */
    public function getAdjustments(): array
    {
        return $this->adjustments;
    }

    /**
     * Mark a stock id's last-count date as recorded.
     *
     * @param string $stockId Item.
     * @return void
     * @since 1.0.0
     */
    public function markRecorded(string $stockId): void
    {
        $this->recorded[] = $stockId;
    }

    /**
     * Stock ids recorded in the count history table.
     *
     * @return string[]
     * @since 1.0.0
     */
    public function getRecorded(): array
    {
        return $this->recorded;
    }
}
