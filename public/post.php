<?php
session_start();
require __DIR__ . '/db.php';

function back($msg) {
  $_SESSION['flash'] = $msg;
  header('Location: ./');
  exit;
}

if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
  http_response_code(400);
  exit('Bad Request');
}

$body = trim($_POST['body'] ?? '');
if ($body === '') {
  back('本文は必須です。');
}

$imagePath = null;
$imageMime = null;

if (!empty($_FILES['image']['name'])) {
  if (!isset($_FILES['image']['error']) || is_array($_FILES['image']['error'])) {
    back('画像アップロードに失敗しました。');
  }
  if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    if (in_array($_FILES['image']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
      back('画像が5MBを超えています。');
    }
    back('画像アップロードでエラーが発生しました。');
  }
  if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
    back('画像が5MBを超えています。');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($_FILES['image']['tmp_name']);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
  ];
  if (!isset($allowed[$mime])) {
    back('許可されていない画像形式です（jpg/png/gif）。');
  }

  if (@getimagesize($_FILES['image']['tmp_name']) === false) {
    back('画像が壊れているか不正です。');
  }

  $basename  = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
  $targetDir = __DIR__ . '/uploads';
  if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0775, true);
  }
  $target = $targetDir . '/' . $basename;
  if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    back('画像の保存に失敗しました。');
  }
  $imagePath = 'uploads/' . $basename;
  $imageMime = $mime;
}

$stmt = $pdo->prepare("INSERT INTO posts (body, image_path, image_mime) VALUES (:body, :image_path, :image_mime)");
$stmt->execute([
  ':body'       => $body,
  ':image_path' => $imagePath,
  ':image_mime' => $imageMime,
]);

back('投稿しました。');









