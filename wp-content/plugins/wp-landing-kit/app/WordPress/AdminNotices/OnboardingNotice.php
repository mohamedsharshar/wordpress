<?php

namespace WpLandingKit\WordPress\AdminNotices;

use WpLandingKit\Facades\Settings;
use WpLandingKit\Ajax\DismissOnboardingNoticeAjaxHandler;
use WpLandingKit\PostTypes\MappedDomainPostType;
use function WpLandingKit\ajax_handler;

class OnboardingNotice {
    const ONBOARDING_NOTICE_OPTION = 'wplk_show_onboarding_notice';

    /**
     * Initialize the onboarding notice hooks.
     *
     * @return void
     */
    public function init() {
        add_action( 'admin_notices', [ $this, 'show' ] );
        add_action( 'admin_footer', [ $this, 'add_dismiss_script' ] );
    }

    /**
     * Determine if the onboarding notice should be shown.
     *
     * @return bool
     */
    protected static function should_show_onboarding_notice() {
        if ( ! Settings::get( 'license_is_active' ) ) {
            return false;
        }
        if ( ! current_user_can( 'create_domains' ) ) {
            return false;
        }
        if ( get_option( self::ONBOARDING_NOTICE_OPTION, false ) !== 'yes' ) {
            return false;
        }
        if ( self::has_mapped_domains() ) {
            return false;
        }
        return true;
    }

    /**
     * Determine if the onboarding notice option should be set.
     *
     * @return bool
     */
    protected static function should_set_onboarding_option() {
        if ( get_option( self::ONBOARDING_NOTICE_OPTION, false ) !== false ) {
            return false;
        }
        if ( self::has_mapped_domains() ) {
            return false;
        }
        return true;
    }

    /**
     * Check if there are any published mapped domain posts.
     *
     * @return bool
     */
    protected static function has_mapped_domains() {
        $post_type  = MappedDomainPostType::POST_TYPE;
        $post_count = wp_count_posts( $post_type );
        $domains    = isset( $post_count->publish ) ? $post_count->publish : 0;
        return (int) $domains > 0;
    }

    /**
     * Output the onboarding admin notice if conditions are met.
     *
     * @return void
     */
    public function show() {
        if ( ! self::should_show_onboarding_notice() ) {
            return;
        }
        ?>
        <div class="notice notice-info is-dismissible" id="wplk-onboarding-notice">
            <h2 style="margin-bottom: 0.5em;"><strong>🎉 <?php esc_html_e( 'Thank you for installing WP Landing Kit!', 'wp-landing-kit' ); ?></strong></h2>
            <p><?php esc_html_e( 'Easily map your posts, pages, and custom post types to any domain with just a few clicks. Get started now!', 'wp-landing-kit' ); ?></p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mapped-domain' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Get Started', 'wp-landing-kit' ); ?></a>
                <a href="https://docs.themeisle.com/article/1810-wp-landingkit" class="button button-secondary" target="_blank"><?php esc_html_e( 'Learn More', 'wp-landing-kit' ); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Output the JavaScript for dismissing the onboarding notice.
     *
     * @return void
     */
    public function add_dismiss_script() {
        if ( get_option( self::ONBOARDING_NOTICE_OPTION, false ) !== 'yes' ) {
            return;
        }
        ?>
        <script>
            (function($){
                function dismissOnboardingNotice(callback) {
                    fetch('<?php echo esc_js( $this->get_ajax_url() ); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: '<?php echo esc_js( $this->get_ajax_action() ); ?>',
                            _wpnonce: '<?php echo esc_js( $this->get_ajax_nonce() ); ?>'
                        })
                    })
                    .then(response => {
                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    });
                }

                $(document).on('click', '#wplk-onboarding-notice .notice-dismiss', function() {
                    dismissOnboardingNotice(function() {
                    var notice = document.getElementById('wplk-onboarding-notice');
                    if (notice) notice.style.display = 'none';
                    });
                });

                $(document).on('click', '#wplk-onboarding-notice .button-primary', function(e) {
                    e.preventDefault();
                    var url = $(this).attr('href');
                    dismissOnboardingNotice(function() {
                    window.location.href = url;
                    });
                });
            })(jQuery);
        </script>
        <?php
    }

    /**
     * Get AJAX variables for the dismiss handler.
     *
     * @return array
     */
    protected function get_ajax_vars() {
        $ajax_handler = ajax_handler( DismissOnboardingNoticeAjaxHandler::class );
        return $ajax_handler ? $ajax_handler->get_script_vars() : [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'action' => 'wplk_dismiss_onboarding_notice',
            'nonce' => wp_create_nonce( 'wplk_dismiss_onboarding_notice' ),
        ];
    }

    /**
     * Get the AJAX URL for the dismiss handler.
     *
     * @return string
     */
    protected function get_ajax_url() {
        $vars = $this->get_ajax_vars();
        return $vars['ajax_url'];
    }

    /**
     * Get the AJAX action for the dismiss handler.
     *
     * @return string
     */
    protected function get_ajax_action() {
        $vars = $this->get_ajax_vars();
        return $vars['action'];
    }

    /**
     * Get the AJAX nonce for the dismiss handler.
     *
     * @return string
     */
    protected function get_ajax_nonce() {
        $vars = $this->get_ajax_vars();
        return $vars['nonce'];
    }

    /**
     * Set the onboarding notice option if appropriate.
     *
     * @return void
     */
    public static function maybe_set_onboarding_option() {
        if ( self::should_set_onboarding_option() ) {
            update_option( self::ONBOARDING_NOTICE_OPTION, 'yes' );
        }
    }
}
