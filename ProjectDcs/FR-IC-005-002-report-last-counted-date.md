# FR-IC-005-002 — Report last counted date per item/location

**Module:** ksf_FA_InventoryCount
**Parent:** BR-IC-004 (auditability)
**Mantis:** #0000175 `[BReq] Report last counted date`
**Status:** SERVICE IMPLEMENTED (repository API + CountHistoryReportService; report UI page pending)

## Functional Requirement

The system SHALL expose the last date each stock item/location pair was
counted. Persistence layer implemented (`DbCountRepository::lastCountDate()`);
report display is backlog work tracked under this FR.
