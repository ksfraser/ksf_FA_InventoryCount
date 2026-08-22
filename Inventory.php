<?php
/**
 * Inventory Taking - module page entry point.
 *
 * Thin FA page shell: sets up security/session, boots the Composer
 * autoloader and hands off to the namespaced page controller.
 *
 * @package ksf_FA_InventoryCount
 *
 * @BABOK Related: BR-IC-001
 */

$path_to_root = '../..';

$page_security = 'SA_ksf_FA_InventoryCountVIEW';
include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

$autoload = dirname(__FILE__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    display_error(_('ksf_FA_InventoryCount: vendor autoload missing. Run "composer install" in the module directory.'));
    end_page();
    exit;
}
require_once $autoload;

use ksfraser\FrontAccounting\InventoryCount\Ui\PageController;

$controller = PageController::create();
$controller->run();
