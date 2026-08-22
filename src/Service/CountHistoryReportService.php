<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Service;

use DateTimeImmutable;
use ksfraser\FrontAccounting\InventoryCount\Repository\CountRepositoryInterface;

/**
 * Builds count-history reports: last counted dates and overdue items.
 *
 * @BABOK Related: FR-IC-005-001
 *
 * @since 1.0.0
 */
class CountHistoryReportService
{
    /** @var CountRepositoryInterface */
    protected $repository;

    /**
     * Constructor.
     *
     * @param CountRepositoryInterface $repository Count persistence.
     * @since 1.0.0
     */
    public function __construct(CountRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Stocked items at a location not counted within the given days.
     *
     * @param array<string, float> $stockAtLocation stock_id => QOH map (from FaApi).
     * @param string               $location        Location code.
     * @param int                  $days            Overdue threshold in days.
     * @param string               $today           Reference date (Y-m-d); defaults to now.
     * @return array<int, array{stock_id:string, last_counted:?string, days_since:int}>
     *
     * @BABOK Related: FR-IC-005-001
     * @since 1.0.0
     */
    public function itemsNotCountedWithin(
        array $stockAtLocation,
        string $location,
        int $days,
        ?string $today = null
    ): array {
        $now = new DateTimeImmutable($today ?? 'now');
        $overdue = [];

        foreach ($stockAtLocation as $stockId => $qoh) {
            $last = $this->repository->lastCountDate($stockId, $location);
            if ($last !== null) {
                $lastDate = new DateTimeImmutable(substr($last, 0, 10));
                $daysSince = (int) $now->diff($lastDate)->format('%a');
                if ($daysSince <= $days) {
                    continue;
                }
            } else {
                $daysSince = null;
            }
            $overdue[] = [
                'stock_id'     => $stockId,
                'last_counted' => $last ?? 'never',
                'days_since'   => $daysSince,
            ];
        }

        return $overdue;
    }

    /**
     * Last counted date for every stocked item at a location.
     *
     * @param array<string, float> $stockAtLocation stock_id => QOH map.
     * @param string               $location        Location code.
     * @return array<int, array{stock_id:string, last_counted:?string}>
     *
     * @BABOK Related: FR-IC-005-002
     * @since 1.0.0
     */
    public function lastCountedDates(array $stockAtLocation, string $location): array
    {
        $report = [];
        foreach ($stockAtLocation as $stockId => $qoh) {
            $report[] = [
                'stock_id'     => $stockId,
                'last_counted' => $this->repository->lastCountDate($stockId, $location),
            ];
        }
        return $report;
    }
}
