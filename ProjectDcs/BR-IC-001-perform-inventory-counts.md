# BR-IC-001 — Perform Inventory Counts (Stock Taking)

**Module:** ksf_FA_InventoryCount
**Business Goal:** Ensure FrontAccounting stock quantities match physical reality so that sales, purchasing and reporting decisions are made on accurate data.

## Business Need

Retail and warehouse locations accumulate discrepancies between system
quantity-on-hand (QOH) and what is physically on the shelf. Without a
repeatable counting process, shrinkage goes undetected, catalogue exports
overstate availability, and customer orders fail.

## Business Requirement

The business requires the ability to perform **partial** or **full**
inventory counts per location, entered by barcode scanning, with the system
automatically adjusting stock through a controlled HOLDING tank location.

## Stakeholders

| Stakeholder | Interest |
|---|---|
| Inventory Manager | Accurate QOH; over/short visibility |
| Store staff | Fast scan-based entry |
| Accounting | Auditable adjustment transactions |

## Related

- FR-IC-001-001 (scan entry)
- FR-IC-001-002 (code resolution)
- FR-IC-001-003 (line maintenance)
- FR-IC-001-004 (over/short summary)
- FR-IC-001-005 (processing/adjustments)

*Mantis:* inventory count project (to be linked once requirements are imported).
