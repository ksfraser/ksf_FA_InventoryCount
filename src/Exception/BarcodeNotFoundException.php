<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Exception;

use Ksfraser\Exceptions\FrontAccounting\FAEntityNotFoundException;

/**
 * Thrown when a scanned barcode cannot be resolved to a stock item.
 *
 * @BABOK Related: FR-IC-001-002
 *
 * @since 1.0.0
 */
class BarcodeNotFoundException extends FAEntityNotFoundException
{
    /**
     * Constructor.
     *
     * @param string $barcode The unresolvable scanned code.
     * @since 1.0.0
     */
    public function __construct(string $barcode)
    {
        parent::__construct('stock_item', $barcode, 'ksf_FA_InventoryCount');
    }
}
