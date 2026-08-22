<?php
/**
 * KSF FrontAccounting Inventory Count Module Hooks.
 *
 * Standard KSF FA module hooks:
 * - Menu entry under Items & Inventory ("Inventory Taking").
 * - Security section SS_ksf_FA_InventoryCount with VIEW/MANAGE areas.
 * - Schema installed from sql/install.sql on activation (0_ prefixed tables).
 *
 * @package ksf_FA_InventoryCount
 * @version 2.4.19-0
 */

define('SS_ksf_FA_InventoryCount', 146 << 8);

/**
 * Hooks for the Inventory Count module.
 *
 * Composer autoloading is done lazily inside hook methods so that FA does
 * not fatal if vendor/ has not been installed yet.
 */
class hooks_ksf_FA_InventoryCount extends hooks
{
    /** @var string Module directory name, matches FA modules/<name>. */
    var $module_name = 'ksf_FA_InventoryCount';

    /** @var string Module version (FA target - release). */
    var $version = '2.4.19-0';

    /**
     * Load the module composer autoloader if present.
     *
     * @return void
     */
    protected function loadAutoloader()
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return;
        }
        require_once $autoload;
    }

    /**
     * Add menu items to the Items and Inventory application.
     *
     * @param object $app FA application instance.
     * @return void
     */
    function install_options($app)
    {
        global $path_to_root;

        switch ($app->id) {
            case 'stock':
                $app->add_rapp_function(
                    2,
                    _('Inventory Taking'),
                    $path_to_root . '/modules/' . $this->module_name . '/Inventory.php',
                    'SA_ksf_FA_InventoryCountVIEW'
                );
                break;
        }
    }

    /**
     * Define security areas and sections.
     *
     * @return array [security_areas, security_sections]
     */
    function install_access()
    {
        $security_sections[SS_ksf_FA_InventoryCount] = _("Inventory Count");

        $security_areas['SA_ksf_FA_InventoryCountVIEW'] = array(
            SS_ksf_FA_InventoryCount | 1,
            _("View inventory counts")
        );
        $security_areas['SA_ksf_FA_InventoryCountMANAGE'] = array(
            SS_ksf_FA_InventoryCount | 2,
            _("Process inventory counts and transfers")
        );

        return array($security_areas, $security_sections);
    }

    /**
     * Activate extension: install module schema.
     *
     * @param int  $company    Company number.
     * @param bool $check_only Only check whether activation is possible.
     * @return bool
     */
    function activate_extension($company, $check_only = true)
    {
        global $path_to_root;

        $this->loadAutoloader();

        if ($check_only) {
            return true;
        }

        $sqlFile = __DIR__ . '/sql/install.sql';
        if (!file_exists($sqlFile)) {
            return true;
        }
        $sql = file_get_contents($sqlFile);
        foreach (preg_split('/;\s*[\r\n]+/', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            db_query($statement, "Inventory Count install: {$statement}");
        }

        return true;
    }

    /**
     * Provide module constants to other modules via hook_invoke.
     *
     * @param array &$data Shared data bag.
     * @param array $opts  Options.
     * @return array Module constants.
     */
    function getModuleConstants(&$data, $opts = array())
    {
        return array(
            'module_name'   => $this->module_name,
            'version'       => $this->version,
            'security_area' => 'SA_ksf_FA_InventoryCountVIEW',
        );
    }

    /**
     * Advertise module capabilities.
     *
     * @param array &$data Shared data bag.
     * @param array $opts  Options.
     * @return array Capability list.
     */
    function getModuleCapabilities(&$data, $opts = array())
    {
        return array('inventory_count', 'count_import', 'count_export', 'xfer_all');
    }

    /**
     * Answer a specific capability query.
     *
     * @param array &$data Shared data bag with optional 'capability' key.
     * @param array $opts  Options.
     * @return bool|array Capability answer.
     */
    function hasCapability(&$data, $opts = array())
    {
        $capabilities = $this->getModuleCapabilities($data, $opts);
        if (isset($opts['capability'])) {
            return in_array($opts['capability'], $capabilities, true);
        }
        return $capabilities;
    }

    /**
     * Generic capability responder used by hook_invoke_all broadcasts.
     *
     * @param array &$data Shared data bag.
     * @param array $opts  Options.
     * @return mixed Response or null when not handled.
     */
    function respondToCapabilityRequest(&$data, $opts = array())
    {
        return $this->hasCapability($data, $opts);
    }
}
