/* Run once on an existing Collins M database after copying assets/images/phones/. */
INSERT INTO product_images (product_id, image_path)
SELECT p.id, CASE p.name
    WHEN 'Galaxy S25 Ultra' THEN 'assets/images/phones/galaxy-s25-ultra.svg'
    WHEN 'Galaxy Z Fold' THEN 'assets/images/phones/galaxy-z-fold.svg'
    WHEN 'iPhone 16 Pro Max' THEN 'assets/images/phones/iphone-16-pro-max.svg'
    WHEN 'iPhone 15 Pro' THEN 'assets/images/phones/iphone-15-pro.svg'
END
FROM products p
WHERE p.name IN ('Galaxy S25 Ultra', 'Galaxy Z Fold', 'iPhone 16 Pro Max', 'iPhone 15 Pro')
  AND NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id);
