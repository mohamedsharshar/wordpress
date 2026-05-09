<?php
define('WP_USE_THEMES', false);
require('c:/laragon/www/wordpress/wp-load.php');
$page = get_post(get_option('woocommerce_myaccount_page_id'));
echo "My Account:\n";
echo $page->post_content;

$shop = get_post(get_option('woocommerce_shop_page_id'));
echo "\n\nShop:\n";
echo $shop->post_content;
