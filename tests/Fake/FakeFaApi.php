<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Fake;

use ksfraser\FrontAccounting\InventoryCount\Fa\FaApiInterface;

/**
 * Deterministic in-memory FA API double for unit tests.
 *
 * @BABOK Related: UT-IC-001-001-001
 */
class FakeFaApi implements FaApiInterface
{
    /** @var array<string, float> "stockId|location" => qoh */
    public $qoh = [];

    /** @var array<int, array<string, mixed>> Recorded transfer calls. */
    public $transfers = [];

    /** @var int Next transaction number to hand out. */
    public $nextTransNo = 100;

    /** @var array<string, string[]> barcode => candidate stock ids */
    public $barcodeMap = [];

    /** @var array<string, array<string, float>> location => stockId => qty */
    public $stockAtLocation = [];

    /**
     * Seed QOH for an item/location pair.
     *
     * @param string $stockId  Item.
     * @param string $location Location.
     * @param float  $qty      Quantity.
     * @return void
     */
    public function seedQoh(string $stockId, string $location, float $qty): void
    {
        $this->qoh[$stockId . '|' . $location] = $qty;
    }

    /**
     * @inheritDoc
     */
    public function getQuantityOnHand(string $stockId, string $location, string $date): float
    {
        return $this->qoh[$stockId . '|' . $location] ?? 0.0;
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
        $this->transfers[] = compact('transNo', 'stockId', 'fromLoc', 'toLoc', 'date', 'reference', 'qty');
    }

    /**
     * @inheritDoc
     */
    public function getNextTransNo(int $transType): int
    {
        return $this->nextTransNo++;
    }

    /**
     * @inheritDoc
     */
    public function resolveBarcode(string $code): array
    {
        return $this->barcodeMap[$code] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getStockAtLocation(string $location): array
    {
        return $this->stockAtLocation[$location] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getLocationCodes(): array
    {
        return array_keys($this->stockAtLocation);
    }
}
