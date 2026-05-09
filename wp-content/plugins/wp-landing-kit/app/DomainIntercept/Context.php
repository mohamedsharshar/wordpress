<?php

namespace WpLandingKit\DomainIntercept;

use WP;
use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Framework\Utils\Str;
use WpLandingKit\Http\Request;
use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\Redirect;
use WpLandingKit\WordPress\Site;
use WpLandingKit\Framework\Utils\Url;

class Context {

	/**
	 * @var Domain
	 */
	private $domain;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var DomainReplacer
	 */
	private $replacer;

	/**
	 * @var Site
	 */
	private $site;

	/**
	 * @param Request $request
	 * @param DomainReplacer $replacer
	 * @param Site $site
	 */
	public function __construct( Request $request, DomainReplacer $replacer, Site $site ) {
		$this->request = $request;
		$this->replacer = $replacer;
		$this->site = $site;
	}

	/**
	 * Override the context to use a specified mapped domain.
	 *
	 * @param Domain $domain
	 */
	public function override( Domain $domain ) {
		$this->domain = $domain;
		$this->request->listen();
		add_filter( 'pre_option_rewrite_rules', [ $this, '_set_rewrite_rules' ] );
		add_action( 'parse_request', [ $this, '_handle_sub_page_mapping' ], 5 );
		add_action( 'parse_request', [ $this, '_handle_custom_query_variable_mapping' ] );
		add_action( 'parse_request', [ $this, '_handle_woo_query_variable_mapping' ] );
		add_action( 'parse_request', [ $this, '_handle_edd_query_variable_mapping' ] );
		add_action( 'parse_request', [ $this, '_handle_dokan_query_variable_mapping' ] );
		add_filter( 'template_redirect', [ $this, '_handle_matched_redirect_rules' ] );
		add_filter( 'get_site_icon_url', [ $this, '_replace_site_icon' ], 10, 3 );
		add_filter( 'post_link', [ $this, 'wplk_post_link' ] );
		add_filter( 'post_type_link', [ $this, 'wplk_post_link' ] );
		add_filter( 'nav_menu_link_attributes', [ $this, 'wplk_nav_menu_link' ] );
		add_action( 'send_headers', [ $this, '_send_response_headers' ] );
		add_action( apply_filters( 'wplk_gtm_script_hook', 'wp_head' ), [ $this, 'wplk_site_script' ] );
		// Remove the canonical redirect — this ensures we don't load up other random posts/pages that WordPress
		// resolves to when fielding requests on secondary domains.
		remove_filter( 'template_redirect', 'redirect_canonical' );

		// WooCommerce hooks.
		add_filter( 'woocommerce_add_to_cart_form_action', [ $this, '_wc_handle_page_url' ] );

		//Dokan hooks.
		add_filter( 'dokan_get_store_url', [ $this, '_dokan_handle_store_url' ] );

		$this->run_replacer();

		$this->fire_domain_init_hook();
	}

	/**
	 * Set the redirect rules for the given domain.
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function _set_rewrite_rules( $rules ) {
		return $this->domain->rewrite_rules();
	}

	/**
	 * Handle dynamic sub page requests. Nested posts need to be looked up using their hierarchical URL structure,
	 * otherwise we would need to create large, elaborate maps that are updated whenever posts are modified. This method
	 * determines whether a hierarhical post URL has been matched during the request parsing/intercept and uses the
	 * subsequent request data to determine which post object to prime the query for.
	 *
	 * @param WP $wp
	 */
	public function _handle_sub_page_mapping( WP $wp ) {
		if ( ! $this->request->get_wplk_var( 'is_sub_page_mapping' ) ) {
			return;
		}

		// Check we have necessary data to handle this request.
		$root_id = $this->request->get_wplk_var( 'root_post_id' );
		$pagename = $this->request->get_wplk_var( 'pagename' );
		if ( empty( $root_id ) or empty( $pagename ) ) {
			return;
		}

		// Get post type and check it is hierarchical.
		$post_type = get_post_type( $root_id );
		if ( ! is_post_type_hierarchical( $post_type ) ) {
			return;
		}

		// Build the full path to the original post object.
		$path = trailingslashit( $this->get_post_url_path( $root_id ) ) . Str::unleadingslashit( $pagename );

		// Prepare the path for lookup — custom post types need to have their permastruct removed.
		$path = $this->remove_permastruct( $path, $post_type );

		// Get the post object this URL needs to resolve to using the post type and the URL path. If a post object
		// matching the full path cannot be found, enforce the fallback mapping for this domain.
		$post = get_page_by_path( $path, OBJECT, $post_type );
		if ( ! $post ) {
			$this->enforce_fallback_mapping( $wp );

			return;
		}

		// Set the query vars to load the target post.
		$wp->query_vars['post_type'] = $post_type;
		$wp->query_vars['p'] = $post->ID;
		$wp->query_vars['page'] = $this->request->get_wplk_var( 'page' );
	}

