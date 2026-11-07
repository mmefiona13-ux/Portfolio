<?php
require 'header.php';

$defaults = [
    'promo_message' => '',
    'hero_title' => 'Upgrade with genuine Samsung & iPhone devices.',
    'hero_subtitle' => 'Explore verified new and carefully selected used phones for every lifestyle and budget.',
    'hero_image' => '',
    'theme_primary' => '#1677ff',
    'low_stock_threshold' => '3'
];

$pdo = db();

$productRows = $pdo->query("
    SELECT p.id, p.name, p.brand, p.price, p.stock,
           (SELECT image_path FROM product_images
            WHERE product_id = p.id ORDER BY id LIMIT 1) AS image
    FROM products p
    ORDER BY p.created_at DESC
")->fetchAll();

$validProductIds = array_map('intval', array_column($productRows, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $errors = [];

    $values = [];
    foreach ($defaults as $key => $fallback) {
        $values[$key] = trim($_POST[$key] ?? $fallback);
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $values['theme_primary'])) {
        $errors[] = 'Please choose a valid theme colour.';
    }

    if ($values['hero_image'] !== '') {
        $parts = parse_url($values['hero_image']);
        $https = is_array($parts)
            && strtolower($parts['scheme'] ?? '') === 'https'
            && filter_var($values['hero_image'], FILTER_VALIDATE_URL);

        $local = preg_match(
            '#^(assets/images|uploads/products)/[A-Za-z0-9._/-]+$#D',
            $values['hero_image']
        );

        if (!$https && !$local) {
            $errors[] = 'Hero images must use HTTPS or a valid local image path.';
        }
    }

    $values['low_stock_threshold'] = (string) max(
        1,
        min(100, (int) $values['low_stock_threshold'])
    );

    $requestedIds = $_POST['featured_product_ids'] ?? [];
    if (!is_array($requestedIds)) {
        $requestedIds = [];
    }

    $requestedIds = array_values(array_unique(array_filter(
        array_map('intval', $requestedIds),
        fn($id) => $id > 0
    )));

    if (count($requestedIds) > 8) {
        $errors[] = 'You can select a maximum of 8 featured products.';
    }

    if (array_diff($requestedIds, $validProductIds)) {
        $errors[] = 'One or more selected featured products are invalid.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $save = $pdo->prepare("
                INSERT INTO website_settings(setting_key, setting_value)
                VALUES(?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($values as $key => $value) {
                $save->execute([$key, $value]);
            }

            $save->execute([
                'featured_product_ids',
                implode(',', $requestedIds)
            ]);

            $pdo->prepare("
                INSERT INTO admin_audit_log
                    (admin_id, action, entity_type, entity_id, detail)
                VALUES(?, ?, ?, ?, ?)
            ")->execute([
                $_SESSION['admin_id'],
                'customize',
                'website',
                'settings',
                'Updated homepage content, theme, featured products and inventory settings'
            ]);

            $pdo->commit();
            flash('success', 'Website appearance and settings saved successfully.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Website customisation error: ' . $e->getMessage());
            flash('error', 'Settings could not be saved. Please try again.');
        }
    } else {
        flash('error', implode(' ', $errors));
    }

    header('Location: customize.php');
    exit;
}

$vals = [];
foreach ($defaults as $key => $value) {
    $vals[$key] = setting($key, $value);
}

$featuredValue = setting('featured_product_ids', '');
$selectedFeaturedIds = $featuredValue !== ''
    ? array_map('intval', explode(',', $featuredValue))
    : [];

$previewFeaturedProducts = array_values(array_filter(
    $productRows,
    fn($product) => in_array((int) $product['id'], $selectedFeaturedIds, true)
));

