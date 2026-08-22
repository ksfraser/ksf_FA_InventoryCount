# UAT-IC-001-perform-partial-count

**Module:** ksf_FA_InventoryCount

## UAT Steps

1. Login with inventory count role; open Items & Inventory -> Inventory Taking.
2. Select location STORE and date.
3. Scan three barcodes (one twice). Verify cart lines: two lines, first with qty 2.
4. Edit a counted quantity and update. Delete one line.
5. Process Count. Verify over/short transfers booked via HOLDING tank and notification shows transfer number.

## Pass Criteria

All steps complete without PHP errors; resulting FA transactions visible in inquiries.
