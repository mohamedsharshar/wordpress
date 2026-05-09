<?php
/**
 * Product Import Script
 * 
 * Run via: WP Admin > Tools > Import Products (added by neve-shop-setup)
 * Or via WP-CLI: wp eval-file wp-content/plugins/neve-shop-setup/import-products.php
 * Or via browser: /wp-admin/admin.php?page=nss-import-products&run=1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register admin page for product import
 */
function nss_register_import_page() {
    add_management_page(
        'Import Products',
        'Import Products',
        'manage_options',
        'nss-import-products',
        'nss_import_products_page'
    );
}
add_action( 'admin_menu', 'nss_register_import_page' );

/**
 * Render the import page
 */
function nss_import_products_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }

    echo '<div class="wrap">';
    echo '<h1>Import Product Images</h1>';

    if ( isset( $_GET['run'] ) && $_GET['run'] === '1' ) {
        check_admin_referer( 'nss_import_products' );
        echo '<pre style="background:#1e1e1e;color:#0f0;padding:20px;border-radius:8px;font-size:14px;max-height:600px;overflow:auto;">';
        nss_run_product_import();
        echo '</pre>';
        echo '<p><a href="' . admin_url( 'edit.php?post_type=product' ) . '" class="button button-primary">View Products</a></p>';
    } else {
        $nonce_url = wp_nonce_url( admin_url( 'tools.php?page=nss-import-products&run=1' ), 'nss_import_products' );
        echo '<p>This will import 14 products from your <code>prod_images</code> folder and attach them as product thumbnails.</p>';
        echo '<p><strong>Source:</strong> <code>C:\Users\moham.MSI-KATANA\Downloads\prod_images</code></p>';
        echo '<a href="' . esc_url( $nonce_url ) . '" class="button button-primary button-hero">🚀 Import Products Now</a>';
    }

    echo '</div>';
}

/**
 * Main import function
 */
