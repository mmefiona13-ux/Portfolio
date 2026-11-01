-- Collins M e-commerce upgrade. Apply once to the existing collins_portfolio database.
-- Existing customer, order, product and admin rows are retained.
USE collins_portfolio;

CREATE TABLE IF NOT EXISTS customer_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_account FOREIGN KEY(account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
  INDEX idx_reset_expiry(expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wishlist_items (
  account_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(account_id, product_id),
  CONSTRAINT fk_wishlist_account FOREIGN KEY(account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recently_viewed (
  account_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(account_id, product_id),
  CONSTRAINT fk_recent_account FOREIGN KEY(account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
  CONSTRAINT fk_recent_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_recent_viewed(viewed_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  old_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  changed_by_admin INT UNSIGNED NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_status_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_status_admin FOREIGN KEY(changed_by_admin) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_status_order_time(order_id,created_at)
) ENGINE=InnoDB;

-- Seed one baseline status event for pre-existing orders without a history row.
INSERT INTO order_status_history(order_id,old_status,new_status,created_at)
SELECT o.id,NULL,o.status,o.created_at FROM orders o
WHERE NOT EXISTS (SELECT 1 FROM order_status_history h WHERE h.order_id=o.id);

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(60) NOT NULL,
  entity_id VARCHAR(80) NOT NULL,
  detail TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_admin FOREIGN KEY(admin_id) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_audit_entity(entity_type,entity_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS flash_sales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_flash_window(active,starts_at,ends_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS flash_sale_products (
  flash_sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  sale_price DECIMAL(12,2) NOT NULL,
  PRIMARY KEY(flash_sale_id,product_id),
  CONSTRAINT fk_flash_sale FOREIGN KEY(flash_sale_id) REFERENCES flash_sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_flash_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS website_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_alerts (
  product_id INT UNSIGNED PRIMARY KEY,
  notified_at TIMESTAMP NULL,
  last_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_stock_alert_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  provider VARCHAR(60) NOT NULL,
  provider_reference VARCHAR(190) NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending','verified','failed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  INDEX idx_payment_provider_ref(provider,provider_reference)
) ENGINE=InnoDB;

ALTER TABLE orders MODIFY status ENUM('Pending','Confirmed','Processing','Shipped','Out for Delivery','Delivered','Ready for Delivery','Completed','Cancelled','Failed Delivery') NOT NULL DEFAULT 'Pending';
ALTER TABLE orders ADD COLUMN estimated_delivery DATE NULL;
ALTER TABLE orders ADD COLUMN stock_restored TINYINT(1) NOT NULL DEFAULT 0;

INSERT IGNORE INTO website_settings(setting_key,setting_value) VALUES
 ('promo_message',''),('hero_title','Upgrade with genuine Samsung & iPhone devices.'),
 ('hero_subtitle','Explore verified new and carefully selected used phones for every lifestyle and budget.'),
 ('hero_image',''),('theme_primary','#1677ff'),('low_stock_threshold','3');