$previewFlashSales = [];
try {
    $previewFlashSales = $pdo->query("
        SELECT fs.title, fs.ends_at, p.id, p.name, p.brand,
               p.price AS regular_price, fsp.sale_price,
               (SELECT image_path FROM product_images
                WHERE product_id = p.id ORDER BY id LIMIT 1) AS image
        FROM flash_sales fs
        JOIN flash_sale_products fsp ON fsp.flash_sale_id = fs.id
        JOIN products p ON p.id = fsp.product_id
        WHERE fs.active = 1
          AND fs.starts_at <= NOW()
          AND fs.ends_at > NOW()
        ORDER BY fs.ends_at
        LIMIT 3
    ")->fetchAll();
} catch (Throwable $e) {
    error_log('Studio flash-sale preview error: ' . $e->getMessage());
}
?>

<div class="admin-title studio-heading">
    <div>
        <p>STORE DESIGN STUDIO</p>
        <h2>Website appearance</h2>
        <span>Control the content and visual identity of your Collins M storefront.</span>
    </div>
    <a class="studio-view-store" href="../index.php" target="_blank">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> View live store
    </a>
</div>

<div class="studio-layout">
    <div class="studio-editor">

        <form method="post" class="studio-form" id="appearance-form">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <section class="studio-card">
                <div class="studio-card-heading">
                    <span class="studio-icon"><i class="fa-solid fa-pen-to-square"></i></span>
                    <div>
                        <h3>Homepage content</h3>
                        <p>Control the first message visitors see.</p>
                    </div>
                </div>

                <label class="studio-field">
                    Promotional announcement
                    <input name="promo_message" maxlength="200"
                        value="<?= e($vals['promo_message']) ?>"
                        placeholder="Example: Free delivery within Nairobi this week">
                    <small>Leave empty to hide the announcement bar.</small>
                </label>

                <label class="studio-field">
                    Main heading
                    <input id="preview-title" name="hero_title" maxlength="180"
                        value="<?= e($vals['hero_title']) ?>" required>
                </label>

                <label class="studio-field">
                    Supporting description
                    <textarea id="preview-subtitle" name="hero_subtitle"
                        maxlength="400" rows="3"><?= e($vals['hero_subtitle']) ?></textarea>
                </label>

                <label class="studio-field">
                    Hero background image path or HTTPS URL
                    <input name="hero_image" maxlength="500"
                        value="<?= e($vals['hero_image']) ?>"
                        placeholder="uploads/products/your-banner.jpg">
                    <small>Use a local image path or a secure HTTPS image URL.</small>
                </label>
            </section>

            <section class="studio-card">
                <div class="studio-card-heading">
                    <span class="studio-icon"><i class="fa-solid fa-palette"></i></span>
                    <div>
                        <h3>Brand appearance</h3>
                        <p>Choose the main colour used across your store.</p>
                    </div>
                </div>

                <div class="studio-colour-row">
                    <label class="studio-field">
                        Primary brand colour
                        <input id="preview-colour" type="color" name="theme_primary"
                            value="<?= e($vals['theme_primary']) ?>">
                    </label>
                    <div class="studio-colour-value">
                        <span>Selected colour</span>
                        <strong id="colour-code"><?= e($vals['theme_primary']) ?></strong>
                    </div>
                </div>
            </section>

            <section class="studio-card">
                <div class="studio-card-heading">
                    <span class="studio-icon"><i class="fa-solid fa-mobile-screen-button"></i></span>
                    <div>
                        <h3>Featured products</h3>
                        <p>Choose up to 8 phones to highlight on your homepage.</p>
                    </div>
                </div>

                <div class="studio-product-list">
                    <?php if ($productRows): ?>
                        <?php foreach ($productRows as $product): ?>
                            <label class="studio-product"
                                data-product-name="<?= e($product['brand'] . ' ' . $product['name']) ?>"
                                data-product-price="<?= e(money($product['price'])) ?>"
                                data-product-image="<?= e($product['image'] ? image_url($product['image']) : '') ?>">
                                <?php if (!empty($product['image'])): ?>
                                    <img class="studio-product-image"
                                         src="<?= e(image_url($product['image'])) ?>"
                                         alt="<?= e($product['brand'] . ' ' . $product['name']) ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="studio-product-image-placeholder">No image</div>
                                <?php endif; ?>
                                <input type="checkbox" name="featured_product_ids[]"
                                    value="<?= (int) $product['id'] ?>"
                                    <?= in_array((int) $product['id'], $selectedFeaturedIds, true) ? 'checked' : '' ?>>
                                <span class="studio-product-details">
                                    <strong><?= e($product['brand'] . ' ' . $product['name']) ?></strong>
                                    <small><?= money($product['price']) ?> Â· <?= (int) $product['stock'] ?> in stock</small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products are available yet.</p>
                    <?php endif; ?>
                </div>
                <small class="studio-hint">Select no more than 8 products.</small>
            </section>

            <section class="studio-card">
                <div class="studio-card-heading">
                    <span class="studio-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <div>
                        <h3>Inventory alerts</h3>
                        <p>Set the stock level at which products should be flagged.</p>
                    </div>
                </div>

                <label class="studio-field">
                    Low-stock warning threshold
                    <input type="number" min="1" max="100"
                        name="low_stock_threshold"
                        value="<?= e($vals['low_stock_threshold']) ?>">
                </label>
            </section>

            <div class="studio-save-bar">
                <span><i class="fa-solid fa-shield-halved"></i> Your changes are saved to the database.</span>
                <button class="save studio-save" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> Save changes
                </button>
            </div>
        </form>
    </div>

    <aside class="studio-preview-column">
        <div class="studio-preview-label">
            <span><i class="fa-solid fa-eye"></i> LIVE PREVIEW</span>
            <span class="studio-live-dot"></span>
        </div>

        <div class="studio-preview" id="studio-preview">
            <div class="studio-preview-nav">
                <strong>COLLINS <span>M.</span></strong>
                <small>HOME &nbsp; SHOP &nbsp; CONTACT</small>
            </div>
            <div class="studio-preview-hero">
                <span>NAIROBI'S TRUSTED PHONE SHOP</span>
                <h2 id="preview-heading"><?= e($vals['hero_title']) ?></h2>
                <p id="preview-description"><?= e($vals['hero_subtitle']) ?></p>
                <span class="studio-preview-button">SHOP PHONES <i class="fa-solid fa-arrow-right"></i></span>
            </div>
            <div class="studio-preview-bottom">
    <strong>Featured phones</strong>
    <div class="studio-featured-grid" id="studio-featured-grid">
        <?php if ($previewFeaturedProducts ?? []): ?>
            <?php foreach ($previewFeaturedProducts as $product): ?>
                <div class="studio-phone-card">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= e(image_url($product['image'])) ?>"
                             alt="<?= e($product['brand'] . ' ' . $product['name']) ?>">
                    <?php else: ?>
                        <div class="studio-no-image">Phone image</div>
                    <?php endif; ?>
                    <strong><?= e($product['brand'] . ' ' . $product['name']) ?></strong>
                    <small><?= money($product['price']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="studio-empty-preview">Select featured phones to see them here.</p>
        <?php endif; ?>
    </div>
</div>
<div class="studio-flash-preview">
    <strong><i class="fa-solid fa-bolt"></i> Flash sale</strong>
    <?php if ($previewFlashSales ?? []): ?>
        <div class="studio-flash-grid">
            <?php foreach ($previewFlashSales as $sale): ?>
                <div class="studio-flash-card">
                    <?php if (!empty($sale['image'])): ?>
                        <img src="<?= e(image_url($sale['image'])) ?>"
                             alt="<?= e($sale['brand'] . ' ' . $sale['name']) ?>">
                    <?php endif; ?>
                    <div>
                        <strong><?= e($sale['brand'] . ' ' . $sale['name']) ?></strong>
                        <small><?= money($sale['sale_price']) ?>
                            <del><?= money($sale['regular_price']) ?></del>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="studio-empty-preview">No active flash-sale products right now.</p>
    <?php endif; ?>
</div>

        <p class="studio-preview-note">
            This preview demonstrates your heading, description and primary colour.
            Save your changes to publish them to the storefront.
        </p>
    </aside>
</div>

<script>
(function () {
    const title = document.getElementById('preview-title');
    const subtitle = document.getElementById('preview-subtitle');
    const colour = document.getElementById('preview-colour');
    const previewTitle = document.getElementById('preview-heading');
    const previewSubtitle = document.getElementById('preview-description');
    const preview = document.getElementById('studio-preview');
    const colourCode = document.getElementById('colour-code');

    if (title) {
        title.addEventListener('input', () => {
            previewTitle.textContent = title.value || 'Your store heading';
        });
    }

    if (subtitle) {
        subtitle.addEventListener('input', () => {
            previewSubtitle.textContent = subtitle.value || 'Your store description';
        });
    }

    if (colour) {
        colour.addEventListener('input', () => {
            preview.style.setProperty('--preview-blue', colour.value);
            colourCode.textContent = colour.value.toUpperCase();
        });

        preview.style.setProperty('--preview-blue', colour.value);
    }

    const checks = document.querySelectorAll('input[name="featured_product_ids[]"]');
    const featuredGrid = document.getElementById('studio-featured-grid');

    function renderFeaturedProducts() {
        if (!featuredGrid) return;

        featuredGrid.replaceChildren();
        const selected = Array.from(checks).filter(check => check.checked);

        if (!selected.length) {
            const empty = document.createElement('p');
            empty.className = 'studio-empty-preview';
            empty.textContent = 'Select featured phones to see them here.';
            featuredGrid.appendChild(empty);
            return;
        }

        selected.forEach(check => {
            const product = check.closest('.studio-product');
            if (!product) return;

            const card = document.createElement('div');
            card.className = 'studio-phone-card';

            if (product.dataset.productImage) {
                const img = document.createElement('img');
                img.src = product.dataset.productImage;
                img.alt = product.dataset.productName || 'Phone';
                card.appendChild(img);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'studio-no-image';
                placeholder.textContent = 'Phone image';
                card.appendChild(placeholder);
            }

            const name = document.createElement('strong');
            name.textContent = product.dataset.productName || 'Phone';

            const price = document.createElement('small');
            price.textContent = product.dataset.productPrice || '';

            card.append(name, price);
            featuredGrid.appendChild(card);
        });
    }

    checks.forEach(check => {
        check.addEventListener('change', () => {
            const selected = document.querySelectorAll(
                'input[name="featured_product_ids[]"]:checked'
            );

            if (selected.length > 8) {
                check.checked = false;
                alert('You can select a maximum of 8 featured products.');
            }

            renderFeaturedProducts();
        });
    });

    renderFeaturedProducts();
})();
</script>

<?php require 'footer.php'; ?>

