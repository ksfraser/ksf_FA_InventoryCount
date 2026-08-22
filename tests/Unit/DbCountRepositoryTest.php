<?php
declare(strict_types=1);

/**
 * Unit tests for the DB count repository (via in-memory adapter double).
 *
 * @BABOK Related: UT-IC-004-002-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Repository\DbCountRepository;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class DbCountRepositoryTest extends TestCase
{
    /**
     * Build a recording adapter double.
     *
     * @return DbAdapterInterface
     */
    protected function makeAdapter(): DbAdapterInterface
    {
        return new class implements DbAdapterInterface {
            /** @var string[] */
            public $statements = [];
            /** @var array<int, array<string, mixed>> */
            public $params = [];
            private string $prefix = '0_';

            public function getDialect(): string
            {
                return 'mysql';
            }
            public function getTablePrefix(): string
            {
                return $this->prefix;
            }
            public function escape(string $value): string
            {
                return $value;
            }
            public function query(string $sql, array $params = []): array
            {
                if (strpos($sql, 'SELECT inventory_date') !== false) {
                    return [['inventory_date' => '2026-01-01 09:00:00']];
                }
                return [];
            }
            public function execute(string $sql, array $params = []): int
            {
                $this->statements[] = $sql;
                $this->params[] = $params;
                return 1;
            }
            public function lastInsertId(): ?int
            {
                return 1;
            }
        };
    }

    /**
     * recordScan inserts into the prefixed scanned table with params.
     *
     * @BABOK Related: UT-IC-004-002-001
     */
    public function testRecordScanInsertsPrefixedRow(): void
    {
        $adapter = $this->makeAdapter();
        $repo = new DbCountRepository($adapter);

        $repo->recordScan('ITEM1', 'STORE', 5.0);

        $this->assertStringContainsString('INSERT INTO 0_ksf_inventory_scanned', $adapter->statements[0]);
        $this->assertSame(['stock_id' => 'ITEM1', 'location' => 'STORE', 'qty' => 5.0], $adapter->params[0]);
    }

    /**
     * recordCountHistory upserts into the history table.
     *
     * @BABOK Related: UT-IC-004-002-002
     */
    public function testRecordHistoryUpserts(): void
    {
        $adapter = $this->makeAdapter();
        $repo = new DbCountRepository($adapter);

        $repo->recordCountHistory('ITEM1', 'STORE');

        $this->assertStringContainsString('REPLACE INTO 0_ksf_inventory_count_history', $adapter->statements[0]);
    }

    /**
     * lastCountDate returns null when no rows.
     *
     * @BABOK Related: UT-IC-004-002-003
     */
    public function testLastCountDateNullWhenMissing(): void
    {
        $adapter = new class implements DbAdapterInterface {
            private string $prefix = '0_';
            public function getDialect(): string
            {
                return 'mysql';
            }
            public function getTablePrefix(): string
            {
                return $this->prefix;
            }
            public function escape(string $value): string
            {
                return $value;
            }
            public function query(string $sql, array $params = []): array
            {
                return [];
            }
            public function execute(string $sql, array $params = []): int
            {
                return 0;
            }
            public function lastInsertId(): ?int
            {
                return null;
            }
        };
        $repo = new DbCountRepository($adapter);

        $this->assertNull($repo->lastCountDate('MISSING', 'STORE'));
    }

    /**
     * lastCountDate returns the stored date when present.
     *
     * @BABOK Related: UT-IC-004-002-004
     */
    public function testLastCountDateReturnsStoredValue(): void
    {
        $repo = new DbCountRepository($this->makeAdapter());

        $this->assertSame('2026-01-01 09:00:00', $repo->lastCountDate('ITEM1', 'STORE'));
    }
}
