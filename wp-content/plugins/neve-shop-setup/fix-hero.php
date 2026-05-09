<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$front_page_id = get_option('page_on_front');
if ($front_page_id) {
    $post = get_post($front_page_id);
    $content = $post->post_content;
    
    // Replace the old image source with the new one
    $content = preg_replace('/<img src=".*?photo-1541643600914-78b084683601.jpg".*?>/', '<img src="/wp-content/uploads/hero.png" alt="Hero Image" style="border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.3);width:100%;height:auto;"/>', $content);
    
    wp_update_post([
        'ID' => $front_page_id,
        'post_content' => $content
    ]);
    echo "Fixed hero image.\n";
}
