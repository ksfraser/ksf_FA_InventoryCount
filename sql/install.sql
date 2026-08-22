-- ksf_FA_InventoryCount schema
-- Per AGENTS.md: tables use the 0_ company prefix (NOT @TB_PREF@).

CREATE TABLE IF NOT EXISTS `0_ksf_inventory_scanned` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stock_id`  VARCHAR(32)  NOT NULL DEFAULT '',
    `location`  VARCHAR(32)  NOT NULL DEFAULT '',
    `qty`       DOUBLE       NOT NULL DEFAULT 1,
    `scandate`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `stock_loc` (`stock_id`, `location`)
) ENGINE=MyISAM;

DROP TRIGGER IF EXISTS `0_ksf_inventory_scanned_date_created`;
CREATE TRIGGER `0_ksf_inventory_scanned_date_created` BEFORE INSERT ON `0_ksf_inventory_scanned`
    FOR EACH ROW SET NEW.`scandate` = NOW();

CREATE TABLE IF NOT EXISTS `0_ksf_inventory_count_history` (
    `stock_id`       VARCHAR(32) NOT NULL,
    `location`       VARCHAR(32) NOT NULL,
    `inventory_date` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `item-location` (`stock_id`, `location`)
) ENGINE=MyISAM;
