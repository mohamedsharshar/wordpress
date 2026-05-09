<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$menu_items = wp_get_nav_menu_items('Shop Navigation');
if ($menu_items) {
    foreach ($menu_items as $item) {
        echo "Menu Item: {$item->title} -> URL: {$item->url}\n";
    }
} else {
    echo "No menu items found for Shop Navigation.\n";
}

$all_menus = wp_get_nav_menus();
foreach ($all_menus as $menu) {
    echo "Found menu: {$menu->name}\n";
    $items = wp_get_nav_menu_items($menu->term_id);
    foreach ($items as $item) {
        echo " - {$item->title} -> {$item->url}\n";
    }
}
