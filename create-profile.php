<?php
define('WP_USE_THEMES', false);
require('c:/laragon/www/wordpress/wp-load.php');

$p = get_page_by_path('profile');
if(!$p) {
    wp_insert_post([
        'post_title'=>'Profile',
        'post_name'=>'profile',
        'post_content'=>'[user_registration_my_account]',
        'post_status'=>'publish',
        'post_type'=>'page'
    ]);
    echo 'Created profile page.';
} else {
    echo 'Profile page exists.';
}
