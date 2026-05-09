<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$contact_content = '
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"}},"color":{"background":"var(--nss-bg)"}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:var(--nss-bg);padding-top:80px;padding-right:20px;padding-bottom:80px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"700","fontSize":"3rem"}}} -->
<h1 class="wp-block-heading has-text-align-center" style="font-size:3rem;font-weight:700">Get in Touch</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"60px"}},"color":{"text":"var(--nss-text-light)"}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--nss-text-light);margin-bottom:60px">We\'d love to hear from you. Send us a message and we\'ll respond as soon as possible.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"60px","left":"60px"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}},"border":{"radius":"12px"},"color":{"background":"#ffffff"}},"layout":{"type":"default"}} -->
<div class="wp-block-group has-background" style="background-color:#ffffff;padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px;border-radius:12px;box-shadow:var(--nss-shadow-sm)"><!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"600"}}} -->
<h3 class="wp-block-heading" style="font-weight:600">Contact Information</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"20px","bottom":"10px"}}}} -->
<p style="margin-top:20px;margin-bottom:10px"><strong>Email:</strong><br>support@neveshop.com</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"10px"}}}} -->
<p style="margin-bottom:10px"><strong>Phone:</strong><br>+1 (555) 123-4567</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Address:</strong><br>123 Commerce St.<br>Suite 100<br>New York, NY 10001</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"40px","bottom":"40px","left":"40px"}},"border":{"radius":"12px"},"color":{"background":"#ffffff"}},"layout":{"type":"default"}} -->
<div class="wp-block-group has-background" style="background-color:#ffffff;padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px;border-radius:12px;box-shadow:var(--nss-shadow-sm)"><!-- wp:shortcode -->
[nss_contact_form]
<!-- /wp:shortcode --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
';

$pages = get_posts([
    'post_type' => 'page',
    'title' => 'Contact',
    'post_status' => 'publish',
    'numberposts' => -1
]);

if (!empty($pages)) {
    foreach ($pages as $p) {
        wp_update_post([
            'ID' => $p->ID,
            'post_content' => $contact_content
        ]);
        
        // Set Neve meta for full width
        update_post_meta($p->ID, 'neve_meta_enable_content_width', 'on');
        update_post_meta($p->ID, 'neve_meta_container', 'full-width');
        update_post_meta($p->ID, 'neve_meta_sidebar_layout', 'full-width');
        
        echo "Updated existing Contact page ID {$p->ID}.\n";
    }
} else {
    $post_id = wp_insert_post([
        'post_title' => 'Contact',
        'post_content' => $contact_content,
        'post_status' => 'publish',
        'post_type' => 'page',
    ]);
    
    if ($post_id) {
        update_post_meta($post_id, 'neve_meta_enable_content_width', 'on');
        update_post_meta($post_id, 'neve_meta_container', 'full-width');
        update_post_meta($post_id, 'neve_meta_sidebar_layout', 'full-width');
        echo "Created new Contact page ID {$post_id}.\n";
    }
}
