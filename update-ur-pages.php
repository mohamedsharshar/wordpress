<?php
define('WP_USE_THEMES', false);
require('c:/laragon/www/wordpress/wp-load.php');

$login_page = get_page_by_path('login');
$register_page = get_page_by_path('register');

if ($login_page) {
    wp_update_post([
        'ID' => $login_page->ID,
        'post_content' => '[user_registration_my_account] <div style="text-align:center; margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 8px;">ليس لديك حساب؟ <a href="' . home_url('/register/') . '" style="color: #6c5ce7; font-weight: 600; text-decoration: none;">سجل حساب جديد الآن</a></div>'
    ]);
}

if ($register_page) {
    // Add login link to register page as well
    $content = $register_page->post_content;
    if (strpos($content, 'سجل الدخول') === false) {
        wp_update_post([
            'ID' => $register_page->ID,
            'post_content' => $content . ' <div style="text-align:center; margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 8px;">لديك حساب بالفعل؟ <a href="' . home_url('/login/') . '" style="color: #6c5ce7; font-weight: 600; text-decoration: none;">سجل الدخول من هنا</a></div>'
        ]);
    }
}

// Ensure the WooCommerce login from checkout is turned off
update_option('woocommerce_enable_myaccount_registration', 'no');
update_option('woocommerce_enable_checkout_login_reminder', 'no');
update_option('woocommerce_enable_signup_and_login_from_checkout', 'no');

echo "Updated pages successfully.";
