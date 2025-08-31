<?php
$host = getenv('MYSQL_HOST') ?: 'db';
$db   = getenv('MYSQL_DATABASE') ?: 'bbs';
$user = getenv('MYSQL_USER') ?: 'bbs_user';
$pass = getenv('MYSQL_PASSWORD') ?: 'changeme_app';

$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  http_response_code(500);
  exit('DB接続エラー');
}









