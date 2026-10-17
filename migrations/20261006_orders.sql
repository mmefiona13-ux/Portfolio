-- Run this once on an existing collins_portfolio database. It does not alter products.
CREATE TABLE IF NOT EXISTS customers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(120) NOT NULL, phone VARCHAR(30) NOT NULL, whatsapp VARCHAR(30) NOT NULL, email VARCHAR(190) NOT NULL, address TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_customers_phone(phone)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_number VARCHAR(40) NOT NULL UNIQUE, customer_id INT UNSIGNED NOT NULL, total DECIMAL(12,2) NOT NULL, payment_method VARCHAR(80) NOT NULL, status ENUM('Pending','Confirmed','Processing','Ready for Delivery','Out for Delivery','Completed','Cancelled') NOT NULL DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_order_customer FOREIGN KEY(customer_id) REFERENCES customers(id), INDEX idx_orders_status(status), INDEX idx_orders_created(created_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NULL, product_name VARCHAR(180) NOT NULL, unit_price DECIMAL(12,2) NOT NULL, quantity INT UNSIGNED NOT NULL, CONSTRAINT fk_item_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE, CONSTRAINT fk_item_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;
