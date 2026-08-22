<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Fake;

use ksfraser\FrontAccounting\InventoryCount\Repository\CountRepositoryInterface;

/**
 * In-memory repository double recording calls for assertions.
 *
 * @BABOK Related: UT-IC-001-005-001
 */
class FakeCountRepository implements CountRepositoryInterface
{
    /** @var array<int, array{stock_id:string, location:string, qty:float}> */
    public $scans = [];

    /** @var array<int, array{stock_id:string, location:string}> */
    public $history = [];

    /** @var array<string, string> "stock|loc" => date */
    public $historyDates = [];

    /**
     * @inheritDoc
     */
    public function recordScan(string $stockId, string $location, float $qty): void
    {
        $this->scans[] = ['stock_id' => $stockId, 'location' => $location, 'qty' => $qty];
    }

    /**
     * @inheritDoc
     */
    public function recordCountHistory(string $stockId, string $location): void
    {
        $this->history[] = ['stock_id' => $stockId, 'location' => $location];
        $this->historyDates[$stockId . '|' . $location] = '2026-08-22 12:00:00';
    }

    /**
     * @inheritDoc
     */
    public function lastCountDate(string $stockId, string $location): ?string
    {
        return $this->historyDates[$stockId . '|' . $location] ?? null;
    }
}
