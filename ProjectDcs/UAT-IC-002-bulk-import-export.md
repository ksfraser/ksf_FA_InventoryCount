# UAT-IC-002-bulk-import-export

**Module:** ksf_FA_InventoryCount

## UAT Steps

1. Prepare a text file of UPCs, including one unknown code.
2. Import via module import form. Verify known codes appear in cart; unknown code reported.
3. Export cart CSV; verify header stock_id,barcode,counted_qty,qoh,variance and row values.

## Pass Criteria

All steps complete without PHP errors; resulting FA transactions visible in inquiries.
