<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Exception;

use Ksfraser\Exceptions\FrontAccounting\FAException;

/**
 * Thrown when an operation requires a location and none has been selected.
 *
 * Extends FAException directly because FAValidationException's shared
 * constructor contract (message first) does not fit validation-only errors.
 *
 * @BABOK Related: FR-IC-001-005
 *
 * @since 1.0.0
 */
class LocationNotSetException extends FAException
{
}
