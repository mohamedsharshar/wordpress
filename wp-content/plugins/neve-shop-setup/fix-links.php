<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$front_page_id = get_option('page_on_front');
if ($front_page_id) {
    $post = get_post($front_page_id);
    $content = $post->post_content;
    
    $shop_url = home_url('/shop/');
    $blog_url = home_url('/blog/');
    
    // Replace exact occurrences
    $content = str_replace('href="/shop/"', 'href="' . esc_attr($shop_url) . '"', $content);
    $content = str_replace('href="/blog/"', 'href="' . esc_attr($blog_url) . '"', $content);
    
    wp_update_post([
        'ID' => $front_page_id,
        'post_content' => $content
    ]);
    
    echo "Fixed links on front page.\n";
} else {
    echo "Front page not found.\n";
}
