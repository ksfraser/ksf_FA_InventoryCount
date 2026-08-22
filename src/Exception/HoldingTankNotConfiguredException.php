<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Exception;

use Ksfraser\Exceptions\FrontAccounting\FAConfigurationException;

/**
 * Thrown when processing a count that requires the HOLDING tank location
 * but the module configuration value is empty or unset.
 *
 * @BABOK Related: FR-IC-004-001
 *
 * @since 1.0.0
 */
class HoldingTankNotConfiguredException extends FAConfigurationException
{
    /**
     * Constructor.
     *
     * @param string $message Human readable explanation.
     * @since 1.0.0
     */
    public function __construct(string $message = 'The HOLDING tank location is not configured. Set it in module Configuration.')
    {
        parent::__construct('inv_count_holdtank', $message, 'ksf_FA_InventoryCount');
    }
}
