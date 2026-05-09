<?php

namespace WpLandingKit\WordPress\AdminNotices;

use WpLandingKit\Facades\Settings;
use WpLandingKit\Ajax\DismissConflictNoticeAjaxHandler;
use function WpLandingKit\ajax_handler;

class ConflictNotice {
    const CONFLICT_NOTICE_OPTION = 'wplk_hide_conflict_notice';

    /**
     * Initialize the notice hooks.
     *
     * @return void
     */
    public function init() {
        add_action( 'admin_notices', [ $this, 'show' ] );
        add_action( 'admin_footer', [ $this, 'add_dismiss_script' ] );
    }

    /**
     * Output the admin notice if conditions are met.
     *
     * @return void
     */
    public function show() {
        if ( ! self::should_show_notice() ) {
            return;
        }
        ?>
        <div class="notice notice-warning is-dismissible" id="wplk-conflict-notice">
            <p>
                <strong><?php esc_html_e( 'Potential Configuration Conflict Detected:', 'wp-landing-kit' ); ?></strong>
                <?php 
                printf(
                    /* translators: %s: WP_SITEURL code tag */
                    esc_html__( 'WP Landing Kit might experience limited functionality because your hosting environment has %s constant defined in your configuration.', 'wp-landing-kit' ),
                    '<code>WP_SITEURL</code>'
                ); 
                ?>
            </p>
            <p>
                <?php 
                printf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    esc_html__( 'Some domain mapping features may still work, but for optimal performance, you may need to modify this constant. %1$sSee our compatibility guide%2$s for step-by-step instructions.', 'wp-landing-kit' ),
                    '<a href="https://wplandingkit.notion.site/How-to-Remove-WP_SITEURL-1dd2427f43b380c7a9c3c53e8a88cfe9" target="_blank">',
                    '</a>'
                ); 
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Check if the notice should be shown.
     *
     * @return bool
     */
    public static function should_show_notice() {
        if ( get_option( self::CONFLICT_NOTICE_OPTION, false ) === 'yes' ) {
            return false;
        }

        if ( ! current_user_can( 'create_domains' ) ) {
            return false;
        }

        if ( ! defined( 'WP_SITEURL' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Output the JavaScript for dismissing the onboarding notice.
     *
     * @return void
     */
    public function add_dismiss_script() {
        if ( ! self::should_show_notice() ) {
            return;
        }
        ?>
        <script>
            (function($){
                function dismissConflictNotice(callback) {
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

                $(document).on('click', '#wplk-conflict-notice .notice-dismiss', function() {
                    dismissConflictNotice(function() {
                        var notice = document.getElementById('wplk-conflict-notice');
                        if (notice) notice.style.display = 'none';
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
        $ajax_handler = ajax_handler( DismissConflictNoticeAjaxHandler::class );
        return $ajax_handler ? $ajax_handler->get_script_vars() : [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'action' => 'wplk_dismiss_conflict_notice',
            'nonce' => wp_create_nonce( 'wplk_dismiss_conflict_notice' ),
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
}
