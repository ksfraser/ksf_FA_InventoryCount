# UT-IC-001-005-005 — Adjusted items publish item_updated

**Module:** ksf_FA_InventoryCount
**Satisfies:** FR-IC-001-005 (integration broadcast)

## Unit Test

- Implementation: `tests/Unit/InventoryCountServiceTest.php::testAdjustedItemsPublishItemUpdated`
- Verifies: after booking over/short transfers, each adjusted stock id
  broadcasts `item_updated` (via ksf_FA_Common ItemEventPublisher) with
  payload stock_id/event='updated'/trigger='module'/adjustment context so
  Square and Woocommerce endpoints resync. Matched lines publish nothing.
