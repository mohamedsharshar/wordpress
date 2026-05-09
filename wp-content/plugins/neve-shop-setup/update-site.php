<?php
/**
 * Site Update Script
 * Run via WP-CLI: wp eval-file wp-content/plugins/neve-shop-setup/update-site.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    define('WP_USE_THEMES', false);
    require_once('../../../wp-load.php');
}

echo "Starting site update...\n";

// Require necessary WordPress functions
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

// 1. Attach images to old products
$products = get_posts([
    'post_type' => 'product',
    'numberposts' => -1,
    'post_status' => 'publish',
]);

$generated_images_dir = 'C:\\Users\\moham.MSI-KATANA\\.gemini\\antigravity\\brain\\56b4200e-9bdd-473b-ab21-025895650d1a\\';
// We need to find the exact filenames of the generated images
$files = glob($generated_images_dir . '*.png');

$image_map = [];
foreach ($files as $file) {
    if (strpos($file, 'leather_messenger_bag') !== false) $image_map['leather_messenger_bag'] = $file;
    if (strpos($file, 'aromatic_candle_set') !== false) $image_map['aromatic_candle_set'] = $file;
    if (strpos($file, 'wireless_headphones') !== false) $image_map['wireless_headphones'] = $file;
    if (strpos($file, 'ceramic_mug_set') !== false) $image_map['ceramic_mug_set'] = $file;
    if (strpos($file, 'bamboo_desk_organizer') !== false) $image_map['bamboo_desk_organizer'] = $file;
    if (strpos($file, 'yoga_mat_premium') !== false) $image_map['yoga_mat_premium'] = $file;
}

echo "Found " . count($image_map) . " generated product images.\n";

$assigned_count = 0;
foreach ($products as $product) {
    if (!has_post_thumbnail($product->ID)) {
        echo "Product ID {$product->ID} ('{$product->post_title}') has no image.\n";
        
        // Pick an image based on title or randomly
        $title = strtolower($product->post_title);
        $selected_image_key = null;
        
        if (strpos($title, 'headphone') !== false) $selected_image_key = 'wireless_headphones';
        elseif (strpos($title, 'bag') !== false) $selected_image_key = 'leather_messenger_bag';
        elseif (strpos($title, 'candle') !== false) $selected_image_key = 'aromatic_candle_set';
        elseif (strpos($title, 'mug') !== false) $selected_image_key = 'ceramic_mug_set';
        elseif (strpos($title, 'desk') !== false || strpos($title, 'organizer') !== false) $selected_image_key = 'bamboo_desk_organizer';
        elseif (strpos($title, 'yoga') !== false || strpos($title, 'mat') !== false) $selected_image_key = 'yoga_mat_premium';
        else {
            // Default to something if no match
            $keys = array_keys($image_map);
            $selected_image_key = $keys[array_rand($keys)];
        }
        
        if ($selected_image_key && isset($image_map[$selected_image_key])) {
            $image_path = $image_map[$selected_image_key];
            
            // Upload and attach
            $upload_dir = wp_upload_dir();
            $filename = sanitize_file_name(basename($image_path));
            $dest_path = $upload_dir['path'] . '/' . $filename;
            
            if (copy($image_path, $dest_path)) {
                $filetype = wp_check_filetype($filename, null);
                $attachment_id = wp_insert_attachment([
                    'guid' => $upload_dir['url'] . '/' . $filename,
                    'post_mime_type' => $filetype['type'],
                    'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                    'post_content' => '',
                    'post_status' => 'inherit',
                ], $dest_path, $product->ID);
                
                if (!is_wp_error($attachment_id)) {
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $dest_path);
                    wp_update_attachment_metadata($attachment_id, $attach_data);
                    set_post_thumbnail($product->ID, $attachment_id);
                    echo "  -> Attached image: $selected_image_key\n";
                    $assigned_count++;
                }
            }
        }
    }
}

// 2. Create Blog Posts
echo "Creating blog posts...\n";
$blog_images = [];
foreach ($files as $file) {
    if (strpos($file, 'blog_fashion_trends') !== false) $blog_images['fashion'] = $file;
    if (strpos($file, 'blog_skincare_routine') !== false) $blog_images['skincare'] = $file;
    if (strpos($file, 'blog_sustainable_living') !== false) $blog_images['sustainable'] = $file;
}

$blogs = [
    [
        'title' => 'Top Fashion Trends for the Upcoming Season',
        'content' => '<!-- wp:paragraph --><p>As the seasons change, so do the trends in the fashion world. This year is all about combining comfort with effortless style. From oversized silhouettes to bold statement accessories, there’s something for everyone to incorporate into their daily wardrobe.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>One of the standout trends we’re seeing is a return to natural textures. Leather, linen, and silk are making a massive comeback, offering both durability and a premium feel. When it comes to accessories, minimalist watches and classic wayfarer sunglasses are the perfect finishing touches to any outfit.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Don’t be afraid to mix and match! Pair a structured leather messenger bag with relaxed denim for a look that’s both put-together and approachable. Stay tuned for more style guides as we navigate the new season.</p><!-- /wp:paragraph -->',
        'image_key' => 'fashion',
        'category' => 'Style'
    ],
    [
        'title' => 'The Ultimate Minimalist Skincare Routine',
        'content' => '<!-- wp:paragraph --><p>In a world of endless beauty products and complicated 10-step routines, sometimes less truly is more. A minimalist skincare routine focuses on high-quality, efficacious ingredients that deliver results without overwhelming your skin (or your schedule).</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>The foundation of any good routine is hydration and protection. A potent hyaluronic acid serum can work wonders for plumping the skin and retaining moisture. Follow this up with a reliable moisturizer and, of course, daily SPF.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Remember, consistency is key. By stripping back your routine to the essentials, you allow your skin to find its natural balance while saving time and money. Discover our curated selection of premium skincare essentials designed for the modern minimalist.</p><!-- /wp:paragraph -->',
        'image_key' => 'skincare',
        'category' => 'Beauty'
    ],
    [
        'title' => 'Simple Steps Towards Sustainable Living',
        'content' => '<!-- wp:paragraph --><p>Sustainable living doesn’t have to mean completely overhauling your lifestyle overnight. It’s about making conscious, incremental choices that benefit both you and the planet. Small changes can collectively make a massive impact.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Start by swapping out single-use plastics for durable alternatives. A high-quality insulated water bottle is a perfect example—it keeps your drinks cold for 24 hours while keeping countless plastic bottles out of landfills. Similarly, opting for a reusable canvas tote bag for your shopping reduces waste significantly.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Look around your home or office. Can you replace plastic desk organizers with bamboo alternatives? Can you choose products with eco-friendly packaging? By being mindful of our purchases, we can foster a healthier environment for generations to come.</p><!-- /wp:paragraph -->',
        'image_key' => 'sustainable',
        'category' => 'Lifestyle'
    ]
];

foreach ($blogs as $blog) {
    $existing = get_page_by_title($blog['title'], OBJECT, 'post');
    if (!$existing) {
        // Create category
        $cat_id = wp_create_category($blog['category']);
        
        $post_id = wp_insert_post([
            'post_title' => $blog['title'],
            'post_content' => $blog['content'],
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_category' => [$cat_id]
        ]);
        
        if ($post_id && !is_wp_error($post_id)) {
            echo "Created blog post: {$blog['title']}\n";
            
            // Attach image
            if (isset($blog['image_key']) && isset($blog_images[$blog['image_key']])) {
                $image_path = $blog_images[$blog['image_key']];
                $upload_dir = wp_upload_dir();
                $filename = sanitize_file_name(basename($image_path));
                $dest_path = $upload_dir['path'] . '/' . $filename;
                
                if (copy($image_path, $dest_path)) {
                    $filetype = wp_check_filetype($filename, null);
                    $attachment_id = wp_insert_attachment([
                        'guid' => $upload_dir['url'] . '/' . $filename,
                        'post_mime_type' => $filetype['type'],
                        'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                        'post_content' => '',
                        'post_status' => 'inherit',
                    ], $dest_path, $post_id);
                    
                    if (!is_wp_error($attachment_id)) {
                        $attach_data = wp_generate_attachment_metadata($attachment_id, $dest_path);
                        wp_update_attachment_metadata($attachment_id, $attach_data);
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }
            }
        }
    } else {
        echo "Blog post already exists: {$blog['title']}\n";
    }
}

// 3. Update Homepage Content to be a Professional Modern E-commerce layout
echo "Updating homepage content...\n";

// Get latest products
$latest_products = get_posts([
    'post_type' => 'product',
    'numberposts' => 4,
    'post_status' => 'publish',
]);

$product_ids = implode(',', array_map(function($p) { return $p->ID; }, $latest_products));

$homepage_content = '
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"},"margin":{"top":"0","bottom":"0"}},"color":{"background":"var(--nss-dark)","text":"#ffffff"}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull has-text-color has-background" style="background-color:var(--nss-dark);color:#ffffff;margin-top:0;margin-bottom:0;padding-top:80px;padding-right:20px;padding-bottom:80px;padding-left:20px"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"40px","left":"40px"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"3.5rem","lineHeight":"1.2","fontWeight":"700"}}} -->
<h1 class="wp-block-heading" style="font-size:3.5rem;font-weight:700;line-height:1.2">Discover True<br><span style="color:var(--nss-primary-light);">Premium Style</span></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"top":"20px","bottom":"30px"}},"color":{"text":"#cbd5e1"}}} -->
<p class="has-text-color" style="color:#cbd5e1;margin-top:20px;margin-bottom:30px;font-size:1.125rem">Elevate your lifestyle with our curated collection of high-end accessories, footwear, and essentials designed for the modern trendsetter.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","textColor":"white","style":{"border":{"radius":"8px"}},"className":"is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background wp-element-button" href="/shop/" style="border-radius:8px;padding:12px 24px;font-weight:600;">Shop the Collection</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"nss-hero-image"} -->
<figure class="wp-block-image size-large nss-hero-image"><img src="/wp-content/uploads/photo-1541643600914-78b084683601.jpg" alt="Hero Image" style="border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.3);"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"20px","right":"20px"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:60px;padding-right:20px;padding-bottom:60px;padding-left:20px"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-weight:700">Featured Products</h2>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-wide","style":{"spacing":{"margin":{"top":"20px","bottom":"40px"}}}} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" style="margin-top:20px;margin-bottom:40px"/>
<!-- /wp:separator -->

<!-- wp:shortcode -->
[products limit="4" columns="4" orderby="id" order="DESC" visibility="visible"]
<!-- /wp:shortcode -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"40px"}}}} -->
<div class="wp-block-buttons" style="margin-top:40px"><!-- wp:button {"style":{"border":{"radius":"8px"}},"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/shop/" style="border-radius:8px">View All Products</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"20px","right":"20px"},"margin":{"top":"0","bottom":"0"}},"color":{"background":"var(--nss-bg)"}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:var(--nss-bg);margin-top:0;margin-bottom:0;padding-top:60px;padding-right:20px;padding-bottom:60px;padding-left:20px"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-weight:700">Latest from the Blog</h2>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-wide","style":{"spacing":{"margin":{"top":"20px","bottom":"40px"}}}} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" style="margin-top:20px;margin-bottom:40px"/>
<!-- /wp:separator -->

<!-- wp:latest-posts {"displayPostDate":true,"displayFeaturedImage":true,"featuredImageAlign":"center","featuredImageSizeSlug":"medium"} /-->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"40px"}}}} -->
<div class="wp-block-buttons" style="margin-top:40px"><!-- wp:button {"style":{"border":{"radius":"8px"}},"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/blog/" style="border-radius:8px">Read More Articles</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"},"margin":{"top":"0","bottom":"0"}},"color":{"background":"var(--nss-primary)","text":"#ffffff"}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull has-text-color has-background" style="background-color:var(--nss-primary);color:#ffffff;margin-top:0;margin-bottom:0;padding-top:80px;padding-right:20px;padding-bottom:80px;padding-left:20px"><!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"100%"} -->
<div class="wp-block-column" style="flex-basis:100%"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-weight:700">Join Our Newsletter</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"30px"}}}} -->
<p class="has-text-align-center" style="margin-bottom:30px">Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<form style="display:flex; max-width:500px; margin:0 auto; gap:10px;">
  <input type="email" placeholder="Enter your email" style="flex-grow:1; padding:12px 16px; border-radius:8px; border:none; outline:none; font-family:\'Inter\', sans-serif;">
  <button type="button" style="padding:12px 24px; border-radius:8px; border:none; background:#1a1a2e; color:#fff; font-weight:600; cursor:pointer; font-family:\'Inter\', sans-serif;">Subscribe</button>
</form>
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
';

$front_page_id = get_option('page_on_front');
if ($front_page_id) {
    wp_update_post([
        'ID' => $front_page_id,
        'post_content' => $homepage_content
    ]);
    echo "Updated front page content.\n";
} else {
    // Create a new homepage
    $home_page_id = wp_insert_post([
        'post_title' => 'Home',
        'post_content' => $homepage_content,
        'post_status' => 'publish',
        'post_type' => 'page',
    ]);
    if ($home_page_id && !is_wp_error($home_page_id)) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_page_id);
        echo "Created and set new front page.\n";
    }
}

// Ensure there is a Blog page
$blog_page_id = get_option('page_for_posts');
if (!$blog_page_id) {
    $blog_page = get_page_by_title('Blog');
    if ($blog_page) {
        update_option('page_for_posts', $blog_page->ID);
        echo "Set existing Blog page for posts.\n";
    } else {
        $new_blog_page_id = wp_insert_post([
            'post_title' => 'Blog',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
        if ($new_blog_page_id && !is_wp_error($new_blog_page_id)) {
            update_option('page_for_posts', $new_blog_page_id);
            echo "Created and set new Blog page.\n";
        }
    }
}

echo "Site update complete!\n";