	/**
	 * Convert any custom query variables to their WP core equivalent.
	 *
	 * @param WP $wp
	 */
	public function _handle_custom_query_variable_mapping( WP $wp ) {
		// Taxonomies don't have a query var that will load their archive via the term ID. This isn't ideal as slugs can
		// change in the WP admin. So, to work around this, we have our own custom query vars in place that we can then
		// map on to the WordPress core request accordingly.
		if (
			$tax = $this->request->get_wplk_var( 'taxonomy' ) and
			$id = $this->request->get_wplk_var( 'term_id' ) and
			is_string( $slug = get_term_field( 'slug', $id, $tax ) )
		) {
			switch ( $tax ) {
				// Categories have specific handling
				case 'category':
					$wp->query_vars['category_name'] = $slug;
					break;
				// Tags have specific handling
				case 'post_tag':
					$wp->query_vars['tag'] = $slug;
					break;
				// Other taxonomies
				default:
					$wp->query_vars[ $tax ] = $slug;
			}

			return;
		} elseif (
			$this->request->get_wplk_var( 'map_all_posts' ) and
			empty( $this->request->get_wplk_var( 'page' ) ) and
			$post_type = $this->request->get_wplk_var( 'post_type' ) and
			$pagename  = $this->request->get_wplk_var( 'pagename' )
		) {
			$pagename = explode( '/', $pagename );
			if ( false !== ( $key = array_search( $post_type, $pagename, true ) ) ) {
				unset( $pagename[ $key ] );
			}
			$wp->query_vars['post_type'] = $post_type;
			$wp->query_vars['name']      = basename( implode( '/', $pagename ) );
			return;
		}
	}

	/**
	 * If the request matches a rule that has been configured to redirect, handle the redirect.
	 */
	public function _handle_matched_redirect_rules() {
		if ( $this->request->get_wplk_var( 'redirect.to' ) ) {
			Redirect::to(
				$this->request->get_wplk_var( 'redirect.to' ),
				$this->request->get_wplk_var( 'redirect.status', 302 )
			);
		}
	}

	/**
	 * If the current domain has custom site icon defined, replace the site icon URL with the domain-specific image.
	 * This is very similar to core's \get_site_icon_url() function.
	 *
	 * @param string $url
	 * @param int $size
	 * @param int $blog_id
	 *
	 * @return string
	 *
	 * @see \get_site_icon_url()
	 */
	public function _replace_site_icon( $url, $size, $blog_id ) {
		if ( $attachment_id = $this->domain->site_icon() ) {
			$size_data = ( $size >= 512 ) ? 'full' : [ $size, $size ];
			$url = wp_get_attachment_image_url( $attachment_id, $size_data );
		}

		return $url;
	}

	/**
	 * If the plugin is configured to do so, add custom headers to indicate whether a request is being served by the
	 * plugin.
	 */
	public function _send_response_headers() {
		if ( Settings::get( 'add_response_headers', true ) || $this->is_connection_test() ) {
			header( "WP-Landing-Kit-Hit: 1" );
		}
	}

	private function run_replacer() {
		$this->replacer->set_target_hosts( [ $this->site->host() ] );
		$this->replacer->set_new_host( $this->domain->host() );
		$this->replacer->set_protocol( $this->domain->protocol() );
		$this->replacer->run();
	}

	/**
	 * Prepare data array and fire the domain init hook for third parties to run custom logic.
	 */
	private function fire_domain_init_hook() {
		$query_vars = [];
		if ( $string = $this->request->query_string() ) {
			parse_str( $string, $query_vars );
		}

		if ( $path = (string) $this->request->path() ) {
			$path = Str::unleadingslashit( $path );
		}

		$protocol = Str::remove( $this->request->protocol(), '://' );

		$args = [
			'host' => $this->domain->host(),
			'domain_id' => $this->domain->ID,
			'protocol' => $protocol,
			'path' => $path,
			'query' => $query_vars,
		];

		/**
		 * Fire hook for running custom code on a per-domain level. This hook can be used to change site behaviour for
		 * specific domains and/or URLs.
		 */
		do_action( 'wp_landing_kit/domain_init', $args );
	}

