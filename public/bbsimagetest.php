<?php
// 元の接続そのまま（必要に応じて実環境へ）
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (isset($_POST['body'])) {
  $image_filename = null;

  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // 5MB超はサーバ側でも拒否（php.ini と二重ガード）
    if (
      ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE) ||
      (isset($_FILES['image']['size']) && $_FILES['image']['size'] > 5 * 1024 * 1024)
    ) {
      header("HTTP/1.1 302 Found"); header("Location: ./bbsimagetest.php"); exit;
    }

    // 画像MIMEのみ許可
    if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
      header("HTTP/1.1 302 Found"); header("Location: ./bbsimagetest.php"); exit;
    }

    // 拡張子をサニタイズ & 許可拡張子に丸める
    $ext = strtolower(pathinfo($_FILES['image']['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed, true)) { $ext = 'jpg'; }

    // 保存
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $ext;
    $filepath = '/var/www/upload/image/' . $image_filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
      header("HTTP/1.1 302 Found"); header("Location: ./bbsimagetest.php"); exit;
    }
  }

  // SQLi対策: プリペアド＋バインド
  $stmt = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :img)");
  $stmt->execute([
    ':body' => $_POST['body'],
    ':img'  => $image_filename,
  ]);

  header("HTTP/1.1 302 Found"); header("Location: ./bbsimagetest.php"); exit;
}

