<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (isset($_POST['body'])) {
  // POSTで送られてくるフォームパラメータ body がある場合

  $image_filename = null;
  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // 5MB超はサーバ側でも拒否（php.ini と二重ガード）
    if (
      ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE) ||
      (isset($_FILES['image']['size']) && $_FILES['image']['size'] > 5 * 1024 * 1024)
    ) {
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php");
      return;
    }

    // アップロードされたものが画像か
    if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php");
      return;
    }

    // 元のファイル名から拡張子を取得（英数字に丸める＆許可拡張子へ）
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?? '');
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed, true)) { $ext = 'jpg'; }

    // 新しいファイル名（重複防止に時間+乱数）
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $ext;
    $filepath =  '/var/www/upload/image/' . $image_filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
  }

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // 処理が終わったらリダイレクトする
  header("HTTP/1.1 302 Found");
  header("Location: ./bbsimagetest.php");
  return;
}

// いままで保存してきたものを取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1"> <!-- スマホ対応 -->
<title>画像付き掲示板</title>
<style>
  :root{
    /* ---- Simple BBS Theme ---- */
    --bg:#f7f9fc;          /* 背景（ごく薄いグレー） */
    --surface:#ffffff;     /* カード */
    --line:#e5e7eb;        /* 罫線 */
    --text:#0f172a;        /* 本文 */
    --muted:#64748b;       /* 補助文字 */
    --accent:#0ea5e9;      /* アクセント */
    --accent-2:#0284c7;
  }

  *{ box-sizing:border-box; }
  html,body{ height:100%; }
  body{
    margin:0;
    color:var(--text);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Hiragino Kaku Gothic ProN","Noto Sans JP", sans-serif;
    line-height:1.7;
    background: var(--bg);
  }

  .container{ max-width:920px; margin:0 auto; padding:clamp(12px,2vw,24px); }

  /* Header */
  header.site{
    display:flex; align-items:center; gap:10px;
    padding:12px 0; margin-bottom:12px;
    border-bottom:1px solid var(--line);
    background:transparent;
    position:sticky; top:0; z-index:10;
    backdrop-filter:saturate(120%) blur(4px);
  }
  .title{ font-size:clamp(20px,4vw,28px); margin:0; }

  /* Card */
  .paper{
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:10px;
    padding:clamp(12px,2vw,18px);
  }

  /* Form */
  form.bbs{ display:grid; gap:12px; }
  textarea[name="body"]{
    width:100%; min-height:7rem;
    padding:10px 12px; font:inherit; font-size:1rem; line-height:1.7;
    border:1px solid var(--line); border-radius:8px; background:#fff; color:var(--text);
    outline:none;
  }
  textarea[name="body"]:focus{ outline:2px solid rgba(14,165,233,.35); outline-offset:2px; }
  input[type="file"]{ display:block; width:100%; color:var(--muted); }
  .btn{
    display:inline-block; padding:10px 16px; border-radius:8px; border:1px solid var(--accent-2);
    background:linear-gradient(180deg, var(--accent), var(--accent-2)); color:#fff; font-weight:700;
    cursor:pointer; transition:filter .15s ease;
  }
  .btn:hover{ filter:brightness(1.05); }

  hr.sep{
    border:none; height:1px; margin:20px 0;
    background: var(--line);
  }

  /* Entries */
  dl.entry{
    display:grid;
    grid-template-columns:5rem 1fr;
    gap:.25rem .75rem;
    padding:12px;
    margin:0 0 12px 0;
    border:1px solid var(--line);
    border-radius:8px;
    background:#fff;
  }
  dl.entry dt{ color:var(--muted); font-weight:700; }
  dl.entry dd{ margin:0; }
  .entry img{
    max-height:12em; width:auto; max-width:100%;
    border-radius:6px; border:1px solid var(--line);
  }

  /* モバイル */
  @media (max-width:600px){
    dl.entry{ grid-template-columns:4.5rem 1fr; }
    .entry img{ max-height:9em; }
  }
</style>
</head>
<body>
  <div class="container">
    <header class="site">
      <h1 class="title">画像付き掲示板</h1>
    </header>

    <section class="paper">
      <!-- フォームのPOST先はこのファイル自身にする -->
      <form class="bbs" method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
        <label for="body" style="color:var(--muted); font-size:14px;">本文</label>
        <textarea id="body" name="body" required placeholder="本文を入力してください"></textarea>
        <div>
          <label for="image" style="color:var(--muted); font-size:14px;">画像（任意・5MBまで）</label>
          <input type="file" accept="image/*" name="image" id="imageInput">
        </div>
        <button class="btn" type="submit">送信</button>
      </form>
    </section>

    <hr class="sep">

    <?php foreach($select_sth as $entry): ?>
      <dl class="entry">
        <dt>ID</dt>
        <dd><?= (int)$entry['id'] ?></dd>

        <dt>日時</dt>
        <dd><?= htmlspecialchars($entry['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>

        <dt>内容</dt>
        <dd>
          <?= nl2br(htmlspecialchars($entry['body'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
          <?php if(!empty($entry['image_filename'])): ?>
          <div style="margin-top:8px;">
            <img src="/image/<?= htmlspecialchars($entry['image_filename'], ENT_QUOTES, 'UTF-8') ?>" alt="">
          </div>
          <?php endif; ?>
        </dd>
      </dl>
    <?php endforeach ?>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
    const imageInput = document.getElementById("imageInput");
    if (!imageInput) return;
    imageInput.addEventListener("change", () => {
      if (imageInput.files.length < 1) return;          // 未選択
      if (imageInput.files[0].size > 5 * 1024 * 1024) { // 5MB超
        alert("5MB以下のファイルを選択してください。");
        imageInput.value = "";
      }
    });
  });
  </script>
</body>
</html>
