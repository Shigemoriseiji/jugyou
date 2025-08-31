<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// フォームからのPOST処理
if (isset($_POST['body'])) {
    $insert_sth = $dbh->prepare("INSERT INTO hogehoge (text) VALUES (:body)");
    $insert_sth->execute([
        ':body' => $_POST['body'],
    ]);

    header("HTTP/1.1 302 Found");
    header("Location: ./formtodbtest.php");
    return;
}

// ページング処理
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 全件数取得
$count_sth = $dbh->query("SELECT COUNT(*) FROM hogehoge");
$total_posts = $count_sth->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// 投稿データ取得（降順）
$sth = $dbh->prepare("SELEaCT * FROM hogehoge ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$sth->bindValue(':limit', $limit, PDO::PARAM_INT);
$sth->bindValue(':offset', $offset, PDO::PARAM_INT);
$sth->execute();
$results = $sth->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>フォーム送信と一覧表示</title>
</head>
<body>
    <!-- フォーム -->
    <form method="POST" action="./formtodbtest.php">
        <textarea name="body" required></textarea>
        <button type="submit">送信</button>
    </form>

    <hr>

    <!-- 投稿一覧 -->
    <?php if (count($results) > 0): ?>
        <p><?= $page ?>ページ目 (全 <?= $total_pages ?> ページ中)</p>
        <?php foreach ($results as $row): ?>
            <div style="margin-bottom: 1em;">
                <strong>送信日時</strong><br>
                <?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?><br>
                <strong>送信内容</strong><br>
                <?= nl2br(htmlspecialchars($row['text'], ENT_QUOTES, 'UTF-8')) ?>
            </div>
        <?php endforeach; ?>

        <!-- ページ遷移 -->
        <div>
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">前のページ</a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>" style="margin-left:1em;">次のページ</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>まだ投稿がありません。</p>
    <?php endif; ?>
</body>
</html>


