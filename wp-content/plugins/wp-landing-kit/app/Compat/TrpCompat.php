<?php

namespace WpLandingKit\Compat;

use WpLandingKit\DomainIntercept\DomainMap;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Framework\Utils\Url;
use WpLandingKit\Http\Server;
use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\Redirect;

/**
 * Class TrpCompat
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with translatepress multilingual.
 */
class TrpCompat {

    /**
     * @var DomainMap
     */
    private $map;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var array
     */
    private $settings = array();

    /**
     * @var array
     */
    private $mappings = array();

    /**
     * @var string
     */
    private $locale = '';

    /**
     * @var string
     */
    private $mapped_language = '';

    public function __construct( DomainMap $map ) {
        $this->map = $map;
    }

    public function init() {
        if ( $this->is_translatepress_running() ) {

            global $TRP_LANGUAGE;

            if ( empty( $TRP_LANGUAGE ) ) {
                return;
            }

            $server       = new Server();
            $current_host = $server->http_host();
            $current_path = trim( $server->request_uri(), '/' );
            $domain_id    = $this->map->get_domain_id( $current_host );

            if ( ! $domain_id ) {   
                return;
            }

            $this->settings = get_option( 'trp_settings' );
            $default_lang   = Arr::get( $this->settings, 'default-language', '' );
            $default_lang   = wplk_get_lang_slug( $default_lang, $this->settings );
            $this->domain   = get_post( $domain_id );
            $this->domain   = Domain::make( $this->domain );
            $path           = $this->remove_locale_from_path( $TRP_LANGUAGE, $current_path );
            $path           = preg_replace('/page\/([0-9]+)/', '', $path );
            $endpoint       = end( explode( '/', $path ) );

            if ( wplk_is_woocommerce_page( $endpoint ) ) {
                $path = trim( substr( $path, 0, strrpos( $path, $endpoint ) ), '/' );
            }

            while( wplk_is_edd_page( $endpoint ) ) {
                $path     = trim( substr( $path, 0, strrpos( $path, $endpoint ) ), '/' );
                $endpoint = end( explode( '/', $path ) );
            }

            foreach ( $this->domain->dynamic_mappings() as $m ) {
                $mapped_locale = Arr::get( $m, 'url_path', '' );
                if ( str_starts_with( trim( $path, '/' ), trim( $mapped_locale, '/' ) ) ) {
                    $this->mappings = $m;
                }
            }

            if ( ! $this->mappings && ! $path ) {
                $this->mappings = $this->domain->root_mapping();
            } elseif ( ! $this->mappings && $path ) {
                $this->mappings = $this->domain->fallback_mapping();
            }

            $this->mapped_language = Arr::get( $this->mappings, 'language', '' );
           
            if ( $current_path !== $path ) {
                $_path     = explode( '/', $current_path );
                $_path     = reset( $_path );
                $lang_slug = wplk_get_lang_slug( $this->mapped_language, $this->settings );

                // if current path language is the same as the mapped language, redirect to the mapped domain.
                if ( $_path === $lang_slug && ( ! empty( $this->settings['add-subdirectory-to-default-language'] ) && 'yes' !== $this->settings['add-subdirectory-to-default-language'] ) ) {
                    $url = $this->domain->protocol() . trim( $this->domain->host(), '/' ) . '/' . trim( $path, '/' );
                    $url = Redirect::to( $url );
                }
            }

            $current_lang_slug = wplk_get_lang_slug( $TRP_LANGUAGE, $this->settings );
            if ( ! str_starts_with( $current_path . '/', $current_lang_slug . '/') ) {
                $this->set_locale( $this->mapped_language );
            } elseif ( str_starts_with( $current_path . '/', $current_lang_slug . '/') ) {
                $this->set_locale( $TRP_LANGUAGE );
            }

            if ( $this->locale ) {
                $TRP_LANGUAGE = $this->locale;
            }

            // Apply any necessary translation filters for the new locale
            add_filter( 'trp_home_url', array( $this, 'trp_change_home_url' ), 10, 5 );
            add_filter( 'trp_pre_get_url_for_language', array( $this, 'trp_mapped_language_url' ), 10, 5 );
            add_filter( 'trp_link_to_redirect_to', array( $this, 'trp_change_redirect_link' ) );
            add_filter( 'the_content', array( $this, 'trp_update_content' ), 9999, 1 );
            add_filter( 'the_title', array( $this, 'trp_update_content' ), 9999, 1 );
        }
    }

