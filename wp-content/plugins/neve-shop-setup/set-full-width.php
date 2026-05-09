<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$front_page_id = get_option('page_on_front');
if ($front_page_id) {
    // Set Neve theme meta to make it full width without container
    update_post_meta($front_page_id, 'neve_meta_enable_content_width', 'on');
    update_post_meta($front_page_id, 'neve_meta_container', 'full-width');
    update_post_meta($front_page_id, 'neve_meta_sidebar_layout', 'full-width');
    echo "Updated page $front_page_id to full width.\n";
} else {
    echo "Front page not found.\n";
}
