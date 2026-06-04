-- Run once if discount columns are missing (Railway/live DB).
-- The app also adds these columns automatically via sisonke_bootstrap_marketplace_schema().

ALTER TABLE group_buy_campaigns
  ADD COLUMN discount_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER campaign_price;

ALTER TABLE group_buy_campaigns
  ADD COLUMN discount_type ENUM('percent','fixed') NULL AFTER discount_enabled;

ALTER TABLE group_buy_campaigns
  ADD COLUMN discount_value DECIMAL(10,2) NULL AFTER discount_type;

ALTER TABLE group_buy_campaigns
  ADD COLUMN sale_price DECIMAL(10,2) NULL AFTER discount_value;
