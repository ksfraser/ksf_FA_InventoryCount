# Requirements Traceability Matrix (RTM)

Auto-generated from ProjectDcs filenames and `@BABOK Related` tags in code/tests.

Regenerate during release prep; do not hand-edit rows.


## Documents

| Type | Document |
|---|---|
| ARCH | ARCH-IC-001-module-architecture.md |
| BR | BR-IC-001-perform-inventory-counts.md |
| BR | BR-IC-003-location-decommissioning-transfers.md |
| BR | BR-IC-004-configurable-auditable-security.md |
| DEFECTS | DEFECTS-IC-001-legacy-defect-mapping.md |
| FR | FR-IC-001-001-barcode-scan-entry.md |
| FR | FR-IC-001-002-scanned-code-resolution.md |
| FR | FR-IC-001-003-count-line-maintenance.md |
| FR | FR-IC-001-004-over-short-summary.md |
| FR | FR-IC-001-005-process-count-holding-tank.md |
| FR | FR-IC-002-bulk-import-export.md |
| FR | FR-IC-003-001-transfer-all-stock.md |
| FR | FR-IC-004-001-holding-tank-config.md |
| FR | FR-IC-004-002-schema-install.md |
| FR | FR-IC-005-001-report-items-not-counted-in-x-days.md |
| FR | FR-IC-005-002-report-last-counted-date.md |
| RTM | RTM.md |
| UAT | UAT-IC-001-perform-partial-count.md |
| UAT | UAT-IC-002-bulk-import-export.md |
| UAT | UAT-IC-003-decommission-location.md |
| UAT | UAT-IC-004-install-security-config.md |
| UML | UML.md |
| UT | UT-IC-001-001-001-scan-increments-line.md |
| UT | UT-IC-001-001-002-line-defaults.md |
| UT | UT-IC-001-002-001-find-by-candidates.md |
| UT | UT-IC-001-003-001-update-qty.md |
| UT | UT-IC-001-003-002-delete-line.md |
| UT | UT-IC-001-003-003-clear-cart.md |
| UT | UT-IC-001-004-001-variance-over.md |
| UT | UT-IC-001-004-002-variance-short.md |
| UT | UT-IC-001-004-003-variance-match.md |
| UT | UT-IC-001-004-004-summary-classification.md |
| UT | UT-IC-001-005-001-overage-to-tank.md |
| UT | UT-IC-001-005-002-shortage-from-tank.md |
| UT | UT-IC-001-005-003-matched-no-transfer.md |
| UT | UT-IC-001-005-004-empty-location-rejected.md |
| UT | UT-IC-001-005-005-adjusted-items-publish-item-updated.md |
| UT | UT-IC-002-001-001-parse-lines.md |
| UT | UT-IC-002-001-002-unresolved-reported.md |
| UT | UT-IC-002-001-003-import-needs-location.md |
| UT | UT-IC-002-002-001-export-csv.md |
| UT | UT-IC-003-001-001-xfer-all-moves.md |
| UT | UT-IC-003-001-002-xfer-all-empty-noop.md |
| UT | UT-IC-003-001-003-xfer-all-invalid-locations.md |
| UT | UT-IC-004-001-001-missing-holdtank-rejected.md |
| UT | UT-IC-004-002-001-repo-record-scan.md |
| UT | UT-IC-004-002-002-repo-history-upsert.md |
| UT | UT-IC-004-002-003-repo-last-date-missing.md |
| UT | UT-IC-004-002-004-repo-last-date-found.md |
| UT | UT-IC-005-001-001-overdue-items-report.md |

## Code / Test Traceability

| Artifact | References |
|---|---|
| src/Domain/CountCart.php | BR-IC-001, FR-IC-001-001, FR-IC-001-002, FR-IC-001-003 |
| src/Domain/CountLine.php | FR-IC-001-001, FR-IC-001-004 |
| src/Domain/CountSummary.php | FR-IC-001-004 |
| src/Domain/ProcessResult.php | FR-IC-001-005 |
| src/Exception/BarcodeNotFoundException.php | FR-IC-001-002 |
| src/Exception/HoldingTankNotConfiguredException.php | FR-IC-004-001 |
| src/Exception/LocationNotSetException.php | FR-IC-001-005 |
| src/Fa/FaApiInterface.php | FR-IC-001-002, FR-IC-001-004, FR-IC-001-005, FR-IC-003-001, FR-IC-004-001 |
| src/Repository/CountRepositoryInterface.php | FR-IC-001-005 |
| src/Repository/DbCountRepository.php | FR-IC-004-002 |
| src/Service/CountHistoryReportService.php | FR-IC-005-001, FR-IC-005-002 |
| src/Service/ExportService.php | FR-IC-002-002 |
| src/Service/ImportService.php | FR-IC-002-001 |
| src/Service/InventoryCountService.php | FR-IC-001-004, FR-IC-001-005 |
| src/Service/TransferAllService.php | FR-IC-003-001 |
| src/Ui/PageController.php | FR-IC-001-001, FR-IC-001-003, FR-IC-001-005 |
| tests/Fake/FakeCountRepository.php | UT-IC-001-005-001 |
| tests/Fake/FakeFaApi.php | UT-IC-001-001-001 |
| tests/Unit/CountCartTest.php | UT-IC-001-001-001, UT-IC-001-002-001, UT-IC-001-003-001, UT-IC-001-003-002, UT-IC-001-003-003 |
| tests/Unit/CountHistoryReportServiceTest.php | UT-IC-005-001-001, UT-IC-005-001-002, UT-IC-005-001-003, UT-IC-005-002-001 |
| tests/Unit/CountLineTest.php | UT-IC-001-001-002, UT-IC-001-004-001, UT-IC-001-004-002, UT-IC-001-004-003 |
| tests/Unit/CountSummaryTest.php | UT-IC-001-004-004 |
| tests/Unit/DbCountRepositoryTest.php | UT-IC-004-002-001, UT-IC-004-002-002, UT-IC-004-002-003, UT-IC-004-002-004 |
| tests/Unit/ExportServiceTest.php | UT-IC-002-002-001 |
| tests/Unit/ImportServiceTest.php | UT-IC-002-001-001, UT-IC-002-001-002, UT-IC-002-001-003 |
| tests/Unit/InventoryCountServiceTest.php | UT-IC-001-005-001, UT-IC-001-005-002, UT-IC-001-005-003, UT-IC-001-005-004, UT-IC-001-005-005, UT-IC-004-001-001 |
| tests/Unit/TransferAllServiceTest.php | UT-IC-003-001-001, UT-IC-003-001-002, UT-IC-003-001-003 |
