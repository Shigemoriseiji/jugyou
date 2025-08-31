<?php
session_start();
require __DIR__ . '/db.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->query("SELECT id, body, image_path, image_mime, created_at FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self';");
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>シンプル掲示板</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./style.css" rel="stylesheet">
</head>
<body>
  <main class="container">
    <h1>シンプル掲示板</h1>

    <?php if ($flash): ?>
      <div class="flash"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="card">
      <h2>新規投稿</h2>
      <form action="./post.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <label class="label">本文（必須）</label>
        <textarea name="body" required maxlength="10000" placeholder="テキストを入力…"></textarea>

        <label class="label">画像（任意・最大5MB・jpg/png/gif）</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">

        <button type="submit">投稿する</button>
        <p class="note">※ 5MBを超える画像はアップロードできません</p>
      </form>
    </section>

    <section>
      <h2>投稿一覧</h2>
      <?php foreach ($posts as $p): ?>
        <article class="post">
          <div class="meta">
            <span>#<?= (int)$p['id'] ?></span>
            <time><?= htmlspecialchars($p['created_at'], ENT_QUOTES, 'UTF-8') ?></time>
          </div>
          <p class="body"><?= nl2br(htmlspecialchars($p['body'], ENT_QUOTES, 'UTF-8')) ?></p>
          <?php if ($p['image_path']): ?>
            <figure class="image">
              <img src="<?= htmlspecialchars($p['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="upload">
            </figure>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
  </main>
</body>
</html>








