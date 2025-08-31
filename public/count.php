<?php
try {
    // DB接続
    $dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

    // アクセスログの挿入
    $insert_sth = $dbh->prepare("INSERT INTO panda (text) VALUES (:text)");
    $insert_sth->execute([
        ':text' => 'hello world!!!!!!!!!'
    ]);

    // 総アクセス数を取得
    $count_sth = $dbh->query("SELECT COUNT(*) FROM panda");
    $count = $count_sth->fetchColumn();

    echo "現在のアクセス数は " . htmlspecialchars($count, ENT_QUOTES, 'UTF-8') . " です。";
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
}
?>