function nss_run_product_import() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo "❌ WooCommerce is not active. Please activate it first.\n";
        return;
    }

    // Require necessary WordPress functions
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $source_dir = 'C:\\Users\\moham.MSI-KATANA\\Downloads\\prod_images';

    if ( ! is_dir( $source_dir ) ) {
        echo "❌ Source directory not found: $source_dir\n";
        return;
    }

    // Product catalog - mapped from actual image content
    $products = [
        [
            'file'        => 'photo-1491553895911-0055eca6402d.jfif',
            'title'       => 'Velocity Runner Sneakers',
            'description' => 'Elevate your stride with the Velocity Runner Sneakers. Featuring a breathtaking gradient colorway in sunset orange and coral, these sneakers combine a lightweight mesh upper with responsive cushioning for unmatched comfort. The sculpted sole delivers superior traction on any surface.',
            'short_desc'  => 'Lightweight mesh sneakers with sunset orange gradient and responsive cushioning.',
            'price'       => '149.99',
            'sale_price'  => '119.99',
            'category'    => 'Footwear',
            'tags'        => ['sneakers', 'running', 'athletic', 'orange'],
            'sku'         => 'NSS-FW-001',
        ],
        [
            'file'        => 'photo-1503602642458-232111445657.jfif',
            'title'       => 'Heritage Leather Wristwatch',
            'description' => 'Timeless sophistication meets modern craftsmanship. This Heritage Leather Wristwatch features a minimalist dial with elegant hour markers, encased in a brushed stainless steel body. The supple genuine leather strap ages beautifully, developing a unique patina over time.',
            'short_desc'  => 'Minimalist stainless steel watch with genuine leather strap.',
            'price'       => '279.99',
            'sale_price'  => '',
            'category'    => 'Accessories',
            'tags'        => ['watch', 'leather', 'luxury', 'minimalist'],
            'sku'         => 'NSS-AC-001',
        ],
        [
            'file'        => 'photo-1505740420928-5e560c06d30e.jfif',
            'title'       => 'Studio Pro Over-Ear Headphones',
            'description' => 'Immerse yourself in studio-quality sound with the Studio Pro Over-Ear Headphones. Featuring premium memory foam ear cushions, a gold-accented design, and powerful 40mm drivers that deliver crystal-clear audio across the entire frequency spectrum. Perfect for audiophiles and music producers alike.',
            'short_desc'  => 'Premium over-ear headphones with 40mm drivers and memory foam cushions.',
            'price'       => '349.99',
            'sale_price'  => '299.99',
            'category'    => 'Electronics',
            'tags'        => ['headphones', 'audio', 'premium', 'studio'],
            'sku'         => 'NSS-EL-001',
        ],
        [
            'file'        => 'photo-1523275335684-37898b6baf30.jfif',
            'title'       => 'Aura Noir Eau de Parfum',
            'description' => 'A captivating fragrance that commands attention. Aura Noir opens with crisp bergamot and black pepper, settling into a heart of smoky oud and leather, with a warm base of amber and vanilla. The sleek amber bottle with its minimalist design is as refined as the scent within.',
            'short_desc'  => 'Bold eau de parfum with notes of oud, leather, and amber.',
            'price'       => '189.99',
            'sale_price'  => '',
            'category'    => 'Beauty',
            'tags'        => ['perfume', 'fragrance', 'luxury', 'unisex'],
            'sku'         => 'NSS-BT-001',
        ],
        [
            'file'        => 'photo-1526170375885-4d8ecf77b99f.jfif',
            'title'       => 'ProVision 50mm Camera Lens',
            'description' => 'Capture life in stunning detail with the ProVision 50mm Camera Lens. Engineered with premium optical glass and multi-coated elements, this lens delivers razor-sharp images with beautiful bokeh. The fast f/1.4 aperture excels in low-light conditions, making it the perfect companion for portrait and street photography.',
            'short_desc'  => 'Professional 50mm f/1.4 lens with multi-coated optical glass.',
            'price'       => '599.99',
            'sale_price'  => '499.99',
            'category'    => 'Electronics',
            'tags'        => ['camera', 'lens', 'photography', 'professional'],
            'sku'         => 'NSS-EL-002',
        ],
        [
            'file'        => 'photo-1541643600914-78b084683601.jfif',
            'title'       => 'Raw Selvedge Denim Jeans',
            'description' => 'Crafted from authentic Japanese selvedge denim, these Raw Selvedge Jeans are a wardrobe cornerstone. The 14oz indigo fabric softens and fades uniquely with wear, creating a pair that\'s truly yours. Features a classic straight fit with reinforced stitching and copper rivets at stress points.',
            'short_desc'  => 'Japanese selvedge denim with classic straight fit and copper rivets.',
            'price'       => '199.99',
            'sale_price'  => '',
            'category'    => 'Clothing',
            'tags'        => ['jeans', 'denim', 'selvedge', 'premium'],
            'sku'         => 'NSS-CL-001',
        ],
        [
            'file'        => 'photo-1542291026-7eec264c27ff.jfif',
            'title'       => 'Crimson Blaze Sport Trainers',
            'description' => 'Ignite your workout with the Crimson Blaze Sport Trainers. These head-turning sneakers feature an aerodynamic silhouette in bold crimson red with a signature swoosh. The engineered knit upper provides a sock-like fit while the articulated sole offers maximum flexibility during high-intensity training.',
            'short_desc'  => 'Bold crimson trainers with engineered knit upper and flexible sole.',
            'price'       => '169.99',
            'sale_price'  => '139.99',
            'category'    => 'Footwear',
            'tags'        => ['sneakers', 'sport', 'training', 'red'],
            'sku'         => 'NSS-FW-002',
        ],
        [
            'file'        => 'photo-1560343090-f0409e92791a.jfif',
            'title'       => 'Seafoam Suede Brogue Oxfords',
            'description' => 'Make a statement with these Seafoam Suede Brogue Oxfords. Handcrafted from premium Italian suede in a striking teal colorway, featuring intricate brogue detailing and a natural leather sole. The perfect fusion of classic British shoemaking tradition and contemporary color confidence.',
            'short_desc'  => 'Italian suede brogues in teal with hand-perforated detailing.',
            'price'       => '249.99',
            'sale_price'  => '',
            'category'    => 'Footwear',
            'tags'        => ['oxford', 'brogue', 'suede', 'formal'],
            'sku'         => 'NSS-FW-003',
        ],
        [
            'file'        => 'photo-1572635196237-14b3f281503f.jfif',
            'title'       => 'Noir Polarized Sunglasses',
            'description' => 'Shield your eyes in iconic style with the Noir Polarized Sunglasses. These classic wayfarers feature jet-black acetate frames with polarized lenses that eliminate glare while providing 100% UV protection. The timeless design ensures these shades never go out of fashion.',
            'short_desc'  => 'Classic wayfarer sunglasses with polarized lenses and UV protection.',
            'price'       => '159.99',
            'sale_price'  => '129.99',
            'category'    => 'Accessories',
            'tags'        => ['sunglasses', 'polarized', 'wayfarer', 'UV-protection'],
            'sku'         => 'NSS-AC-002',
        ],
        [
            'file'        => 'photo-1583394838336-acd977736f90.jfif',
            'title'       => 'Apex Wired Studio Headphones',
            'description' => 'Experience uncompromised audio fidelity with the Apex Wired Studio Headphones. Designed for critical listening, these headphones deliver a flat, accurate frequency response that reveals every nuance in your music. The cushioned headband and breathable ear pads ensure comfort during long studio sessions.',
            'short_desc'  => 'Professional wired headphones with flat frequency response for studio use.',
            'price'       => '229.99',
            'sale_price'  => '',
            'category'    => 'Electronics',
            'tags'        => ['headphones', 'wired', 'studio', 'professional'],
            'sku'         => 'NSS-EL-003',
        ],
        [
            'file'        => 'photo-1602143407151-7111542de6e8.jfif',
            'title'       => 'EcoVault Insulated Water Bottle',
            'description' => 'Stay hydrated sustainably with the EcoVault Insulated Water Bottle. The double-wall vacuum insulation keeps drinks cold for 24 hours or hot for 12. Finished in a sophisticated matte forest green with a leak-proof twist cap, this BPA-free bottle is your perfect daily companion.',
            'short_desc'  => 'Double-wall vacuum insulated bottle in matte forest green, 24hr cold.',
            'price'       => '39.99',
            'sale_price'  => '34.99',
            'category'    => 'Lifestyle',
            'tags'        => ['water-bottle', 'eco-friendly', 'insulated', 'travel'],
            'sku'         => 'NSS-LF-001',
        ],
        [
            'file'        => 'premium_photo-1664392147011-2a720f214e01.jfif',
            'title'       => 'Sienna Saddle Crossbody Bag',
            'description' => 'Timeless elegance in every stitch. The Sienna Saddle Crossbody Bag is crafted from full-grain calfskin leather in a rich camel tone, featuring a signature saddle flap closure with gold-tone hardware. The adjustable strap converts from crossbody to shoulder carry, while the organized interior keeps your essentials in order.',
            'short_desc'  => 'Full-grain calfskin crossbody with saddle flap and gold hardware.',
            'price'       => '389.99',
            'sale_price'  => '329.99',
            'category'    => 'Accessories',
            'tags'        => ['bag', 'leather', 'crossbody', 'designer'],
            'sku'         => 'NSS-AC-003',
        ],
        [
            'file'        => 'premium_photo-1670537994863-5ad53a3214e0.jfif',
            'title'       => 'Luminance Hyaluronic Serum',
            'description' => 'Unlock radiant, youthful skin with the Luminance Hyaluronic Serum. This lightweight formula combines triple-weight hyaluronic acid with vitamin C and niacinamide to deeply hydrate, brighten, and smooth fine lines. The frosted glass dropper bottle preserves the potency of every active ingredient.',
            'short_desc'  => 'Triple-weight hyaluronic acid serum with vitamin C and niacinamide.',
            'price'       => '79.99',
            'sale_price'  => '64.99',
            'category'    => 'Beauty',
            'tags'        => ['skincare', 'serum', 'hyaluronic', 'anti-aging'],
            'sku'         => 'NSS-BT-002',
        ],
        [
            'file'        => 'premium_photo-1677541205130-51e60e937318.jfif',
            'title'       => 'Velvet Rouge Matte Lipstick',
            'description' => 'Make a bold impression with Velvet Rouge Matte Lipstick. This richly pigmented formula delivers an intense, true-red hue in a single swipe with a luxuriously matte finish that lasts up to 12 hours. Infused with vitamin E and jojoba oil to keep lips moisturized and comfortable all day.',
            'short_desc'  => 'Long-wear matte lipstick in true red with 12-hour staying power.',
            'price'       => '34.99',
            'sale_price'  => '29.99',
            'category'    => 'Beauty',
            'tags'        => ['lipstick', 'matte', 'makeup', 'long-wear'],
            'sku'         => 'NSS-BT-003',
        ],
    ];

    echo "═══════════════════════════════════════════════════\n";
    echo "   🛍️  NEVE SHOP - PRODUCT IMPORT\n";
    echo "═══════════════════════════════════════════════════\n\n";
    echo "📁 Source: $source_dir\n";
    echo "📦 Products to import: " . count( $products ) . "\n\n";

    // First, delete any existing sample products created by previous plugin activation
    $existing = get_posts([
        'post_type'   => 'product',
        'numberposts' => -1,
        'post_status' => 'any',
        'meta_key'    => '_nss_imported',
        'meta_value'  => '1',
    ]);

    if ( ! empty( $existing ) ) {
        echo "🗑️  Cleaning up " . count( $existing ) . " previously imported products...\n";
        foreach ( $existing as $post ) {
            // Delete thumbnail attachment too
            $thumb_id = get_post_thumbnail_id( $post->ID );
            if ( $thumb_id ) {
                wp_delete_attachment( $thumb_id, true );
            }
            wp_delete_post( $post->ID, true );
        }
        echo "   ✅ Cleanup complete.\n\n";
    }

    $success_count = 0;
    $error_count   = 0;

    foreach ( $products as $i => $product ) {
        $num = $i + 1;
        echo "───────────────────────────────────────────────────\n";
        echo "[$num/14] 📷 {$product['title']}\n";

        $image_path = $source_dir . '\\' . $product['file'];

        if ( ! file_exists( $image_path ) ) {
            echo "   ❌ Image not found: {$product['file']}\n";
            $error_count++;
            continue;
        }

        // --- Step 1: Create / ensure product category ---
        $cat_slug = sanitize_title( $product['category'] );
        $cat_term = get_term_by( 'slug', $cat_slug, 'product_cat' );

        if ( ! $cat_term ) {
            $result = wp_insert_term( $product['category'], 'product_cat', [ 'slug' => $cat_slug ] );
            if ( is_wp_error( $result ) ) {
                echo "   ⚠️  Category creation failed: " . $result->get_error_message() . "\n";
                $cat_id = 0;
            } else {
                $cat_id = $result['term_id'];
                echo "   📂 Created category: {$product['category']}\n";
            }
        } else {
            $cat_id = $cat_term->term_id;
        }

        // --- Step 2: Create the WooCommerce product ---
        $product_id = wp_insert_post([
            'post_title'   => $product['title'],
            'post_content' => $product['description'],
            'post_excerpt' => $product['short_desc'],
            'post_status'  => 'publish',
            'post_type'    => 'product',
        ]);

        if ( is_wp_error( $product_id ) ) {
            echo "   ❌ Product creation failed: " . $product_id->get_error_message() . "\n";
            $error_count++;
            continue;
        }

        echo "   ✅ Product created (ID: $product_id)\n";

        // --- Step 3: Set WooCommerce product meta ---
        update_post_meta( $product_id, '_regular_price', $product['price'] );
        if ( ! empty( $product['sale_price'] ) ) {
            update_post_meta( $product_id, '_sale_price', $product['sale_price'] );
            update_post_meta( $product_id, '_price', $product['sale_price'] );
            echo "   💰 Price: \${$product['price']} → \${$product['sale_price']} (sale)\n";
        } else {
            update_post_meta( $product_id, '_price', $product['price'] );
            echo "   💰 Price: \${$product['price']}\n";
        }

        update_post_meta( $product_id, '_sku', $product['sku'] );
        update_post_meta( $product_id, '_manage_stock', 'yes' );
        update_post_meta( $product_id, '_stock', rand( 15, 100 ) );
        update_post_meta( $product_id, '_stock_status', 'instock' );
        update_post_meta( $product_id, '_visibility', 'visible' );
        update_post_meta( $product_id, '_nss_imported', '1' );

        // Set product type
        wp_set_object_terms( $product_id, 'simple', 'product_type' );

        // Set category
        if ( $cat_id ) {
            wp_set_object_terms( $product_id, [ $cat_id ], 'product_cat' );
            echo "   📂 Category: {$product['category']}\n";
        }

        // Set tags
        if ( ! empty( $product['tags'] ) ) {
            wp_set_object_terms( $product_id, $product['tags'], 'product_tag' );
            echo "   🏷️  Tags: " . implode( ', ', $product['tags'] ) . "\n";
        }

        // --- Step 4: Upload and attach the image ---
        echo "   📤 Uploading image...\n";

        // Copy JFIF to uploads as JPG (WordPress handles JPG better)
        $upload_dir = wp_upload_dir();
        $filename   = sanitize_file_name( str_replace( '.jfif', '.jpg', $product['file'] ) );
        $dest_path  = $upload_dir['path'] . '/' . $filename;

        if ( ! copy( $image_path, $dest_path ) ) {
            echo "   ❌ Failed to copy image to uploads\n";
            $error_count++;
            continue;
        }

        // Get the MIME type
        $filetype = wp_check_filetype( $filename, null );
        if ( empty( $filetype['type'] ) ) {
            // Force JPEG for JFIF files
            $filetype = [
                'ext'  => 'jpg',
                'type' => 'image/jpeg',
            ];
        }

        // Create attachment post
        $attachment_id = wp_insert_attachment([
            'guid'           => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $dest_path, $product_id );

        if ( is_wp_error( $attachment_id ) ) {
            echo "   ❌ Attachment creation failed: " . $attachment_id->get_error_message() . "\n";
            $error_count++;
            continue;
        }

        // Generate attachment metadata (thumbnails, sizes, etc.)
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $dest_path );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        // Set as product thumbnail (featured image)
        set_post_thumbnail( $product_id, $attachment_id );

        echo "   🖼️  Image attached (Attachment ID: $attachment_id)\n";

        $success_count++;
        echo "   ✅ DONE\n";
    }

    echo "\n═══════════════════════════════════════════════════\n";
    echo "   📊 IMPORT SUMMARY\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "   ✅ Successfully imported: $success_count / " . count( $products ) . "\n";
    if ( $error_count > 0 ) {
        echo "   ❌ Errors: $error_count\n";
    }
    echo "   🛒 Shop URL: " . get_permalink( get_option( 'woocommerce_shop_page_id' ) ) . "\n";
    echo "═══════════════════════════════════════════════════\n";

    // Clear WooCommerce transient caches
    if ( function_exists( 'wc_delete_product_transients' ) ) {
        wc_delete_product_transients();
    }
    delete_transient( 'wc_products_onsale' );

    echo "\n🔄 Cache cleared. Your shop is ready!\n";
}
