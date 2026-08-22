# UT-IC-001-005-001-overage-to-tank

**Module:** ksf_FA_InventoryCount
**Satisfies:** see @BABOK refs in code

## Unit Test

- Implementation: `tests/Unit/InventoryCountServiceTest.php::testOverageTransfersToHoldingTank (+ QoH refresh)`
- Verifies: Overage books loc->tank transfer.

## Result

PASS (`vendor/bin/phpunit`)
