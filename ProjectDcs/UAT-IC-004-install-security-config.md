# UAT-IC-004-install-security-config

**Module:** ksf_FA_InventoryCount

## UAT Steps

1. Install/activate ksf_FA_InventoryCount extension. Verify 0_ksf_inventory_scanned and history tables exist.
2. Grant SA_ksf_FA_InventoryCountVIEW/MANAGE areas to a role; verify menu entry visibility follows role.
3. Set HOLDING tank configuration; verify processing works, and fails gracefully with clear message when unset.

## Pass Criteria

All steps complete without PHP errors; resulting FA transactions visible in inquiries.