	/**
	 * Enforce the fallback mapping. This involves pulling the query string from the rewrite rules array and setting the
	 * context accordingly. If the query string contains a 'wplk' array, just override the value on the server object —
	 * this should be substantial provided we are calling this before any of our custom 'wplk' handler code which
	 * currently all resides in this class.
	 *
	 * @param WP $wp
	 */
	private function enforce_fallback_mapping( WP $wp ) {
		global $wp_rewrite;
		parse_str( parse_url( $wp_rewrite->rules['^.+'], PHP_URL_QUERY ), $args );

		if ( isset( $args['wplk'] ) and is_array( $args['wplk'] ) ) {
			$this->request->set_wplk( $args['wplk'] );
		} else {
			$wp->query_vars = $args;
		}
	}

	/**
	 * Look up the post name for a given post. This is used when running sub page map handling as we need to perform a
	 * lookup of any hierarchical posts by their original permalink even though we potentially have requests coming in
	 * on different URL bases.
	 *
	 * For now, let's continue using get_permalink(). However, we might consider replacing the call here with a direct
	 * SQL call to the posts table if we find that this is overhead we need to avoid.
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	private function get_post_url_path( $post_id ) {
		return (string) parse_url( get_permalink( $post_id ), PHP_URL_PATH );
	}

	/**
	 * Remove any post type permastruct base.
	 *
	 * @param string $path
	 * @param string $post_type
	 *
	 * @return string|string[]|null
	 */
	private function remove_permastruct( $path, $post_type ) {
		global $wp_rewrite;

		if ( ! $permastruct = $wp_rewrite->get_extra_permastruct( $post_type ) ) {
			return $path;
		}

		if ( ! $post_type_obj = get_post_type_object( $post_type ) ) {
			return $path;
		}

		$new_path = preg_replace( "#^/{$post_type_obj->rewrite['slug']}#", '', $path, 1 );
		if ( is_string( $new_path ) ) {
			return $new_path;
		}

		return $path;
	}

	/**
	 * Convert any WooCommerce query variables to their WP core equivalent.
	 *
	 * @param WP $wp
	 */
	public function _handle_woo_query_variable_mapping( Wp $wp ) {
		if ( $this->request->get_wplk_var( 'is_woo_page_mapping' ) ) {
			global $wp_rewrite, $wp_query;
			parse_str( $wp->matched_query, $args );
			if ( isset( $args['wplk'] ) and is_array( $args['wplk'] ) ) {
				$wp->query_vars['page'] = '';
				if ( isset( $args['wplk']['pagename'] ) ) {
					$pagename     = explode( '/', $args['wplk']['pagename'] );
					$woo_endpoint = end( $pagename );
					$woo_page     = reset( $pagename );
					if ( wplk_is_woocommerce_page( $woo_page ) ) {
						$wp->query_vars['pagename'] = $woo_page;
						if ( $woo_page !== $woo_endpoint ) {
							$wp->query_vars[ $woo_endpoint ] = ! empty( $args['wplk']['page'] ) ? $args['wplk']['page'] : '';
						}
					} elseif ( wplk_check_is_cartflows_request() ) {
						$page_id = wplk_get_cartflows_page_id( $woo_endpoint );
						if ( $page_id ) {
							$wp->query_vars['page_id'] = $page_id;
						} else {
							$this->enforce_fallback_mapping( $wp );
							return;
						}
					} else {
						$this->enforce_fallback_mapping( $wp );
						return;
					}
				}
				$this->request->set_wplk( $args['wplk'] );
			} else {
				$wp->query_vars = $args;
			}
		}
	}

	/**
	 * WooCommerce add to cart action.
	 *
	 * @param string $url Action URL.
	 * @return string
	 */
	public function _wc_handle_page_url( $url ) {
		$url = Url::replace_host( $this->request->full_url(), $this->domain->host() );
		$url = Url::set_protocol( $url, $this->domain->protocol() ?: $this->request->protocol() );
		return $url;
	}

	/**
	 * Filters the permalink for a post.
	 *
	 * @param string $permalink The post's permalink.
	 * @return string post link.
	 */
	public function wplk_post_link( $permalink ) {
		$skip_links = $this->domain->skip_links_replacement();
		if ( ! empty( $skip_links ) && 'yes' === $skip_links ) {
			$permalink = str_replace( $this->domain->host(), $this->site->host(), $permalink );
		}
		return $permalink;
	}

