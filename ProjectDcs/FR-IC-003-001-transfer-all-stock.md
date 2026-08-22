# FR-IC — Transfer all stock between locations

**Module:** ksf_FA_InventoryCount
**Parent:** BR-IC-001 / BR-IC-003 / BR-IC-004 as applicable

**Mantis:** #0000053 (location lost), #0000054 (cart reset)

**Acceptance additions:** after a successful transfer-all the count cart is cleared; source/destination locations must be non-blank and distinct.

## Functional Requirement

The system SHALL move every stocked item from a source location to a destination location under a single transfer number. Empty source is a no-op success; identical or missing locations are rejected.

## Traceability

- UT-IC-003-001-001
- UT-IC-003-001-002
- UT-IC-003-001-003
