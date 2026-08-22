<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Domain;

/**
 * Immutable summary of an over/short analysis for a processed count.
 *
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-IC-001-004
 *
 * @since 1.0.0
 */
class CountSummary
{
    /** @var int */
    protected $linesCounted;

    /** @var int Lines whose count matched system QOH. */
    protected $linesMatched;

    /** @var int Lines over system QOH. */
    protected $linesOver;

    /** @var int Lines short of system QOH. */
    protected $linesShort;

    /** @var float Total overage quantity. */
    protected $overQty;

    /** @var float Total shortage quantity (positive number). */
    protected $shortQty;

    /**
     * Derive a summary from a set of count lines with QOH populated.
     *
     * @param CountLine[] $lines Counted lines.
     * @return self
     *
     * @since 1.0.0
     */
    public static function fromLines(array $lines): self
    {
        $summary = new self();
        $summary->linesCounted = count($lines);
        $summary->linesMatched = 0;
        $summary->linesOver = 0;
        $summary->linesShort = 0;
        $summary->overQty = 0.0;
        $summary->shortQty = 0.0;

        foreach ($lines as $line) {
            if ($line->matches()) {
                $summary->linesMatched++;
                continue;
            }
            if ($line->variance() > 0) {
                $summary->linesOver++;
                $summary->overQty += $line->variance();
            } else {
                $summary->linesShort++;
                $summary->shortQty += abs($line->variance());
            }
        }

        return $summary;
    }

    /**
     * @return int
     * @since 1.0.0
     */
    public function getLinesCounted(): int
    {
        return $this->linesCounted;
    }

    /**
     * @return int
     * @since 1.0.0
     */
    public function getLinesMatched(): int
    {
        return $this->linesMatched;
    }

    /**
     * @return int
     * @since 1.0.0
     */
    public function getLinesOver(): int
    {
        return $this->linesOver;
    }

    /**
     * @return int
     * @since 1.0.0
     */
    public function getLinesShort(): int
    {
        return $this->linesShort;
    }

    /**
     * @return float
     * @since 1.0.0
     */
    public function getOverQty(): float
    {
        return $this->overQty;
    }

    /**
     * @return float
     * @since 1.0.0
     */
    public function getShortQty(): float
    {
        return $this->shortQty;
    }
}