    private function set_locale( $locale ) {
        $this->locale = $locale;
    }

    public function is_translatepress_running() {
        return defined( 'TRP_PLUGIN_VERSION' );
    }

    public function trp_change_home_url( $new_url, $abs_home, $lang, $path, $url ) {
        $url = Url::replace_host( $url, $this->domain->host() );
        return $url;
    }

    public function trp_mapped_language_url( $url, $lang, $home_url, $url_from_string, $url_slug ) {
        if ( $this->mappings ) {
            $default_lang   = Arr::get( $this->settings, 'default-language', '' );
            $current_locale = $this->locale;
            $path = trim( parse_url( $url, PHP_URL_PATH ), '/' );

            if ( $current_locale === $lang && $this->mapped_language === $current_locale ) {
                $url = URL::replace_host( $url, $this->domain->host() );
            } elseif ( $current_locale !== $lang && $this->mapped_language === $lang ) {
                $path = $this->remove_locale_from_path( $current_locale, $path );

                if ( ! empty( $this->settings['add-subdirectory-to-default-language'] ) && 'yes' === $this->settings['add-subdirectory-to-default-language'] ) {
                    $url = $this->domain->protocol() . trim( $this->domain->host(), '/' ) . '/'  . trim( $url_slug . '/' . $path, '/' );
                }   else {
                    $url = $this->domain->protocol() . trim( $this->domain->host(), '/' ) . '/' . trim( $path, '/' );
                }
            } else {
                $path      = $this->remove_locale_from_path( $this->mapped_language, $path );
                $url       = Url::remove_path( $url );
                $url       = Url::replace_host( $url, $this->domain->host() );
                $lang_slug = wplk_get_lang_slug( $lang, $this->settings );
                $url       = trim( $url, '/' ) . '/' . $lang_slug . '/' . $path;
            }
        }
        return $url;
    }

    private function remove_locale_from_path( $locale, $path ) {
        if ( empty( $locale ) || empty( $path ) ) {
            return $path;
        }

        $path = trim( $path, '/' );
        $searchable = wplk_get_lang_slug( $locale, $this->settings );

        // Define a regex pattern to match the locale at the start, middle, or end of the path
        $pattern = '/(^' . preg_quote( $searchable, '/' ) . '(\/|$))|(?<=\/)' . preg_quote( $searchable, '/' ) . '(\/|$)/';

        // Replace the first occurrence of the locale segment
        $result = preg_replace( $pattern, '', $path, 1 );
        return $result;
    }

    public function trp_change_redirect_link( $url ) {
        $path         = trim( parse_url( $url, PHP_URL_PATH ), '/' );
        $default_lang = Arr::get( $this->settings, 'default-language', '' );
        $path         = $this->remove_locale_from_path( $default_lang, $path );
        $url          = Url::remove_path( $url );
        $locale_slug  = wplk_get_lang_slug( $this->mapped_language, $this->settings );
        $url          = $url . '/' . $locale_slug . '/' . $path;
        return $url;
    }

    public function trp_update_content( $content ) {
        $content = trp_translate( $content, $this->locale, false );
        $content = preg_replace( '/(<|&lt;)trp-post-container (.*?)(>|&gt;)/i', '', $content );
        $content = preg_replace( '/(<|&lt;)(\\\\)*\/trp-post-container(>|&gt;)/i', '', $content );
        return $content;
    }
}