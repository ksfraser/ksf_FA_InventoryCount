# DEFECTS-IC-001 — Legacy defects and their disposition in ksf_FA_InventoryCount

**Module:** ksf_FA_InventoryCount

These Mantis issues were filed against the legacy `ksf_Inventory` /
`FA_InventoryCount` code. The refactor re-architected cart handling as the
`CountCart` aggregate with unit-tested single-line mutations, so several
legacy defects are fixed by design. Each row below was verified against the
new code, not assumed.

| Mantis | Summary | Status | Disposition / evidence |
|---|---|---|---|
| #0000172 | Editing the cart borks it | FIXED | `CountCart::updateQty()` mutates only the targeted line; no wholesale session overwrite. Test: `CountCartTest::testUpdateQty` |
| #0000171 | Counting an item already in cart clobbers cart | FIXED | `CountCart::addScan()` increments the existing line quantity instead of replacing. Test: `CountCartTest::testAddScanIncrementsExistingLine` |
| #0000173 | Deleting an item in cart clobbers cart | FIXED | `CountCart::delete()` unsets only the targeted key. Test: `CountCartTest::testDeleteRemovesLine` |
| #0000174 | Over/Under tab - empty cart throws errors | FIXED | `CountSummary::fromLines([])` returns a zeroed summary; count process/summarize handle empty carts without error paths. Tests: `CountSummaryTest`, `InventoryCountServiceTest` |
| #0000053 | Transfer ALL loses location | FIXED (service level) | Locations are explicit parameters; blank/equal locations rejected with `LocationNotSetException`. Test: `TransferAllServiceTest::testSameLocationThrows`, `testEmptyLocationIsNoOp`. UI wiring still to come — see FR-IC-003-001 acceptance additions |
| #0000054 | Transfer ALL does not reset CART | PENDING | No transfer-all UI wiring yet in `PageController`; when added it MUST call `cart->clear()` after success (criterion recorded in FR-IC-003-001) |
| #0000179 | FA CORE transfer allows a blank location | FIXED | Count process rejects blank location before booking (`LocationNotSetException`). Test: `InventoryCountServiceTest::testEmptyLocationThrows`. Note: FA core itself is out of our control; our module never books with a blank location |

Regression coverage lives in `tests/Unit/` (CountCartTest,
InventoryCountServiceTest, TransferAllServiceTest).
