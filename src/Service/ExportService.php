<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Service;

use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;

/**
 * Builds CSV output of a count cart for export/download.
 *
 * @BABOK Related: FR-IC-002-002
 *
 * @since 1.0.0
 */
class ExportService
{
    /**
     * Serialize cart lines to CSV.
     *
     * Columns: stock_id, barcode, counted_qty, qoh, variance.
     *
     * @param CountCart $cart Source cart.
     * @return string CSV content with header row.
     *
     * @since 1.0.0
     */
    public function toCsv(CountCart $cart): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['stock_id', 'barcode', 'counted_qty', 'qoh', 'variance']);
        foreach ($cart->lines() as $line) {
            fputcsv($out, [
                $line->getStockId(),
                $line->getBarcode(),
                $line->getCountedQty(),
                $line->getQoh(),
                $line->variance(),
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }
}
