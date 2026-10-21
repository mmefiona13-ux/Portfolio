CREATE DATABASE IF NOT EXISTS collins_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE collins_portfolio;

CREATE TABLE admins (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_name VARCHAR(120) NOT NULL, brand ENUM('Samsung','Apple') NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_category (category_name,brand)
) ENGINE=InnoDB;
CREATE TABLE products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(180) NOT NULL, brand ENUM('Samsung','Apple') NOT NULL, category_id INT UNSIGNED NOT NULL, price DECIMAL(12,2) NOT NULL, storage VARCHAR(80) NOT NULL, `condition` ENUM('New','Used') NOT NULL DEFAULT 'New', quality VARCHAR(255) NOT NULL, stock INT UNSIGNED NOT NULL DEFAULT 0, description TEXT NOT NULL, specifications TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_product_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT, INDEX idx_products_brand (brand), INDEX idx_products_stock(stock), INDEX idx_products_price(price)
) ENGINE=InnoDB;
CREATE TABLE product_colors (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, color_name VARCHAR(60) NOT NULL, CONSTRAINT fk_color_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE, INDEX idx_color_product(product_id)
) ENGINE=InnoDB;
CREATE TABLE product_images (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, image_path VARCHAR(255) NOT NULL, CONSTRAINT fk_image_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE, INDEX idx_image_product(product_id)
) ENGINE=InnoDB;
CREATE TABLE reviews (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, customer_name VARCHAR(80) NOT NULL, rating TINYINT UNSIGNED NOT NULL, comment TEXT NOT NULL, status ENUM('pending','approved') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_review_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE, CONSTRAINT check_rating CHECK (rating BETWEEN 1 AND 5), INDEX idx_review_product_status(product_id,status)
) ENGINE=InnoDB;
CREATE TABLE customers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(120) NOT NULL, phone VARCHAR(30) NOT NULL, whatsapp VARCHAR(30) NOT NULL, email VARCHAR(190) NOT NULL, address TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_customers_phone(phone)
) ENGINE=InnoDB;
CREATE TABLE orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_number VARCHAR(40) NOT NULL UNIQUE, customer_id INT UNSIGNED NOT NULL, total DECIMAL(12,2) NOT NULL, payment_method VARCHAR(80) NOT NULL, status ENUM('Pending','Confirmed','Processing','Ready for Delivery','Out for Delivery','Completed','Cancelled') NOT NULL DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_order_customer FOREIGN KEY(customer_id) REFERENCES customers(id), INDEX idx_orders_status(status), INDEX idx_orders_created(created_at)
) ENGINE=InnoDB;
CREATE TABLE order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NULL, product_name VARCHAR(180) NOT NULL, unit_price DECIMAL(12,2) NOT NULL, quantity INT UNSIGNED NOT NULL, CONSTRAINT fk_item_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE, CONSTRAINT fk_item_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO categories(category_name,brand) VALUES
('Galaxy S Series','Samsung'),('Galaxy Ultra Series','Samsung'),('Galaxy Z Fold','Samsung'),('Galaxy Z Flip','Samsung'),('Other Samsung Galaxy','Samsung'),
('Standard iPhone','Apple'),('iPhone Plus','Apple'),('iPhone Pro','Apple'),('iPhone Pro Max','Apple'),('Other iPhone','Apple');

/* Optional starter catalogue, derived from the previous portfolio. Add real images in the admin dashboard. */
INSERT INTO products(name,brand,category_id,price,storage,`condition`,quality,stock,description,specifications) VALUES
('Galaxy S25 Ultra','Samsung',2,185000,'256GB','New','Brand new, sealed device',3,'The ultimate Galaxy experience with powerful cameras and flagship performance.','Contact Collins M for the complete model specifications.'),
('Galaxy Z Fold','Samsung',3,210000,'256GB','New','Brand new, sealed device',2,'A big-screen experience that folds into your pocket.','Contact Collins M for the complete model specifications.'),
('iPhone 16 Pro Max','Apple',9,195000,'256GB','New','Brand new, sealed device',4,'Big display, big impact, and a refined Pro experience.','Contact Collins M for the complete model specifications.'),
('iPhone 15 Pro','Apple',8,145000,'128GB','Used','Excellent condition, carefully selected',1,'Titanium design with capable Pro performance.','Contact Collins M for the complete model specifications.');
INSERT INTO product_colors(product_id,color_name) VALUES (1,'Titanium Black'),(1,'Titanium Gray'),(2,'Phantom Black'),(3,'Desert Titanium'),(4,'Natural Titanium');
INSERT INTO product_images(product_id,image_path) VALUES
(1,'assets/images/phones/galaxy-s25-ultra.svg'),
(2,'assets/images/phones/galaxy-z-fold.svg'),
(3,'assets/images/phones/iphone-16-pro-max.svg'),
(4,'assets/images/phones/iphone-15-pro.svg');
