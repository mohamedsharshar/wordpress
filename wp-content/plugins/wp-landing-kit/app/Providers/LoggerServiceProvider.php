<?php

namespace WpLandingKit\Providers;

use WpLandingKit\Framework\Providers\ServiceProviderBase;
use WpLandingKit\PostTypes\MappedDomainPostType;

class LoggerServiceProvider extends ServiceProviderBase {

    public function register() {
        // Register container bindings if needed in the future.
    }

    public function boot() {
        add_filter('wp_landing_kit_logger_data', function( $value ) {
            $post_type  = MappedDomainPostType::POST_TYPE;
            $post_count = wp_count_posts( $post_type );

            $value['domains'] = isset( $post_count->publish ) ? $post_count->publish : 0;
            return $value;
        });
    }
}