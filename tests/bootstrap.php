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

// Recording double for FA's hook_invoke_first(), used to verify that variance
// booking delegates to ksf_FA_Warehouse when it answers and falls back to the
// inline path when it does not. Mirrors FA semantics: walk providers, stop at the
// first non-null reply, decline leaves $data untouched.
//
// Script a reply with:
//   $GLOBALS['ksf_test_first_reply']['move_inventory'] = ['trans_no' => .., 'adjustments' => [..]]
// Leave it unset (or set it to null) to simulate warehouse being inactive.
if (!function_exists('hook_invoke_first')) {
    function hook_invoke_first($method, &$data, $opts = null)
    {
        $GLOBALS['ksf_test_first_calls'][] = [$method, $data, $opts];

        if ($method !== 'respondToCapabilityRequest') {
            return null;
        }

        $request = isset($opts['request']) ? $opts['request'] : '';

        if (array_key_exists($request, $GLOBALS['ksf_test_first_reply'])) {
            return $GLOBALS['ksf_test_first_reply'][$request];
        }

        return null;
    }
}

$GLOBALS['ksf_test_first_calls'] = [];
$GLOBALS['ksf_test_first_reply'] = [];
