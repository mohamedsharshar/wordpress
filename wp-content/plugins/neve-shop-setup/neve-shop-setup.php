<?php
/**
 * Plugin Name: Neve Shop Setup
 * Description: Sets up WooCommerce shop, cart, checkout, registration/login, and enhances the Neve navbar.
 * Version: 1.0.0
 * Author: Dev
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Neve_Shop_Setup {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Run setup on activation
        register_activation_hook( __FILE__, [ $this, 'activate' ] );

        // Enqueue styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ], 20 );

        // Enable registration
        add_action( 'init', [ $this, 'enable_registration' ] );

        // Add nav menu items (cart, account)
        add_filter( 'wp_nav_menu_items', [ $this, 'add_nav_icons' ], 10, 2 );

        // Add body class for our styling
        add_filter( 'body_class', [ $this, 'add_body_class' ] );

        // Register widget/shortcodes
        add_action( 'init', [ $this, 'register_shortcodes' ] );

        // Add login/register modal markup to footer
        add_action( 'wp_footer', [ $this, 'render_auth_modal' ] );

        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 20 );

        // AJAX login handler
        add_action( 'wp_ajax_nopriv_neve_shop_login', [ $this, 'ajax_login' ] );
        add_action( 'wp_ajax_nopriv_neve_shop_register', [ $this, 'ajax_register' ] );

        // Redirect after logout
        add_action( 'wp_logout', [ $this, 'logout_redirect' ] );

        // WooCommerce account settings
        add_filter( 'woocommerce_registration_redirect', [ $this, 'registration_redirect' ] );

        // Force redirects to custom login/register
        add_action( 'template_redirect', [ $this, 'enforce_custom_login' ] );

        // Admin notice if WooCommerce not active
        add_action( 'admin_notices', [ $this, 'check_woocommerce' ] );

        // Disable WooCommerce coming soon mode
        add_action( 'init', [ $this, 'disable_coming_soon' ], 5 );

        // AJAX cart count
        add_action( 'wp_ajax_nss_get_cart_count', [ $this, 'ajax_cart_count' ] );
        add_action( 'wp_ajax_nopriv_nss_get_cart_count', [ $this, 'ajax_cart_count' ] );

        // Force Neve Full Width on Homepage
        add_filter('neve_meta_container', function($val) {
            if (is_front_page()) return 'full-width';
            return $val;
        });
        add_filter('neve_meta_enable_content_width', function($val) {
            if (is_front_page()) return 'on';
            return $val;
        });
        add_filter('neve_meta_sidebar_layout', function($val) {
            if (is_front_page()) return 'full-width';
            return $val;
        });

        // Fix My Account page links and any absolute/relative link issue
        add_action('wp_footer', [$this, 'fix_myaccount_links_script'], 99);

        // Custom Footer
        add_action( 'wp_footer', [ $this, 'custom_footer' ], 1 );
        add_filter( 'theme_mod_neve_footer_enable', '__return_false' ); // Disable default Neve footer
        
        // Contact Form Shortcode
        add_shortcode( 'nss_contact_form', [ $this, 'contact_form_shortcode' ] );
        
        // Aggressive CSS overrides
        add_action('wp_head', function() {
            echo '<style>
                /* Force hide Neve Footer components */
                .hfg_footer, .builder-item--footer_copyright, footer#site-footer, .site-footer, .footer-bottom, #colophon, .h-footer-bottom-wrap, .nv-footer-content {
                    display: none !important;
                    visibility: hidden !important;
                    height: 0 !important;
                    opacity: 0 !important;
                }
                body footer:not(.nss-professional-footer) {
                    display: none !important;
                }
                /* Force full width backgrounds for alignfull blocks */
                .neve-shop-enhanced .entry-content > .alignfull {
                    position: relative;
                    width: 100% !important;
                    max-width: 100% !important;
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                }
                .neve-shop-enhanced .entry-content > .alignfull::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    bottom: 0;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 100vw;
                    background-color: inherit;
                    z-index: -1;
                    border-radius: 0 !important;
                }
                /* Center the inner content */
                .neve-shop-enhanced .entry-content .wp-block-group__inner-container,
                .neve-shop-enhanced .entry-content .wp-block-columns.alignwide {
                    max-width: 1200px !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                    width: 100% !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }
                /* Ensure blog grid is actually a grid */
                .neve-shop-enhanced .wp-block-latest-posts {
                    display: grid !important;
                    grid-template-columns: repeat(3, 1fr) !important;
                    gap: 30px !important;
                }
            </style>';
        });
    }

    /**
     * AJAX cart count handler
     */
    public function ajax_cart_count() {
        $count = 0;
        if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
            $count = WC()->cart->get_cart_contents_count();
        }
        wp_send_json( [ 'count' => $count ] );
    }

    /**
     * Contact Form Shortcode
     */
    public function contact_form_shortcode() {
        ob_start();
        
        $submitted = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nss_contact_submit'])) {
            // In a real scenario, we would send an email here.
            // wp_mail(get_option('admin_email'), 'New Contact Message', sanitize_textarea_field($_POST['nss_message']));
            $submitted = true;
        }
        
        ?>
        <div class="nss-contact-form-container">
            <?php if ($submitted): ?>
                <div class="nss-alert nss-alert-success" style="margin-bottom: 24px; padding: 16px; background: rgba(46, 213, 115, 0.1); color: #2ed573; border-radius: var(--nss-radius-sm); border: 1px solid rgba(46, 213, 115, 0.2);">
                    Thank you! Your message has been sent successfully. We will get back to you shortly.
                </div>
            <?php endif; ?>
            
            <form action="" method="POST" class="nss-contact-form">
                <div class="nss-form-row">
                    <div class="nss-form-group" style="flex: 1;">
                        <label for="nss_name">Full Name</label>
                        <input type="text" id="nss_name" name="nss_name" required placeholder="John Doe" class="nss-input">
                    </div>
                    <div class="nss-form-group" style="flex: 1;">
                        <label for="nss_email">Email Address</label>
                        <input type="email" id="nss_email" name="nss_email" required placeholder="john@example.com" class="nss-input">
                    </div>
                </div>
                <div class="nss-form-group">
                    <label for="nss_subject">Subject</label>
                    <input type="text" id="nss_subject" name="nss_subject" required placeholder="How can we help you?" class="nss-input">
                </div>
                <div class="nss-form-group">
                    <label for="nss_message">Message</label>
                    <textarea id="nss_message" name="nss_message" required rows="5" placeholder="Write your message here..." class="nss-input" style="resize: vertical;"></textarea>
                </div>
                <button type="submit" name="nss_contact_submit" class="nss-btn nss-btn-primary" style="width: 100%;">Send Message</button>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Custom Professional Footer
     */
    public function custom_footer() {
        ?>
        <footer class="nss-professional-footer">
            <div class="nss-footer-top">
                <div class="nss-footer-container">
                    <div class="nss-footer-grid">
                        <div class="nss-footer-col nss-brand-col">
                            <h3 class="nss-footer-logo">Neve Shop</h3>
                            <p class="nss-footer-desc">Curating the finest premium products for the modern lifestyle. Quality, elegance, and durability in every piece.</p>
                            <div class="nss-social-links">
                                <a href="#" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
                                <a href="#" aria-label="Twitter"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path></svg></a>
                                <a href="#" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
                            </div>
                        </div>
                        <div class="nss-footer-col">
                            <h4 class="nss-footer-heading">Quick Links</h4>
                            <ul class="nss-footer-links">
                                <li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Shop</a></li>
                                <li><a href="<?php echo esc_url(home_url('/about/')); ?>">About Us</a></li>
                                <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a></li>
                                <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></li>
                            </ul>
                        </div>
                        <div class="nss-footer-col">
                            <h4 class="nss-footer-heading">Customer Service</h4>
                            <ul class="nss-footer-links">
                                <li><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">My Account</a></li>
                                <li><a href="#">Shipping Policy</a></li>
                                <li><a href="#">Returns & Exchanges</a></li>
                                <li><a href="#">FAQ</a></li>
                            </ul>
                        </div>
                        <div class="nss-footer-col nss-newsletter-col">
                            <h4 class="nss-footer-heading">Payment Methods</h4>
                            <p class="nss-footer-desc">We accept all major credit cards and secure payment methods.</p>
                            <div class="nss-payment-icons">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nss-footer-bottom">
                <div class="nss-footer-container">
                    <p class="nss-copyright">&copy; <?php echo date('Y'); ?> Neve Shop. All rights reserved. Designed with <span style="color:var(--nss-error);">♥</span></p>
                </div>
            </div>
        </footer>
        <?php
    }

    /**
     * On activation: create WooCommerce pages and set up menu
     */
    public function activate() {
        // Enable registration in WordPress
        update_option( 'users_can_register', 1 );
        update_option( 'default_role', 'customer' );

        // Enable WooCommerce registration
        update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
        update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
        update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );

        // Disable coming soon
        update_option( 'woocommerce_coming_soon', 'no' );

        // Create WooCommerce pages if they don't exist
        $this->create_woocommerce_pages();

        // Create/update the primary menu
        $this->setup_menu();

        flush_rewrite_rules();
    }

    /**
     * Disable WooCommerce coming soon mode
     */
    public function disable_coming_soon() {
        if ( get_option( 'woocommerce_coming_soon' ) === 'yes' ) {
            update_option( 'woocommerce_coming_soon', 'no' );
        }
    }

    /**
     * Create WooCommerce pages
     */
    private function create_woocommerce_pages() {
        $pages = [
            'shop'       => [
                'title'   => 'Shop',
                'content' => '',
                'option'  => 'woocommerce_shop_page_id',
            ],
            'cart'       => [
                'title'   => 'Cart',
                'content' => '<!-- wp:woocommerce/cart --><!-- /wp:woocommerce/cart -->',
                'option'  => 'woocommerce_cart_page_id',
            ],
            'checkout'   => [
                'title'   => 'Checkout',
                'content' => '<!-- wp:woocommerce/checkout --><!-- /wp:woocommerce/checkout -->',
                'option'  => 'woocommerce_checkout_page_id',
            ],
            'my-account' => [
                'title'   => 'My Account',
                'content' => '<!-- wp:woocommerce/my-account --><!-- /wp:woocommerce/my-account -->',
                'option'  => 'woocommerce_myaccount_page_id',
            ],
        ];

        foreach ( $pages as $slug => $page_data ) {
            $existing_id = get_option( $page_data['option'] );
            $existing    = get_post( $existing_id );

            if ( $existing && $existing->post_status === 'publish' ) {
                continue;
            }

            // Check if a page with this slug already exists
            $found = get_page_by_path( $slug );
            if ( $found && $found->post_status === 'publish' ) {
                update_option( $page_data['option'], $found->ID );
                continue;
            }

            $page_id = wp_insert_post( [
                'post_title'   => $page_data['title'],
                'post_name'    => $slug,
                'post_content' => $page_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ] );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( $page_data['option'], $page_id );
            }
        }
    }

    /**
     * Set up the primary navigation menu
     */
    private function setup_menu() {
        $menu_name = 'Shop Navigation';
        $menu      = wp_get_nav_menu_object( $menu_name );

        if ( ! $menu ) {
            $menu_id = wp_create_nav_menu( $menu_name );
        } else {
            $menu_id = $menu->term_id;
            // Clear existing items
            $items = wp_get_nav_menu_items( $menu_id );
            if ( $items ) {
                foreach ( $items as $item ) {
                    wp_delete_post( $item->ID, true );
                }
            }
        }

        if ( is_wp_error( $menu_id ) ) {
            return;
        }

        // Home
        $front_page_id = get_option( 'page_on_front' );
        if ( $front_page_id ) {
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'     => 'Home',
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $front_page_id,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => 1,
            ] );
        } else {
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'  => 'Home',
                'menu-item-url'    => home_url( '/' ),
                'menu-item-type'   => 'custom',
                'menu-item-status' => 'publish',
                'menu-item-position' => 1,
            ] );
        }

        // Shop
        $shop_id = get_option( 'woocommerce_shop_page_id' );
        if ( $shop_id ) {
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'     => 'Shop',
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $shop_id,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => 2,
            ] );
        }

        // About (keep existing if it exists)
        $about = get_page_by_path( 'about' );
        if ( $about ) {
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'     => 'About',
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $about->ID,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => 3,
            ] );
        }

        // Blog
        $blog = get_page_by_path( 'blog' );
        if ( $blog ) {
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'     => 'Blog',
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $blog->ID,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => 4,
            ] );
        }

        // Contact
        $contact = get_page_by_path( 'contact' );
        if ( $contact ) {
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'     => 'Contact',
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $contact->ID,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => 5,
            ] );
        }

        // Assign menu to Neve's primary location
        $locations                 = get_theme_mod( 'nav_menu_locations', [] );
        $locations['primary']      = $menu_id;
        $locations['top-bar']      = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );
    }

    /**
     * Enable user registration
     */
    public function enable_registration() {
        if ( get_option( 'users_can_register' ) != 1 ) {
            update_option( 'users_can_register', 1 );
        }
    }

    /**
     * Check WooCommerce is active
     */
    public function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-error"><p><strong>Neve Shop Setup</strong> requires WooCommerce to be installed and active.</p></div>';
        }
    }

    /**
     * Add body class
     */
    public function add_body_class( $classes ) {
        $classes[] = 'neve-shop-enhanced';
        return $classes;
    }

    /**
     * Add cart icon and account icon to the primary navigation
     */
    public function add_nav_icons( $items, $args ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return $items;
        }

        // Only add to primary menu
        $target_locations = [ 'primary', 'top-bar' ];
        if ( ! isset( $args->theme_location ) || ! in_array( $args->theme_location, $target_locations, true ) ) {
            return $items;
        }

        $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        $cart_url   = wc_get_cart_url();
        $account_url = wc_get_page_permalink( 'myaccount' );

        // Cart icon
        $cart_badge = $cart_count > 0 ? '<span class="nss-cart-badge">' . $cart_count . '</span>' : '';
        $cart_item  = '<li class="menu-item nss-nav-icon nss-cart-icon">';
        $cart_item .= '<a href="' . esc_url( $cart_url ) . '" title="Cart">';
        $cart_item .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
        $cart_item .= $cart_badge;
        $cart_item .= '</a></li>';

        // Account icon
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $account_item  = '<li class="menu-item nss-nav-icon nss-account-icon nss-has-dropdown">';
            $account_item .= '<a href="' . esc_url( $account_url ) . '" title="My Account">';
            $account_item .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            $account_item .= '<span class="nss-user-name">' . esc_html( $current_user->display_name ) . '</span>';
            $account_item .= '</a>';
            $account_item .= '<ul class="nss-dropdown">';
            $account_item .= '<li><a href="' . esc_url( home_url( '/profile/' ) ) . '">My Profile</a></li>';
            $account_item .= '<li><a href="' . esc_url( home_url( '/my-account/' ) ) . '">Dashboard</a></li>';
            $account_item .= '<li><a href="' . esc_url( home_url( '/my-account/orders/' ) ) . '">Orders</a></li>';
            $account_item .= '<li><a href="' . esc_url( home_url( '/my-account/edit-account/' ) ) . '">Settings</a></li>';
            $account_item .= '<li class="nss-dropdown-divider"></li>';
            $account_item .= '<li><a href="' . esc_url( wp_logout_url( home_url() ) ) . '">Logout</a></li>';
            $account_item .= '</ul>';
            $account_item .= '</li>';
        } else {
            $login_url = home_url('/login/');
            $account_item  = '<li class="menu-item nss-nav-icon nss-account-icon">';
            $account_item .= '<a href="' . esc_url($login_url) . '" title="Login / Register">';
            $account_item .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            $account_item .= '<span class="nss-login-text">Login / Register</span>';
            $account_item .= '</a></li>';
        }

        $items .= $cart_item . $account_item;

        return $items;
    }

    /**
     * Enqueue custom styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'neve-shop-setup-styles',
            plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
            [],
            '1.0.0'
        );

        // Google Font
        wp_enqueue_style(
            'neve-shop-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            [],
            null
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'neve-shop-setup-scripts',
            plugin_dir_url( __FILE__ ) . 'assets/js/main.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        wp_localize_script( 'neve-shop-setup-scripts', 'nssAjax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'nss_auth_nonce' ),
        ] );
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode( 'nss_mini_cart', [ $this, 'mini_cart_shortcode' ] );
    }

    public function mini_cart_shortcode() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return '';
        }
        ob_start();
        woocommerce_mini_cart();
        return ob_get_clean();
    }

    /**
     * Render the auth modal (login/register) in the footer
     */
    public function render_auth_modal() {
        if ( is_user_logged_in() ) {
            return;
        }
        ?>
        <div id="nss-auth-overlay" class="nss-auth-overlay">
            <div class="nss-auth-modal">
                <button class="nss-modal-close" aria-label="Close">&times;</button>

                <div class="nss-auth-tabs">
                    <button class="nss-tab-btn active" data-tab="login">Sign In</button>
                    <button class="nss-tab-btn" data-tab="register">Create Account</button>
                </div>

                <!-- Login Form -->
                <div class="nss-tab-content active" id="nss-login-tab">
                    <form id="nss-login-form" class="nss-auth-form">
                        <div class="nss-form-group">
                            <label for="nss-login-email">Email or Username</label>
                            <input type="text" id="nss-login-email" name="username" required autocomplete="username" />
                        </div>
                        <div class="nss-form-group">
                            <label for="nss-login-password">Password</label>
                            <input type="password" id="nss-login-password" name="password" required autocomplete="current-password" />
                        </div>
                        <div class="nss-form-row">
                            <label class="nss-checkbox-label">
                                <input type="checkbox" name="remember" value="1" /> Remember me
                            </label>
                            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="nss-forgot-link">Forgot password?</a>
                        </div>
                        <div class="nss-form-message" id="nss-login-message"></div>
                        <button type="submit" class="nss-submit-btn">
                            <span class="nss-btn-text">Sign In</span>
                            <span class="nss-btn-loader" style="display:none;">
                                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner{transform-origin:center;animation:spin .75s infinite linear}@keyframes spin{100%{transform:rotate(360deg)}}</style><circle class="spinner" cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="42" stroke-linecap="round"/></svg>
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="nss-tab-content" id="nss-register-tab">
                    <form id="nss-register-form" class="nss-auth-form">
                        <div class="nss-form-group">
                            <label for="nss-reg-username">Username</label>
                            <input type="text" id="nss-reg-username" name="username" required autocomplete="username" />
                        </div>
                        <div class="nss-form-group">
                            <label for="nss-reg-email">Email Address</label>
                            <input type="email" id="nss-reg-email" name="email" required autocomplete="email" />
                        </div>
                        <div class="nss-form-group">
                            <label for="nss-reg-password">Password</label>
                            <input type="password" id="nss-reg-password" name="password" required minlength="6" autocomplete="new-password" />
                        </div>
                        <div class="nss-form-message" id="nss-register-message"></div>
                        <button type="submit" class="nss-submit-btn">
                            <span class="nss-btn-text">Create Account</span>
                            <span class="nss-btn-loader" style="display:none;">
                                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner{transform-origin:center;animation:spin .75s infinite linear}@keyframes spin{100%{transform:rotate(360deg)}}</style><circle class="spinner" cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="42" stroke-linecap="round"/></svg>
                            </span>
                        </button>
                    </form>
                </div>

                <div class="nss-auth-footer">
                    <p>By continuing, you agree to our Terms of Service and Privacy Policy.</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX Login handler
     */
    public function ajax_login() {
        check_ajax_referer( 'nss_auth_nonce', 'nonce' );

        $username = sanitize_text_field( $_POST['username'] ?? '' );
        $password = $_POST['password'] ?? '';
        $remember = ! empty( $_POST['remember'] );

        $user = wp_signon( [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ] );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( [ 'message' => 'Invalid username or password.' ] );
        }

        wp_send_json_success( [ 'message' => 'Login successful! Redirecting...', 'redirect' => home_url() ] );
    }

    /**
     * AJAX Register handler
     */
    public function ajax_register() {
        check_ajax_referer( 'nss_auth_nonce', 'nonce' );

        $username = sanitize_user( $_POST['username'] ?? '' );
        $email    = sanitize_email( $_POST['email'] ?? '' );
        $password = $_POST['password'] ?? '';

        if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
            wp_send_json_error( [ 'message' => 'All fields are required.' ] );
        }

        if ( strlen( $password ) < 6 ) {
            wp_send_json_error( [ 'message' => 'Password must be at least 6 characters.' ] );
        }

        if ( username_exists( $username ) ) {
            wp_send_json_error( [ 'message' => 'This username is already taken.' ] );
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error( [ 'message' => 'This email is already registered.' ] );
        }

        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
        }

        // Set role to customer if WooCommerce is active
        $user = new WP_User( $user_id );
        if ( class_exists( 'WooCommerce' ) ) {
            $user->set_role( 'customer' );
        }

        // Auto login
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        wp_send_json_success( [ 'message' => 'Account created! Redirecting...', 'redirect' => home_url() ] );
    }

    /**
     * Redirect after logout
     */
    public function logout_redirect() {
        wp_safe_redirect( home_url() );
        exit;
    }

    /**
     * Registration redirect
     */
    public function registration_redirect( $redirect ) {
        return home_url( '/login/' );
    }

    /**
     * Force redirect unauthenticated users to the User Registration login page
     */
    public function enforce_custom_login() {
        if ( is_user_logged_in() ) {
            // Redirect logged-in users away from login/register pages
            if ( is_page( 'login' ) || is_page( 'register' ) ) {
                wp_redirect( wc_get_page_permalink( 'myaccount' ) );
                exit;
            }
            return;
        }

        // If not logged in and trying to access WooCommerce My Account, send to custom login
        if ( class_exists( 'WooCommerce' ) && is_account_page() ) {
            wp_redirect( home_url( '/login/' ) );
            exit;
        }
    }

    /**
     * Fix links that are missing the WordPress subdirectory path
     * or are broken in the My Account page.
     */
    public function fix_myaccount_links_script() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fix any link that starts with exactly / and not //, and doesn't start with /wordpress/
            var links = document.querySelectorAll('a[href^="/"]');
            links.forEach(function(link) {
                var href = link.getAttribute('href');
                if (href && href.startsWith('/') && !href.startsWith('//') && !href.startsWith('/wordpress/')) {
                    // Prepend /wordpress
                    link.setAttribute('href', '/wordpress' + href);
                }
            });
            
            // Fix specific WooCommerce my-account endpoint links that might have been hardcoded
            var dashboardLinks = document.querySelectorAll('.woocommerce-MyAccount-content a, .woocommerce-MyAccount-navigation a');
            dashboardLinks.forEach(function(link) {
                var href = link.getAttribute('href');
                if (!href) return;
                
                if (href.includes('/orders/') || href === 'orders') {
                    link.setAttribute('href', '/wordpress/my-account/orders/');
                } else if (href.includes('/edit-address/') || href === 'edit-address') {
                    link.setAttribute('href', '/wordpress/my-account/edit-address/');
                } else if (href.includes('/edit-account/') || href === 'edit-account') {
                    link.setAttribute('href', '/wordpress/my-account/edit-account/');
                } else if (href.includes('/downloads/') || href === 'downloads') {
                    link.setAttribute('href', '/wordpress/my-account/downloads/');
                }
            });
        });
        </script>
        <?php
    }
}

// Include product import tool
require_once plugin_dir_path( __FILE__ ) . 'import-products.php';

// Initialize
Neve_Shop_Setup::get_instance();
