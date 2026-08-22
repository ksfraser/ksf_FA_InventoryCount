<?php
declare(strict_types=1);

/**
 * Unit tests for the over/short count summary.
 *
 * @BABOK Related: UT-IC-001-004-004
 */

namespace ksfraser\FrontAccounting\InventoryCount\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountLine;
use ksfraser\FrontAccounting\InventoryCount\Domain\CountSummary;

class CountSummaryTest extends TestCase
{
    /**
     * @BABOK Related: UT-IC-001-004-004
     */
    public function testFromLinesClassifiesOverShortMatched(): void
    {
        $summary = CountSummary::fromLines([
            new CountLine('A', 12.0, 10.0), // over by 2
            new CountLine('B', 7.0, 10.0),  // short by 3
            new CountLine('C', 5.0, 5.0),   // match
        ]);

        $this->assertSame(3, $summary->getLinesCounted());
        $this->assertSame(1, $summary->getLinesMatched());
        $this->assertSame(1, $summary->getLinesOver());
        $this->assertSame(1, $summary->getLinesShort());
        $this->assertSame(2.0, $summary->getOverQty());
        $this->assertSame(3.0, $summary->getShortQty());
    }

    /**
     * @BABOK Related: UT-IC-001-004-004
     */
    public function testEmptyCartSummary(): void
    {
        $summary = CountSummary::fromLines([]);

        $this->assertSame(0, $summary->getLinesCounted());
        $this->assertSame(0.0, $summary->getOverQty());
        $this->assertSame(0.0, $summary->getShortQty());
    }

    /**
     * @BABOK Related: UT-IC-001-004-004
     */
    public function testMultipleOversAccumulate(): void
    {
        $summary = CountSummary::fromLines([
            new CountLine('A', 15.0, 10.0),
            new CountLine('B', 20.0, 20.0),
            new CountLine('C', 9.0, 8.0),
        ]);

        $this->assertSame(2, $summary->getLinesOver());
        $this->assertEqualsWithDelta(6.0, $summary->getOverQty(), 0.000001);
    }
}
