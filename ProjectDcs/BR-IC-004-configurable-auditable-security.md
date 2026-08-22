# BR-IC-004 — Configurable, Auditable Installation & Security

**Module:** ksf_FA_InventoryCount

## Business Need

Adjustments move real inventory value; they must only occur through a
deliberately configured holding location, performed by authorized users, and
leave an audit trail of when each item was last counted.

## Business Requirement

The business requires:
1. A configurable HOLDING tank location used for all over/short corrections.
2. Role-based security areas for viewing vs managing counts.
3. Persistent storage of scans and last-count dates per item/location.

## Related

- FR-IC-004-001 (holding tank configuration)
- FR-IC-004-002 (schema install: scans table, trigger, history table)
- FR-IC-004-003 (security areas & menu integration)
