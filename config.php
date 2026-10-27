<?php
require_once __DIR__ . '/bootstrap_env.php';
try {
    $dsn = 'mysql:host=' . appEnv('DB_HOST', '127.0.0.1') . ';port=' . appEnv('DB_PORT', '3306') . ';dbname=' . appEnv('DB_NAME') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, appEnv('DB_USER'), appEnv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('Database belum siap. Periksa .env dan impor database/schema.sql.');
}
$conn = $pdo;
$db = $pdo;
if (session_status() === PHP_SESSION_NONE) session_start();

define('UPLOAD_DIR', __DIR__ . '/assets/img/');
function requireLogin() { require_once __DIR__ . '/auth_check.php'; }
function redirect($path) { header('Location: ' . $path); exit; }
function generateSlug($title) { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-'); }
