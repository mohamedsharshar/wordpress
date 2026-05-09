<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Require necessary WordPress functions
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

echo "Adding 10 Products...\n";

$products_data = [
    ['title' => 'Minimalist Gold Watch', 'price' => '199.99', 'cat' => 'Accessories', 'img' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Premium Leather Wallet', 'price' => '89.00', 'cat' => 'Accessories', 'img' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Classic Aviator Sunglasses', 'price' => '145.00', 'cat' => 'Accessories', 'img' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Matte Black Backpack', 'price' => '120.00', 'cat' => 'Bags', 'img' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Noise-Cancelling Earbuds', 'price' => '249.00', 'cat' => 'Electronics', 'img' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Ceramic Coffee Dripper', 'price' => '45.00', 'cat' => 'Home', 'img' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Linen Blend T-Shirt', 'price' => '35.00', 'cat' => 'Clothing', 'img' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Suede Chelsea Boots', 'price' => '180.00', 'cat' => 'Footwear', 'img' => 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Wireless Charging Pad', 'price' => '55.00', 'cat' => 'Electronics', 'img' => 'https://images.unsplash.com/photo-1622445275463-afa2ab738c34?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Steel Water Bottle', 'price' => '40.00', 'cat' => 'Accessories', 'img' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=800&q=80']
];

function nss_upload_image_from_url($url, $post_id, $title) {
    $tmp = download_url($url);
    if (is_wp_error($tmp)) return false;
    
    $file_array = [
        'name' => sanitize_title($title) . '.jpg',
        'tmp_name' => $tmp
    ];
    
    $id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        return false;
    }
    return $id;
}

foreach ($products_data as $pd) {
    if (!get_page_by_title($pd['title'], OBJECT, 'product')) {
        $post_id = wp_insert_post([
            'post_title' => $pd['title'],
            'post_content' => 'Premium quality ' . strtolower($pd['title']) . ' designed for the modern lifestyle.',
            'post_status' => 'publish',
            'post_type' => 'product'
        ]);
        
        if ($post_id) {
            update_post_meta($post_id, '_price', $pd['price']);
            update_post_meta($post_id, '_regular_price', $pd['price']);
            
            $cat_id = wp_create_category($pd['cat']);
            wp_set_object_terms($post_id, $cat_id, 'product_cat');
            
            echo "Uploading image for {$pd['title']}...\n";
            $attach_id = nss_upload_image_from_url($pd['img'], $post_id, $pd['title']);
            if ($attach_id) {
                set_post_thumbnail($post_id, $attach_id);
            }
            echo "Added product: {$pd['title']}\n";
        }
    } else {
        echo "Product exists: {$pd['title']}\n";
    }
}

echo "\nAdding 3 Blogs...\n";

$blogs_data = [
    [
        'title' => 'How to Build a Minimalist Wardrobe',
        'cat' => 'Style',
        'img' => 'https://images.unsplash.com/photo-1434389678232-04015f624025?auto=format&fit=crop&w=800&q=80',
        'content' => 'Building a minimalist wardrobe is all about versatility and quality over quantity. Focus on neutral colors, durable fabrics, and classic cuts that never go out of style. Start by decluttering items you haven\'t worn in a year, and invest in staple pieces like a great pair of jeans, a tailored blazer, and comfortable white sneakers.'
    ],
    [
        'title' => 'The Science of Good Sleep',
        'cat' => 'Wellness',
        'img' => 'https://images.unsplash.com/photo-1541781774459-bb2af2f05b55?auto=format&fit=crop&w=800&q=80',
        'content' => 'Quality sleep is the foundation of a healthy lifestyle. Creating a calming nighttime routine is essential. Dim the lights an hour before bed, avoid screens, and invest in high-quality bedding. A slightly cool room and a comfortable mattress can dramatically improve your sleep architecture, leaving you refreshed for the day ahead.'
    ],
    [
        'title' => 'Essential Tech Gadgets for Productivity',
        'cat' => 'Technology',
        'img' => 'https://images.unsplash.com/photo-1504280390227-331ef2914e1c?auto=format&fit=crop&w=800&q=80',
        'content' => 'In today\'s fast-paced world, the right tools can make all the difference. Noise-cancelling headphones are a must for deep work, while a wireless charging pad keeps your desk clutter-free. Don\'t underestimate the power of an ergonomic mouse and a high-resolution monitor to reduce fatigue during long working hours.'
    ]
];

foreach ($blogs_data as $bd) {
    if (!get_page_by_title($bd['title'], OBJECT, 'post')) {
        $cat_id = wp_create_category($bd['cat']);
        $post_id = wp_insert_post([
            'post_title' => $bd['title'],
            'post_content' => '<!-- wp:paragraph --><p>' . $bd['content'] . '</p><!-- /wp:paragraph -->',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_category' => [$cat_id]
        ]);
        
        if ($post_id) {
            echo "Uploading image for {$bd['title']}...\n";
            $attach_id = nss_upload_image_from_url($bd['img'], $post_id, $bd['title']);
            if ($attach_id) {
                set_post_thumbnail($post_id, $attach_id);
            }
            echo "Added blog: {$bd['title']}\n";
        }
    } else {
        echo "Blog exists: {$bd['title']}\n";
    }
}

echo "\nDone!\n";
