<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$pages = [
    'woocommerce_shop_page_id' => 'Shop',
    'woocommerce_cart_page_id' => 'Cart',
    'woocommerce_checkout_page_id' => 'Checkout',
    'woocommerce_myaccount_page_id' => 'My Account'
];

foreach ($pages as $option => $title) {
    $page = get_page_by_title($title);
    if ($page) {
        update_option($option, $page->ID);
        echo "Set $option to ID {$page->ID}\n";
    } else {
        echo "Page '$title' not found!\n";
    }
}
