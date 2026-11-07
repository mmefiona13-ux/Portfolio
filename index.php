<?php
require_once __DIR__.'/includes/functions.php';$where=[];$params=[];$q=trim($_GET['q']??'');if($q!==''){$where[]='(p.name LIKE ? OR p.brand LIKE ? OR c.category_name LIKE ?)';array_push($params,"%$q%","%$q%","%$q%");}foreach(['brand'=>['Samsung','Apple'],'condition'=>['New','Used']] as $f=>$allowed)if(in_array($_GET[$f]??'',$allowed,true)){$where[]="p.`$f`=?";$params[]=$_GET[$f];}$category=filter_var($_GET['category']??null,FILTER_VALIDATE_INT);if($category){$where[]='p.category_id=?';$params[]=$category;}$storage=trim($_GET['storage']??'');if($storage!==''){$where[]='p.storage=?';$params[]=$storage;}$quality=trim($_GET['quality']??'');if($quality!==''){$where[]='p.quality LIKE ?';$params[]='%'.$quality.'%';}if(($_GET['availability']??'')==='in')$where[]='p.stock>0';foreach(['min'=>'>=','max'=>'<='] as $f=>$op)if(isset($_GET[$f])&&$_GET[$f]!==''&&is_numeric($_GET[$f])&&(float)$_GET[$f]>=0){$where[]="p.price $op ?";$params[]=(float)$_GET[$f];}$sort=($_GET['sort']??'')==='low'?'p.price ASC':(($_GET['sort']??'')==='high'?'p.price DESC':'p.created_at DESC');$sql='SELECT p.*,c.category_name,(SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY id LIMIT 1) image FROM products p LEFT JOIN categories c ON c.id=p.category_id'.($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY $sort LIMIT 48";$s=db()->prepare($sql);$s->execute($params);$products=$s->fetchAll();$countSql="SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id=p.category_id".($where?" WHERE ".implode(" AND ",$where):"");$countStatement=db()->prepare($countSql);$countStatement->execute($params);$totalProducts=(int)$countStatement->fetchColumn();$categories=db()->query('SELECT * FROM categories ORDER BY brand,category_name')->fetchAll();$storages=db()->query('SELECT DISTINCT storage FROM products ORDER BY storage')->fetchAll(PDO::FETCH_COLUMN);$flash=[];try{$flash=db()->query('SELECT fs.title,fs.ends_at,TIMESTAMPDIFF(SECOND,NOW(),fs.ends_at) remaining_seconds,p.id,p.name,p.brand,p.price regular_price,fsp.sale_price,(SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY id LIMIT 1) image FROM flash_sales fs JOIN flash_sale_products fsp ON fsp.flash_sale_id=fs.id JOIN products p ON p.id=fsp.product_id WHERE fs.active=1 AND fs.starts_at<=NOW() AND fs.ends_at>NOW() ORDER BY fs.ends_at')->fetchAll();}catch(Throwable $e){}$page_title='Genuine Samsung & iPhone Devices';require 'includes/header.php';
$featuredProducts = [];
try {
    $featuredIds = array_values(array_filter(
        array_map('intval', explode(',', setting('featured_product_ids', ''))),
        fn($id) => $id > 0
    ));

    if ($featuredIds) {
        $marks = implode(',', array_fill(0, count($featuredIds), '?'));
        $featuredQuery = db()->prepare("
            SELECT p.*,
                (SELECT image_path FROM product_images
                 WHERE product_id = p.id ORDER BY id LIMIT 1) AS image
            FROM products p
            WHERE p.id IN ($marks)
        ");
        $featuredQuery->execute($featuredIds);
        $featuredRows = $featuredQuery->fetchAll();

        $featuredMap = [];
        foreach ($featuredRows as $row) {
            $featuredMap[(int)$row['id']] = $row;
        }

        foreach ($featuredIds as $id) {
            if (isset($featuredMap[$id])) {
                $featuredProducts[] = $featuredMap[$id];
            }
        }
    }
} catch (Throwable $e) {
    $featuredProducts = [];
}
?>
<?php if(setting('promo_message')!==''):?><div class="promo-bar"><?=e(setting('promo_message'))?></div><?php endif?>
<section class="hero" <?php if(setting('hero_image')):?>style="background-image:linear-gradient(90deg,#06111fd9,#06111f55),url('<?=e(setting('hero_image'))?>')"<?php endif?>><div class="container hero-content"><p class="eyebrow light">Nairobi’s trusted phone plug</p><h1><?=e(setting('hero_title','Upgrade with genuine Samsung & iPhone devices.'))?></h1><p><?=e(setting('hero_subtitle','Explore verified new and carefully selected used phones for every lifestyle and budget.'))?></p><div class="hero-actions"><a class="button primary" href="#shop">Shop phones <i class="fa-solid fa-arrow-right"></i></a><a class="button glass" href="<?=e(whatsapp_url())?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp us</a></div></div></section>
<?php if ($featuredProducts): ?>
<section class="section featured-section" id="featured">
    <div class="container">
        <div class="section-title">
            <div>
                <p class="eyebrow">Handpicked for you</p>
                <h2>Featured phones</h2>
                <p>Explore our recommended Samsung and iPhone devices.</p>
            </div>
        </div>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $p):
                $featuredPrice = product_price((int)$p['id'], (float)$p['price']);
            ?>
            <article class="product-card">
                <a class="product-image" href="product-details.php?id=<?=$p['id']?>">
                    <img loading="lazy" src="<?=image_url($p['image'] ?? '')?>" alt="<?=e($p['brand'].' '.$p['name'])?>">
                </a>
                <div class="product-info">
                    <span class="badge <?=strtolower($p['brand'])?>"><?=e($p['brand'])?></span>
                    <button class="wishlist-toggle" type="button" data-product-id="<?=$p['id']?>" aria-label="Save <?=e($p['name'])?>">♡</button>
                    <h3><a href="product-details.php?id=<?=$p['id']?>"><?=e($p['name'])?></a></h3>
                    <p><?=e($p['storage'])?> · <?=e($p['condition'])?> · <?=e($p['quality'])?></p>
                    <strong><?=money($featuredPrice)?></strong>
                    <?php if ($featuredPrice < (float)$p['price']): ?>
                        <del><?=money($p['price'])?></del>
                    <?php endif; ?>
                    <span class="stock <?=$p['stock'] ? 'in' : 'out'?>">
                        <?=$p['stock'] ? $p['stock'].' in stock' : 'Out of stock'?>
                    </span>
                    <div class="card-actions">
                        <a class="button dark-button" href="product-details.php?id=<?=$p['id']?>">View details</a>
                        <?php if ($p['stock']): ?>
                        <form method="post" action="cart.php">
                            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                            <input type="hidden" name="product_id" value="<?=$p['id']?>">
                            <button class="button primary" name="action" value="add">Add to cart</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<section class="section compact" id="recently-viewed" hidden><div class="container"><div class="section-title"><div><p class="eyebrow">Pick up where you left off</p><h2>Recently viewed</h2></div><div class="slider-controls"><button type="button" data-recent-prev aria-label="Previous">‹</button><button type="button" data-recent-next aria-label="Next">›</button></div></div><div class="recent-track" id="recent-track"></div></div></section>
<?php if($flash):?><section class="section flash-section"><div class="container"><p class="eyebrow">Limited time</p><h2>Flash sales</h2><div class="products-grid"><?php foreach($flash as $f):$discount=(int)round(((float)$f['regular_price']-(float)$f['sale_price'])/(float)$f['regular_price']*100);?><article class="product-card"><a class="product-image" href="product-details.php?id=<?=$f['id']?>"><img src="<?=image_url($f['image'])?>" alt="<?=e($f['name'])?>"></a><div class="product-info"><span class="badge sale-badge">LIMITED OFFER · <?=$discount?>% OFF</span><h3><?=e($f['name'])?></h3><strong><?=money($f['sale_price'])?></strong> <del><?=money($f['regular_price'])?></del><p><?=e($f['title'])?></p><div class="countdown" data-remaining-seconds="<?=max(0,(int)$f['remaining_seconds'])?>">Loading countdown…</div><a class="button primary" href="product-details.php?id=<?=$f['id']?>">Shop deal</a></div></article><?php endforeach?></div><button class="button dark-button" id="flash-load-more" type="button" hidden>Load more</button></div></div></section><?php endif?>
<section class="section" id="shop"><div class="container"><div class="section-title"><div><p class="eyebrow">Find your next phone</p><h2>Phones made to be in your hands.</h2></div></div><form class="filters" id="catalogue-filters" method="get"><input name="q" value="<?=e($q)?>" placeholder="Search phones e.g. iPhone Pro"><select name="brand"><option value="">All brands</option><option value="Samsung" <?=($_GET['brand']??'')==='Samsung'?'selected':''?>>Samsung</option><option value="Apple" <?=($_GET['brand']??'')==='Apple'?'selected':''?>>Apple</option></select><select name="category"><option value="">All categories</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=$category==$c['id']?'selected':''?>><?=e($c['brand'].' — '.$c['category_name'])?></option><?php endforeach?></select><select name="storage"><option value="">Any storage</option><?php foreach($storages as $st):?><option value="<?=e($st)?>" <?=($storage??'')===$st?'selected':''?>><?=e($st)?></option><?php endforeach?></select><input name="quality" value="<?=e($_GET['quality']??'')?>" placeholder="Quality"><select name="condition"><option value="">Any condition</option><option value="New" <?=($_GET['condition']??'')==='New'?'selected':''?>>New</option><option value="Used" <?=($_GET['condition']??'')==='Used'?'selected':''?>>Used</option></select><select name="availability"><option value="">Any availability</option><option value="in" <?=($_GET['availability']??'')==='in'?'selected':''?>>In stock</option></select><input name="min" type="number" min="0" placeholder="Min price" value="<?=e($_GET['min']??'')?>"><input name="max" type="number" min="0" placeholder="Max price" value="<?=e($_GET['max']??'')?>"><select name="sort"><option value="">Newest</option><option value="low" <?=($_GET['sort']??'')==='low'?'selected':''?>>Price: low to high</option><option value="high" <?=($_GET['sort']??'')==='high'?'selected':''?>>Price: high to low</option></select><button class="button primary">Apply</button><button class="button dark-button" type="reset" id="clear-filters">Clear filters</button></form><p id="catalogue-count" aria-live="polite"><?=e((string)$totalProducts)?> products</p><div class="products-grid" id="catalogue-results"><?php foreach($products as $p):$price=product_price((int)$p['id'],(float)$p['price']);?><article class="product-card"><a class="product-image" href="product-details.php?id=<?=$p['id']?>"><img loading="lazy" src="<?=image_url($p['image'])?>" alt="<?=e($p['brand'].' '.$p['name'])?>"></a><div class="product-info"><span class="badge <?=strtolower($p['brand'])?>"><?=e($p['brand'])?></span><button class="wishlist-toggle" type="button" data-product-id="<?=$p['id']?>" aria-label="Save <?=e($p['name'])?>">♡</button><h3><a href="product-details.php?id=<?=$p['id']?>"><?=e($p['name'])?></a></h3><p><?=e($p['storage'])?> · <?=e($p['condition'])?> · <?=e($p['quality'])?></p><strong><?=money($price)?></strong><?php if($price<(float)$p['price']):?><del><?=money($p['price'])?></del><?php endif?><span class="stock <?=$p['stock']?'in':'out'?>"><?=$p['stock']?$p['stock'].' in stock':'Out of stock'?></span><div class="card-actions"><a class="button dark-button" href="product-details.php?id=<?=$p['id']?>">View details</a><?php if($p['stock']):?><form method="post" action="cart.php"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="product_id" value="<?=$p['id']?>"><button class="button primary" name="action" value="add">Add to cart</button></form><?php endif?></div></div></article><?php endforeach?></div><button class="button dark-button" id="load-more" type="button" hidden>Load more</button></div></div></section>
<section class="section dark" id="about"><div class="container"><p class="eyebrow light">The Collins M standard</p><h2>Find the phone that <em>fits your life.</em></h2><p>Genuine Samsung Galaxy and Apple iPhone devices in Nairobi—new and carefully selected used options.</p></div></section><section class="section contact" id="contact"><div class="container"><p class="eyebrow">Let’s get you connected</p><h2>Ready for an upgrade?</h2><p>Ask about a model, colour, storage, or current price.</p><div class="business-details"><div><i class="fa-solid fa-location-dot"></i><span><small>Physical address</small><strong>Nairobi CBD</strong></span></div><div><i class="fa-solid fa-truck-fast"></i><span><small>Delivery</small><strong>Free delivery within Nairobi</strong></span></div><div><i class="fa-regular fa-envelope"></i><span><small>Email</small><strong><a href="mailto:mmefiona13@gmail.com">mmefiona13@gmail.com</a></strong></span></div></div><a class="button whatsapp" target="_blank" rel="noopener" href="<?=e(whatsapp_url())?>"><i class="fa-brands fa-whatsapp"></i> Start WhatsApp inquiry</a></div></section><?php require 'includes/footer.php';?>