// 一覧取得（DB側の created_at は DEFAULT CURRENT_TIMESTAMP を想定）
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>画像付き掲示板</title>
<style>
  /* ===== Simple BBS minimal CSS ===== */
  :root{
    --bg:#fafafa;
    --card:#ffffff;
    --border:#e5e7eb;
    --text:#111827;
    --muted:#6b7280;
    --link:#0a66c2;
    --primary:#2563eb;
  }
  *{ box-sizing:border-box; }
  html,body{ height:100%; }
  body{
    margin:0; background:var(--bg); color:var(--text);
    font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Hiragino Kaku Gothic ProN","Noto Sans JP",sans-serif;
    line-height:1.7;
  }
  .container{ max-width:920px; margin:0 auto; padding:16px; }
  header.site{
    position:sticky; top:0; z-index:10;
    background:var(--bg); border-bottom:1px solid var(--border);
    padding:12px 0; margin-bottom:12px;
  }
  .title{ margin:0; font-size:clamp(20px,4vw,28px); }

  .card{
    background:var(--card); border:1px solid var(--border);
    border-radius:8px; padding:16px;
  }
  form.bbs{ display:grid; gap:12px; }
  label{ color:var(--muted); font-size:14px; }
  textarea{
    width:100%; min-height:7rem; padding:10px 12px;
    border:1px solid var(--border); border-radius:6px; background:#fff; color:inherit; outline:none;
  }
  textarea:focus{ outline:2px solid rgba(37,99,235,.25); outline-offset:2px; }
  input[type="file"]{ width:100%; color:var(--muted); }
  .btn{
    display:inline-block; padding:10px 16px; border-radius:6px;
    border:1px solid var(--primary); background:var(--primary); color:#fff; font-weight:700; cursor:pointer;
  }
  .btn:hover{ filter:brightness(1.05); }

  hr.sep{ border:none; height:1px; margin:20px 0; background:var(--border); }

  dl.entry{
    display:grid; grid-template-columns:5rem 1fr; gap:.25rem .75rem;
    padding:12px; margin:0 0 12px 0;
    background:#fff; border:1px solid var(--border); border-radius:6px;
  }
  dl.entry dt{ color:var(--muted); font-weight:700; }
  dl.entry dd{ margin:0; }
  .entry img{ max-width:100%; height:auto; border:1px solid var(--border); border-radius:6px; }

  /* アンカー/返信ボタン（機能用の最低限） */
  a.anchor{ color:var(--link); text-decoration:none; border-bottom:1px dotted rgba(10,102,194,.4); }
  a.anchor:hover{ text-decoration:underline; }
  .idline{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .idlink{ font-weight:700; text-decoration:none; color:var(--text); }
  .reply-btn{
    padding:2px 8px; font-size:12px; cursor:pointer;
    border:1px solid var(--border); border-radius:6px; background:#f3f4f6; color:#374151;
  }
  .reply-btn:hover{ background:#eef2f7; }

  /* モバイル最適化 */
  @media (max-width:600px){
    .container{ padding:14px; }
    dl.entry{ grid-template-columns:4.5rem 1fr; }
    .title{ font-size:22px; }
  }
</style>
</head>
<body>
  <div class="container">
    <header class="site"><h1 class="title">画像付き掲示板</h1></header>

    <section class="card">
      <form class="bbs" method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
        <label for="body">本文</label>
        <textarea id="body" name="body" required placeholder="本文を入力してください"></textarea>
        <div>
          <label for="image">画像（任意・5MBまで）</label>
          <input type="file" accept="image/*" name="image" id="imageInput">
        </div>
        <button class="btn" type="submit">送信</button>
      </form>
    </section>

    <hr class="sep">

    <?php foreach($select_sth as $entry): ?>
      <dl class="entry" id="p<?= (int)$entry['id'] ?>">
        <dt>ID</dt>
        <dd class="idline">
          <a class="idlink" href="#p<?= (int)$entry['id'] ?>">#<?= (int)$entry['id'] ?></a>
          <button type="button" class="reply-btn" data-reply-id="<?= (int)$entry['id'] ?>">返信</button>
        </dd>

        <dt>日時</dt>
        <dd><?= htmlspecialchars($entry['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>

        <dt>内容</dt>
        <dd>
          <?php
            // 本文：XSS対策（エスケープ+改行保持）
            $bodySafe = nl2br(htmlspecialchars($entry['body'] ?? '', ENT_QUOTES, 'UTF-8'));
            // >>123 を #p123 へのアンカーへ（数字のみ）
            $bodyWithAnchors = preg_replace(
              '/&gt;&gt;(\d{1,7})/',
              '<a class="anchor" href="#p$1">&gt;&gt;$1</a>',
              $bodySafe
            );
            echo $bodyWithAnchors;
          ?>
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
  // レスアンカー補助：「返信」ボタンで >>ID を本文へ
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".reply-btn");
    if (!btn) return;
    const id = btn.getAttribute("data-reply-id");
    const ta = document.getElementById("body");
    if (!ta) return;
    ta.value = (ta.value ? ta.value + "\n" : "") + ">>" + id + " ";
    ta.focus();
    ta.setSelectionRange(ta.value.length, ta.value.length);
  });

  // ブラウザ側 自動縮小（<=5MB） JPEG/PNG/WebP 対応
  document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("imageInput");
    if (!input) return;
    const MAX_BYTES = 5 * 1024 * 1024;

    input.addEventListener("change", async () => {
      if (!input.files || input.files.length === 0) return;
      const file = input.files[0];

      // アニメGIFやSVGは破損しやすいため非対応
      if (!/^image\/(jpeg|png|webp)$/i.test(file.type) || /\.gif$/i.test(file.name)) {
        if (file.size > MAX_BYTES) {
          alert("この形式は自動圧縮に未対応です。5MB以下の画像を選んでください。");
          input.value = "";
        }
        return;
      }
      if (file.size <= MAX_BYTES) return;

      try {
        const blob = await resizeDownToUnder5MB(file, MAX_BYTES);
        if (blob && blob.size <= MAX_BYTES && blob.size < file.size) {
          const newName = (file.name.replace(/\.[^.]+$/, "") || "image") + ".jpg";
          const newFile = new File([blob], newName, { type: "image/jpeg" });
          const dt = new DataTransfer(); dt.items.add(newFile); input.files = dt.files;
        } else {
          alert("自動圧縮で5MB以下にできませんでした。別の画像を選んでください。");
          input.value = "";
        }
      } catch (e) {
        console.error(e);
        alert("画像の圧縮に失敗しました。5MB以下の画像を選んでください。");
        input.value = "";
      }
    });

    async function resizeDownToUnder5MB(file, limitBytes) {
      const img = await fileToImage(file);
      const { width, height } = scaleToMax(img.width, img.height, 2200); // 初期長辺2200px
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d", { alpha:false });
      let w = width, h = height, q = 0.92;

      canvas.width = w; canvas.height = h; ctx.drawImage(img,0,0,w,h);
      for (let step=0; step<9; step++) {
        const blob = await canvasToBlob(canvas, "image/jpeg", q);
        if (blob.size <= limitBytes) return blob;
        if (step % 2 === 1) { // 2回に1回サイズも縮小
          w = Math.max(320, Math.round(w * 0.85));
          h = Math.max(320, Math.round(h * 0.85));
          canvas.width = w; canvas.height = h; ctx.drawImage(img,0,0,w,h);
        }
        q = Math.max(0.5, q - 0.07);
      }
      return await canvasToBlob(canvas, "image/jpeg", q);
    }
    function scaleToMax(w,h,max){ const r = Math.max(w,h)>max ? max/Math.max(w,h) : 1; return {width:Math.round(w*r), height:Math.round(h*r)}; }
    function fileToImage(file){
      return new Promise((resolve,reject)=>{
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = ()=>{ URL.revokeObjectURL(url); resolve(img); };
        img.onerror = (e)=>{ URL.revokeObjectURL(url); reject(e); };
        img.src = url;
      });
    }
    function canvasToBlob(canvas,type,quality){
      return new Promise((resolve)=>canvas.toBlob(resolve,type,quality));
    }
  });
  </script>
</body>
</html>

