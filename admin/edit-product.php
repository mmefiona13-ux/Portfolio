<?php
require 'header.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = db()->prepare('SELECT * FROM products WHERE id=?');
$statement->execute([$id]);
$product = $statement->fetch();
if (!$product) { flash('error', 'Product not found.'); header('Location: manage-products.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $name = trim($_POST['name'] ?? '');
        $brand = in_array($_POST['brand'] ?? '', ['Samsung', 'Apple'], true) ? $_POST['brand'] : '';
        $category = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        if (!$name || !$brand || !$category || $price === false || $price < 0 || $stock === false || $stock < 0) throw new RuntimeException('Please complete required fields with valid values.');
        $statement = db()->prepare('UPDATE products SET name=?,brand=?,category_id=?,price=?,storage=?,`condition`=?,quality=?,stock=?,description=?,specifications=? WHERE id=?');
        $statement->execute([$name, $brand, $category, $price, trim($_POST['storage'] ?? ''), ($_POST['condition'] ?? '') === 'Used' ? 'Used' : 'New', trim($_POST['quality'] ?? ''), $stock, trim($_POST['description'] ?? ''), trim($_POST['specifications'] ?? ''), $id]);
        db()->prepare('DELETE FROM product_colors WHERE product_id=?')->execute([$id]);
        foreach (array_filter(array_map('trim', explode(',', $_POST['colors'] ?? ''))) as $color) db()->prepare('INSERT INTO product_colors(product_id,color_name) VALUES(?,?)')->execute([$id, mb_substr($color, 0, 60)]);
        if ($path = upload_image($_FILES['image'] ?? [])) {
            db()->prepare('DELETE FROM product_images WHERE product_id=?')->execute([$id]);
            db()->prepare('INSERT INTO product_images(product_id,image_path) VALUES(?,?)')->execute([$id, $path]);
        }
        flash('success', 'Product updated.'); header('Location: manage-products.php'); exit;
    } catch (Throwable $e) { flash('error', $e->getMessage()); }
}
?><div class="admin-title"><p>CATALOGUE</p><h2>Edit <?= e($product['name']) ?></h2></div><?php require 'product-form.php'; require 'footer.php'; ?>
