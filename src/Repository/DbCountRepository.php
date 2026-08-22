<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Repository;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * DB-backed count repository using the shared ModulesDAO adapter so that
 * persistence stays testable and FA-agnostic.
 *
 * @BABOK Related: FR-IC-004-002
 *
 * @since 1.0.0
 */
class DbCountRepository implements CountRepositoryInterface
{
    /** @var DbAdapterInterface */
    protected $db;

    /**
     * Constructor.
     *
     * @param DbAdapterInterface $db Database adapter (FA dialect).
     * @since 1.0.0
     */
    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Fully-qualified (prefixed) table name.
     *
     * @param string $name Unprefixed table name.
     * @return string
     * @since 1.0.0
     */
    protected function table(string $name): string
    {
        return $this->db->getTablePrefix() . $name;
    }

    /**
     * @inheritDoc
     */
    public function recordScan(string $stockId, string $location, float $qty): void
    {
        $this->db->execute(
            'INSERT INTO ' . $this->table('ksf_inventory_scanned')
            . ' (stock_id, location, qty) VALUES (:stock_id, :location, :qty)',
            [
                'stock_id' => $stockId,
                'location' => $location,
                'qty'      => $qty,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function recordCountHistory(string $stockId, string $location): void
    {
        $this->db->execute(
            'REPLACE INTO ' . $this->table('ksf_inventory_count_history')
            . ' (stock_id, location, inventory_date) VALUES (:stock_id, :location, NOW())',
            [
                'stock_id' => $stockId,
                'location' => $location,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function lastCountDate(string $stockId, string $location): ?string
    {
        $rows = $this->db->query(
            'SELECT inventory_date FROM ' . $this->table('ksf_inventory_count_history')
            . ' WHERE stock_id = :stock_id AND location = :location',
            [
                'stock_id' => $stockId,
                'location' => $location,
            ]
        );
        if (count($rows) === 0) {
            return null;
        }
        return (string) $rows[0]['inventory_date'];
    }
}
