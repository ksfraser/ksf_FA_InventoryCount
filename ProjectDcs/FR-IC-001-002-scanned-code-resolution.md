# FR-IC — Scanned code resolution

**Module:** ksf_FA_InventoryCount
**Parent:** BR-IC-001 / BR-IC-003 / BR-IC-004 as applicable

## Functional Requirement

The system SHALL resolve a scanned code to a master stock id by exact stock_id, then item_codes mapping, then loose containment match. Unresolvable codes raise BarcodeNotFoundException.

## Traceability

- UT-IC-001-002-001
