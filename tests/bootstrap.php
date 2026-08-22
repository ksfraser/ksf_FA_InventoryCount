<?php
/**
 * PHPUnit bootstrap: load Composer autoloader and provide the minimal
 * FrontAccounting environment (constants) needed by unit tests.
 */

require __DIR__ . '/../vendor/autoload.php';

if (!defined('ST_LOCTRANSFER')) {
    // FrontAccounting transaction type for location transfers.
    define('ST_LOCTRANSFER', 16);
}
