<?php
declare(strict_types=1);
require __DIR__ . '/config/database.php';
$statement = db()->prepare('SELECT password FROM admins WHERE email = ?');
$statement->execute(['mmefiona13@gmail.com']);
echo password_verify('#Fionaweb2026', (string)$statement->fetchColumn()) ? 'PASSWORD_VERIFY=PASS' : 'PASSWORD_VERIFY=FAIL';
