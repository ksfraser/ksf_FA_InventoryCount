# ksf_FA_InventoryCount

FrontAccounting (2.4.x) module for **inventory counting / stock taking**.

Consolidates the legacy `ksf_Inventory` and `FA_InventoryCount` repositories
into a single PSR-4 module under the `ksfraser\FrontAccounting\InventoryCount`
namespace, using composition (no legacy generic_interface inheritance) and
the shared KSF packages:

| Package | Use |
|---|---|
| `ksfraser/traits` | Reusable traits |
| `ksfraser/exceptions` | FA exception hierarchy (`FAConfigurationException`, ...) |
| `ksfraser/ksf-fa-common` | Platform contracts/utilities |
| `ksfraser/famock` (dev) | FA mocking for PHPUnit |

## Features

- Barcode scan entry into a count session (`FR-IC-001-001`)
- Scan resolution by stock id / item code / loose match (`FR-IC-001-002`)
- Edit / delete counted lines, clear cart (`FR-IC-001-003`)
- Over/short summary vs system QOH per location (`FR-IC-001-004`)
- Process count -> stock transfers through a configurable HOLDING tank location (`FR-IC-001-005`)
- Bulk import of scanned codes from text/CSV files (`FR-IC-002-001`)
- CSV export of the count cart (`FR-IC-002-002`)
- Transfer ALL stock between locations (decommission temporary locations) (`FR-IC-003-001`)
- Module configuration of the holding tank (`FR-IC-004-001`)
- Schema install: `0_ksf_inventory_scanned`, trigger, `0_ksf_inventory_count_history` (`FR-IC-004-002`)

See `ProjectDcs/` for BR/FR/UT/UAT/ARCH documents and the RTM.

## Install

```bash
composer install          # inside this module directory
```

Then in FrontAccounting: Setup -> Install/Activate Extensions, activate
`ksf_FA_InventoryCount`, grant the new security areas to roles.

## Tests

```bash
./vendor/bin/phpunit
```

## Legacy migration notes

- `ksf_Inventory` was the newer base (session cart, UI, import/export).
- `FA_InventoryCount` contributed only superseded standalone copies of shared
  classes (`class.generic_interface.php`, `class.item.php`) which are replaced
  here by composition + shared packages.
