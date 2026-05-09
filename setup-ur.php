<?php
define('WP_USE_THEMES', false);
require('c:/laragon/www/wordpress/wp-load.php');

// Disable WooCommerce registration and login on checkout
update_option('woocommerce_enable_myaccount_registration', 'no');
update_option('woocommerce_enable_checkout_login_reminder', 'no');

// Check if User Registration form exists, if not, create one
$forms = get_posts(['post_type' => 'user_registration', 'post_status' => 'publish']);
$form_id = 0;

if (empty($forms)) {
    // We should trigger the plugin's install method or create a basic form manually
    if (class_exists('UR_Install')) {
        UR_Install::install();
        $forms = get_posts(['post_type' => 'user_registration', 'post_status' => 'publish']);
        if (!empty($forms)) {
            $form_id = $forms[0]->ID;
        }
    }
} else {
    $form_id = $forms[0]->ID;
}

echo "Form ID: " . $form_id . "\n";

// Update the login and register links in our nave-shop-setup
// Wait, the nave-shop-setup had an auth modal. 
// Maybe the user wants to use the plugin's page instead of the modal?
// Let's create a Registration page and Login page if they don't exist.

function create_or_get_page($title, $slug, $content) {
    $page = get_page_by_path($slug);
    if (!$page) {
        $page_id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
        return $page_id;
    }
    
    // Update existing page content just in case
    wp_update_post([
        'ID' => $page->ID,
        'post_content' => $content
    ]);
    return $page->ID;
}

$register_page_id = create_or_get_page('Register', 'register', '[user_registration_form id="' . $form_id . '"]');
$login_page_id = create_or_get_page('Login', 'login', '[user_registration_my_account]');

// Make the plugin use this login page
update_option('user_registration_login_options_login_redirect_url', '');
update_option('user_registration_general_setting_login_options', 'default');
update_option('user_registration_my_account_page_id', $login_page_id);

echo "Register page ID: $register_page_id\n";
echo "Login page ID: $login_page_id\n";
