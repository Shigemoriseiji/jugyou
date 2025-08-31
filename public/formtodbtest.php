<?php
// データベース接続
$dsn = 'mysql:host=mysql;dbname=example_db;charset=utf8mb4';
$user = 'root';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo '接続失敗: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

// データ取得：created_at の降順で取得
$sql = 'SELECT text, created_at FROM hogehoge ORDER BY created_at DESC';
$sth = $dbh->prepare($sql);
$sth->execute();
$results = $sth->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿一覧</title>
</head>
<body>
    <h1>投稿一覧</h1>
    <table border="1">
        <tr>
            <th>内容</th>
            <th>投稿日時</th>
        </tr>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['text'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>


