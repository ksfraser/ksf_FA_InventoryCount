# FR-IC-005-001 — Report items not counted within X days

**Module:** ksf_FA_InventoryCount
**Parent:** BR-IC-004 (auditability)
**Mantis:** #0000176 `[BReq] Report Items not counted in X days`
**Status:** BACKLOG (not yet implemented)

## Functional Requirement

The system SHALL provide a report listing every stock item at a location whose
`0_ksf_inventory_count_history` last-count date is older than a configurable
number of days (or never counted).

## Implementation Notes

Data source exists: `DbCountRepository::lastCountDate()` /
`0_ksf_inventory_count_history`. Requires an aggregate query + UI report page.
