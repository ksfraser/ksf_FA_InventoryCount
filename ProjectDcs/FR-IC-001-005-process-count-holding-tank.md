# FR-IC — Process count via holding tank

**Module:** ksf_FA_InventoryCount
**Parent:** BR-IC-001 / BR-IC-003 / BR-IC-004 as applicable

## Functional Requirement

On processing, the system SHALL refresh QOH, book compensating stock transfers through the configured HOLDING tank location (overage: location->tank; shortage: tank->location) under one transfer number, and record scan + count history rows. Missing location or holding-tank configuration SHALL abort processing with an exception.

## Traceability

- UT-IC-001-005-001
- UT-IC-001-005-002
- UT-IC-001-005-003
- UT-IC-001-005-004
- UT-IC-004-001-001
