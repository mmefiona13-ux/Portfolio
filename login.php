<?php
require_once __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['admin_id'])) { header('Location: admin/dashboard.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
    $password = $_POST['password'] ?? '';
    $statement = db()->prepare('SELECT * FROM admins WHERE email=?');
    $statement->execute([$email]);
    $admin = $statement->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_last_activity'] = time();
        header('Location: admin/dashboard.php');
        exit;
    }
    flash('error', 'Invalid email or password.');
}
$page_title = 'Admin login';
require 'includes/header.php';
?><section class="section"><div class="container auth-card"><p class="eyebrow">Collins M back office</p><h1>Admin sign in</h1><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><label>Email<input required type="email" name="email"></label><label>Password<input required type="password" name="password"></label><button class="button primary">Sign in</button></form></div></section><?php require 'includes/footer.php'; ?>
