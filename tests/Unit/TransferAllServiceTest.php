<?php
declare(strict_types=1);

/**
 * Unit tests for transferring all stock between locations.
 *
 * @BABOK Related: UT-IC-003-001-001
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Exception\LocationNotSetException;
use ksfraser\FrontAccounting\InventoryCount\Service\TransferAllService;
use ksfraser\FrontAccounting\InventoryCount\Tests\Fake\FakeFaApi;

class TransferAllServiceTest extends TestCase
{
    /**
     * All stocked items move from source to destination.
     *
     * @BABOK Related: UT-IC-003-001-001
     */
    public function testTransferAllMovesEveryStockedItem(): void
    {
        $fa = new FakeFaApi();
        $fa->stockAtLocation['FAIR'] = [
            'ITEM1' => 10.0,
            'ITEM2' => 3.0,
        ];
        $service = new TransferAllService($fa);

        $result = $service->transferAll('FAIR', 'STORE', '2026-08-22');

        $this->assertNotNull($result->getTransNo());
        $this->assertCount(2, $this->readAttributePublic($result));
        foreach ($result->getAdjustments() as $adj) {
            $this->assertSame('FAIR', $adj['from']);
            $this->assertSame('STORE', $adj['to']);
        }
        $this->assertCount(2, $fa->transfers);
    }

    /**
     * Empty source location results in a no-op success.
     *
     * @BABOK Related: UT-IC-003-001-002
     */
    public function testEmptyLocationIsNoOp(): void
    {
        $service = new TransferAllService(new FakeFaApi());

        $result = $service->transferAll('EMPTY', 'STORE', '2026-08-22');

        $this->assertCount(0, $result->getAdjustments());
    }

    /**
     * Same or missing locations are rejected.
     *
     * @BABOK Related: UT-IC-003-001-003
     */
    public function testSameLocationThrows(): void
    {
        $service = new TransferAllService(new FakeFaApi());

        $this->expectException(LocationNotSetException::class);
        $service->transferAll('STORE', 'STORE', '2026-08-22');
    }

    /**
     * Helper to expose adjustments count without reflection boilerplate.
     *
     * @param object $result ProcessResult.
     * @return array
     */
    protected function readAttributePublic(object $result): array
    {
        return $result->getAdjustments();
    }
}
