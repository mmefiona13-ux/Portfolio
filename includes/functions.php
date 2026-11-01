<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

const WHATSAPP_PHONE = '254702664606';
const WHATSAPP_MESSAGE = 'Hello! Thank you for reaching out to us. Are you looking to buy a specific phone model, or do you have questions about our current phones, and pricing? let me know how i can assist you today';

function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money($value): string { return 'KSh ' . number_format((float)$value, 0); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void { $expected=$_SESSION['csrf']??'';$provided=$_POST['csrf']??'';if(!is_string($expected)||$expected===''||!is_string($provided)||$provided===''||!hash_equals($expected,$provided)){http_response_code(419);exit('Invalid request token. Please go back and try again.');} }
function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function show_flash(): void { if (!empty($_SESSION['flash'])) { [$type, $message] = $_SESSION['flash']; unset($_SESSION['flash']); echo '<div class="notice notice-' . e($type) . '">' . e($message) . '</div>'; } }
function image_url(?string $path): string {
    $fallback = 'assets/images/phone-placeholder.svg';
    if (!$path) return $fallback;
    return preg_match('#^(?:assets/images/phones|uploads/products)/[A-Za-z0-9._/-]+$#', $path) ? e($path) : $fallback;
}
function whatsapp_url(): string { return 'https://wa.me/' . WHATSAPP_PHONE . '?text=' . rawurlencode(WHATSAPP_MESSAGE); }
function cart(): array { return $_SESSION['cart'] ?? []; }
function cart_count(): int { return array_sum(array_map('intval', cart())); }
function cart_total(): float { if (!cart()) return 0; $ids=array_keys(cart()); $marks=implode(',',array_fill(0,count($ids),'?')); $s=db()->prepare("SELECT id,price FROM products WHERE id IN ($marks)"); $s->execute($ids); $total=0; foreach($s as $p) $total += product_price((int)$p['id'],(float)$p['price']) * (int)(cart()[$p['id']] ?? 0); return $total; }
function customer_id(): int { return (int)($_SESSION['customer_account_id'] ?? 0); }
function require_customer(): void { if (!customer_id()) { flash('error','Please sign in to view your account.'); header('Location: login.php'); exit; } }
function setting(string $key, string $default=''): string { try { $s=db()->prepare('SELECT setting_value FROM website_settings WHERE setting_key=?');$s->execute([$key]);$v=$s->fetchColumn();return $v===false?$default:(string)$v; } catch(Throwable $e) { return $default; } }
function product_price(int $productId, float $regular): float { try {$s=db()->prepare('SELECT fsp.sale_price FROM flash_sale_products fsp JOIN flash_sales fs ON fs.id=fsp.flash_sale_id WHERE fsp.product_id=? AND fs.active=1 AND fs.starts_at<=NOW() AND fs.ends_at>NOW() ORDER BY fsp.sale_price LIMIT 1');$s->execute([$productId]);$sale=$s->fetchColumn();return $sale!==false?(float)$sale:$regular;}catch(Throwable $e){return $regular;} }
function customer_recently_viewed(int $productId): void { if (!customer_id()) return; try{$pdo=db();$s=$pdo->prepare('INSERT INTO recently_viewed(account_id,product_id,viewed_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE viewed_at=NOW()');$s->execute([customer_id(),$productId]);$pdo->exec('DELETE FROM recently_viewed WHERE account_id='.(int)customer_id().' AND product_id NOT IN (SELECT product_id FROM (SELECT product_id FROM recently_viewed WHERE account_id='.(int)customer_id().' ORDER BY viewed_at DESC LIMIT 3) recent_keep)');}catch(Throwable $e){} }
function require_admin(): void { if (empty($_SESSION['admin_id']) || (int)($_SESSION['admin_last_activity'] ?? 0) < time()-1800) { $_SESSION=[]; session_destroy(); session_start(); flash('error','Please sign in to access the back office.'); header('Location: ../login.php'); exit; } $_SESSION['admin_last_activity']=time(); }
function product_image(int $id): ?string { $s = db()->prepare('SELECT image_path FROM product_images WHERE product_id=? ORDER BY id LIMIT 1'); $s->execute([$id]); return $s->fetchColumn() ?: null; }
function colors_for(int $id): array { $s = db()->prepare('SELECT color_name FROM product_colors WHERE product_id=?'); $s->execute([$id]); return $s->fetchAll(PDO::FETCH_COLUMN); }
function upload_image(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('Image upload failed. Images must be under 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    $dir = __DIR__ . '/../uploads/products'; if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save uploaded image.');
    return 'uploads/products/' . $name;
}
