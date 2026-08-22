<?php
declare(strict_types=1);

/**
 * Unit tests for count-history reports (FR-IC-005-001 / FR-IC-002).
 *
 * @BABOK Related: UT-IC-005-001-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Repository\DbCountRepository;
use ksfraser\FrontAccounting\InventoryCount\Service\CountHistoryReportService;
use ksfraser\FrontAccounting\InventoryCount\Tests\Fake\FakeCountRepository;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class CountHistoryReportServiceTest extends TestCase
{
    /** @var FakeCountRepository */
    protected $repo;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->repo = new FakeCountRepository();
    }

    /**
     * Helper: seed history rows directly into the fake.
     *
     * @param string $stockId Item.
     * @param string $location Location.
     * @param string $date Last count date.
     * @return void
     */
    protected function seedHistory(string $stockId, string $location, string $date): void
    {
        $this->repo->historyDates[$stockId . '|' . $location] = $date;
    }

    /**
     * Items never counted are reported as overdue.
     *
     * @BABOK Related: UT-IC-005-001-001
     */
    public function testNeverCountedItemsAreOverdue(): void
    {
        $service = new CountHistoryReportService($this->repo);

        $overdue = $service->itemsNotCountedWithin(
            ['ITEM1' => 5.0, 'ITEM2' => 3.0],
            'STORE',
            7,
            '2026-08-22'
        );

        $this->assertCount(2, $overdue);
        $this->assertSame('never', $overdue[0]['last_counted']);
    }

    /**
     * Items counted within the window are excluded; older ones included,
     * with days-since computed.
     *
     * @BABOK Related: UT-IC-005-001-002
     */
    public function testOverdueFilteringByDays(): void
    {
        $this->seedHistory('FRESH', 'STORE', '2026-08-20'); // 2 days ago
        $this->seedHistory('STALE', 'STORE', '2026-08-01'); // 21 days ago
        $service = new CountHistoryReportService($this->repo);

        $overdue = $service->itemsNotCountedWithin(
            ['FRESH' => 1.0, 'STALE' => 2.0],
            'STORE',
            7,
            '2026-08-22'
        );

        $this->assertCount(1, $overdue);
        $this->assertSame('STALE', $overdue[0]['stock_id']);
        $this->assertSame(21, $overdue[0]['days_since']);
    }

    /**
     * Zero stocked items yields an empty report.
     *
     * @BABOK Related: UT-IC-005-001-003
     */
    public function testEmptyLocationGivesEmptyReport(): void
    {
        $service = new CountHistoryReportService($this->repo);

        $this->assertSame([], $service->itemsNotCountedWithin([], 'STORE', 7, '2026-08-22'));
    }

    /**
     * Last-counted report returns dates for every stocked item.
     *
     * @BABOK Related: UT-IC-005-002-001
     */
    public function testLastCountedDatesCoversAllStock(): void
    {
        $this->seedHistory('A', 'STORE', '2026-07-01 10:00:00');
        $service = new CountHistoryReportService($this->repo);

        $report = $service->lastCountedDates(['A' => 4.0, 'B' => 1.0], 'STORE');

        $this->assertSame('2026-07-01 10:00:00', $report[0]['last_counted']);
        $this->assertNull($report[1]['last_counted']);
    }
}
