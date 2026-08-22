# FR-IC-002 — Bulk Import / Export of Counts

**Module:** ksf_FA_InventoryCount
**Parent:** BR-IC-001 (efficiency of count entry), extends to bulk workflows.

## Functional Requirement

### FR-IC-002-001 — Import text/CSV file of scanned codes
The system SHALL allow uploading a plain-text or CSV file containing one
scanned code per line (optional second column = counted quantity, default 1;
`#` lines are comments). Each code SHALL be resolved to a master stock id and
added to the count cart. Unresolvable codes SHALL be reported back to the
user without aborting the import. An empty location selection or a file with
no importable lines SHALL be rejected with an error.

### FR-IC-002-002 — Export cart as CSV
The system SHALL export the current count cart as CSV with columns:
`stock_id, barcode, counted_qty, qoh, variance`.

## Acceptance Criteria
1. A file of UPCs imports each line into the cart with qty 1.
2. Optional quantities are honoured.
3. Unknown codes appear in the "unresolved" report; known codes still import.
4. Export produces the exact header row above.

## Related UT/UAT
- UT-IC-002-001-001, UT-IC-002-001-002, UT-IC-002-001-003
- UT-IC-002-002-001
