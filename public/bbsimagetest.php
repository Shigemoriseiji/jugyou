<?php
// データベース接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 投稿処理
if (isset($_POST['body'])) {
  $image_filename = null;

  // 画像がアップロードされた場合の処理
  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    if (preg_match('/^image\//', $_FILES['image']['type']) !== 1) {
      // 画像ファイルでない場合はリダイレクト
      header("Location: ./bbsimagetest.php");
      exit;
    }

    // 拡張子取得
    $pathinfo = pathinfo($_FILES['image']['name']);
    $extension = $pathinfo['extension'];

    // 一意なファイル名を生成
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
    $filepath = '/var/www/upload/image/' . $image_filename;

    // ファイルを保存
    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
  }

  // 投稿データをデータベースに保存
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // リダイレクトして再表示
  header("Location: ./bbsimagetest.php");
  exit;
}

// 投稿一覧を取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$entries = [];
if ($select_sth->execute()) {
  $entries = $select_sth->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- 投稿フォーム -->
<form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
  <textarea name="body" required></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="image">
  </div>
  <button type="submit">送信</button>
</form>

<hr>

<!-- 投稿一覧表示 -->
<?php foreach($entries as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID</dt>
    <dd><?= htmlspecialchars($entry['id']) ?></dd>

    <dt>日時</dt>
    <dd><?= htmlspecialchars($entry['created_at']) ?></dd>

    <dt>内容</dt>
    <dd>
      <?= nl2br(htmlspecialchars($entry['body'])) ?>
      <?php if (!empty($entry['image_filename'])): ?>
        <div>
          <img src="/upload/image/<?= htmlspecialchars($entry['image_filename']) ?>" style="max-height: 10em;">
        </div>
      <?php endif; ?>
    </dd>
  </dl>
<?php endforeach; ?>