	/**
	 * Filters the WP_Nav_Menu link.
	 *
	 * @param array $atts The item link attributes.
	 * @return string link attributes.
	 */
	public function wplk_nav_menu_link( $atts ) {
		$skip_menu_link = $this->domain->skip_links_replacement( 'skip_menu_links_replacement' );
		if ( isset( $atts['href'] ) && ( ! empty( $skip_menu_link ) && 'yes' === $skip_menu_link ) ) {
			$atts['href'] = str_replace( $this->domain->host(), $this->site->host(), $atts['href'] );
		}
		return $atts;
	}

	/**
	 * Convert any EDD query variables to their WP core equivalent.
	 *
	 * @param WP $wp
	 */
	public function _handle_edd_query_variable_mapping( Wp $wp ) {
		if ( $this->request->get_wplk_var( 'is_edd_page_mapping' ) ) {
			parse_str( $wp->matched_query, $args );
			if ( isset( $args['wplk'] ) && is_array( $args['wplk'] ) ) {
				$wp->query_vars['page'] = '';
				if ( isset( $args['wplk']['pagename'] ) ) {
					$pagename     = explode( '/', $args['wplk']['pagename'] );
					$edd_endpoint = end( $pagename );
					if ( wplk_is_edd_page( $edd_endpoint ) ) {
						$wp->query_vars['pagename'] = $args['wplk']['pagename'];
					} else {
						$this->enforce_fallback_mapping( $wp );
						return;
					}
				}
				$this->request->set_wplk( $args['wplk'] );
			} else {
				$wp->query_vars = $args;
			}
		}
	}

	/*
	 * Check if current request is a connection test
	 *
	 * @return bool Whether this is a connection test request
	 */
	public function is_connection_test() {
		// Look for a specific user agent
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		if ( strpos( $user_agent, 'WPLandingKit' ) !== false ) {
			return true;
		}

		return false;
	}
	/**
	 * Hook javascript for google tag manager and other site scripts.
	 */
	public function wplk_site_script() {
		$allowed_tags = $this->domain->allowed_script_tags();
		$script = get_post_meta( $this->domain->ID, '_wplk_gtm_script', true );
		if ( $script ) {
			echo wp_kses( $script, $allowed_tags );
		}
	}

	/**
	 * Convert any dokan query variables to their WP core equivalent.
	 *
	 * @param WP $wp
	 */
	public function _handle_dokan_query_variable_mapping( Wp $wp) {
		if ( $this->request->get_wplk_var( 'is_dokan_page_mapping' ) ) {
			parse_str( $wp->matched_query, $args );
			if ( isset( $args['wplk'] ) && is_array( $args['wplk'] ) ) {
				$wp->query_vars['page'] = '';
				if ( isset( $args['wplk']['pagename'] ) ) {
					$pagename       = explode( '/', $args['wplk']['pagename'] );
					$dokan_endpoint = end( $pagename );
					$dokan_page     = reset( $pagename );
					if ( wplk_is_dokan_vendor_page( $dokan_endpoint ) ) {
						$custom_store_url                    = dokan_get_option( 'custom_store_url', 'dokan_general', 'store' );
						$wp->query_vars[ $custom_store_url ] = $dokan_endpoint;
					} elseif ( wplk_is_dokan_page( $dokan_page ) ) {
						$wp->query_vars['pagename'] = $dokan_page;
						if ( $dokan_page !== $dokan_endpoint ) {
							$wp->query_vars[ $dokan_endpoint ] = ! empty( $args['wplk']['page'] ) ? $args['wplk']['page'] : '';
						}
					} else {
						$this->enforce_fallback_mapping( $wp );
						return;
					}
				}
				$this->request->set_wplk( $args['wplk'] );
			} else {
				$wp->query_vars = $args;
			}
		}
	}

	/**
	 * Update dokan store url.
	 *
	 * @param  string $store_url dokan store url.
	 * @return string
	 */
	public function _dokan_handle_store_url( $store_url ) {
        $store_url = explode( '/', $store_url );
        $store_url = array_filter( $store_url );
        $endpoint  = end( $store_url );

        foreach ( $this->domain->dynamic_mappings() as $m ) {
            $vendor = Arr::get( $m, 'vendor' );
            $url    = Arr::get( $m, 'url_path' );
            if ( $vendor === $endpoint && $endpoint !== $url ) {
                $endpoint = $url;
            }
        }

        if ( $m = $this->domain->root_mapping() ) {
            $vendor = Arr::get( $m, 'vendor' );
            if ( $vendor === $endpoint ) {
                $endpoint = '';
            }
        }
        return $this->domain->protocol() . $this->domain->host() . '/' . $endpoint;
	}
}