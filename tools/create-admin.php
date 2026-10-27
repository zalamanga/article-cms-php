<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config.php';
$username = appEnv('ADMIN_USERNAME');
$password = appEnv('ADMIN_PASSWORD');
if ($username === '' || strlen($password) < 10 || strlen($password) > 72) {
    fwrite(STDERR, "Isi ADMIN_USERNAME dan ADMIN_PASSWORD 10-72 byte pada environment lokal.\n");
    exit(1);
}
$stmt = $pdo->prepare('INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)');
$stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), '']);
echo "Admin demo dibuat. Hapus ADMIN_PASSWORD dari environment setelah selesai.\n";
