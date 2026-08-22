# UT-IC-005-001-001 — Overdue items report

**Module:** ksf_FA_InventoryCount

## Unit Test

- Implementation: `tests/Unit/CountHistoryReportServiceTest.php`
- Verifies: never-counted items reported as 'never'; items counted within
  the window excluded; stale items include days_since; empty stock = empty report.
  Also covers FR-IC-005-002 lastCountedDates coverage (UT-IC-005-002-001).
