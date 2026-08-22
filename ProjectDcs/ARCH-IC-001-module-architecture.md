# ARCH-IC-001 — Module Architecture

**Module:** ksf_FA_InventoryCount

## Overview

Single-repo FrontAccounting extension consolidating the legacy `ksf_Inventory`
and `FA_InventoryCount` modules. PSR-4, namespaced, composition over
inheritance (no legacy `generic_interface` hierarchy).

## Namespace

```
ksfraser\FrontAccounting\InventoryCount\
```

## Component Diagram

```
modules/ksf_FA_InventoryCount/
├── Inventory.php            FA page shell (security + autoloader)
├── hooks.php                hooks_ksf_FA_InventoryCount (menu, security, schema)
├── sql/install.sql          0_ksf_inventory_scanned (+trigger), history table
└── src/
    ├── Domain/              CountLine, CountCart, CountSummary, ProcessResult
    ├── Fa/                  FaApiInterface -> FrontAccountingApi (global fns)
    ├── Repository/          CountRepositoryInterface -> DbCountRepository
    │                        (uses Ksfraser\ModulesDAO\Db\DbAdapterInterface)
    ├── Service/             InventoryCountService, TransferAllService,
    │                        ImportService, ExportService
    ├── Ui/PageController    Thin FA UI controller composing the services
    └── Exception/           HoldingTankNotConfiguredException,
                             LocationNotSetException, BarcodeNotFoundException
                             (extend Ksfraser\Exceptions\FrontAccounting\*)
```

## Key Decisions

| Decision | Rationale |
|---|---|
| No generic_interface inheritance | Deep legacy inheritance replaced by DI + traits packages |
| FaApiInterface port | Unit-testable domain logic without a running FA |
| ModulesDAO DbAdapter for persistence | Shared, tested DB abstraction |
| Overage -> holding tank; shortage <- holding tank | Matches legacy transfer semantics via add_stock_transfer_item |
| Tables `0_ksf_*`, trigger sets scandate | AGENTS.md conventions |

## Dependencies (composer)

- `ksfraser/traits`, `ksfraser/exceptions`, `ksfraser/ksf-fa-common`
- dev: `phpunit/phpunit ^9`, `ksfraser/famock`, `ksfraser/ksf-modules-dao`

## UML

See ProjectDocs/UML.md (class diagram) in this repo's ProjectDcs.
