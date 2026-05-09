<?php

namespace WpLandingKit\Utils;

use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Framework\Utils\Str;
use WpLandingKit\Models\Domain;
use WpLandingKit\Http\Request;
use WpLandingKit\Http\Server;

/**
 * Class RewriteRulesGenerator
 * @package WpLandingKit\Utils
 *
 * Generates the rewrite rules for a given Domain based on the Domain object's settings.
 */
class RewriteRulesGenerator {

	const ROOT_MATCH = '$';
	const FALLBACK_MATCH = '^.+';

	// This does not currently take into consideration modifications to the pagination permalink structure.
	const PAGINATION_PART_MATCH = 'page/?([0-9]{1,})';

	/** @var Domain */
	private $domain;
	private $rules = [];

	/**
	 * @param Domain $domain
	 */
	public function __construct( Domain $domain ) {
		$this->domain = $domain;
	}

	public static function make( Domain $domain ) {
		$instance = new static( $domain );
		$instance->generate();

		return $instance->to_array();
	}

	public function to_array() {
		return $this->rules;
	}

	public function generate() {
		if ( empty( $this->domain->config( 'mappings', null ) ) ) {
			$this->rules = [];

			return;
		}

		$this->handle_dynamic_mappings();
		$this->handle_language_mapping();
		$this->handle_root_mapping();
		$this->handle_fallback_mapping();
	}

	private function handle_root_mapping() {
		if ( $m = $this->domain->root_mapping() ) {
			$query = $this->build_query( $m );
			$this->append_rule( self::ROOT_MATCH, $query );

			$server      = new Server();
			$request_uri = $server->request_uri();
			$request_uri = trim( $request_uri, '/' );
			$request_uri = explode( '/', $request_uri );
			$endpoint    = end( $request_uri );
			$is_edd_page = wplk_is_edd_page( $endpoint );
			$is_woo_page = wplk_is_woocommerce_page( $endpoint );
			$is_dokan_page = wplk_is_dokan_page( $endpoint ) || wplk_is_dokan_vendor_page( $endpoint );

			if ( $is_woo_page && $this->mapping_supports_woo_pages( $m ) ) {
				$this->add_woo_page_rule( $m, '' );
			}

			if ( $is_edd_page && $this->mapping_supports_edd_pages( $m ) ) {
				$this->add_edd_page_rule( $m, '' );
			}

			if ( $is_dokan_page && $this->mapping_supports_dokan_pages( $m ) ) {
				$this->add_dokan_page_rule( $m, '' );
			}

			if ( $this->mapping_supports_sub_pages( $m ) ) {
				$this->add_sub_page_rule( $m, '' );
			}

			if ( $this->mapping_supports_pagination( $m ) ) {
				$path = $this->append_language_rule( self::PAGINATION_PART_MATCH, $m );
				$this->append_rule( '^' . $path . '/?$', add_query_arg( 'paged', '$matches[1]', $query ) );
			}

			if ( ! $is_woo_page && ! $is_edd_page && ! $is_dokan_page && $this->mapping_supports_auto_map_posts( $m ) ) {
				$this->add_auto_map_posts_rule( $m, '' );
			}
		}
	}

	private function handle_dynamic_mappings() {
		foreach ( $this->domain->dynamic_mappings() as $m ) {
			$query = $this->build_query( $m );

			if ( $this->mapping_supports_woo_pages( $m ) ) {
				$this->add_woo_page_rule( $m, Arr::get( $m, 'url_path' ) );
				$this->append_rule( $this->build_match( $m ), $query );
				continue;
			}

			if ( $this->mapping_supports_edd_pages( $m ) ) {
				$this->add_edd_page_rule( $m, Arr::get( $m, 'url_path' ) );
				$this->append_rule( $this->build_match( $m ), $query );
				continue;
			}

			if ( $this->mapping_supports_dokan_pages( $m ) ) {
				$this->add_dokan_page_rule( $m, Arr::get( $m, 'url_path' ) );
				$this->append_rule( $this->build_match( $m ), $query );
				continue;
			}

			if ( $this->mapping_supports_sub_pages( $m ) ) {
				$this->add_sub_page_rule( $m, Arr::get( $m, 'url_path' ) );
				$this->append_rule( $this->build_match( $m ), $query );
				continue;
			}

			if ( $this->mapping_supports_auto_map_posts( $m ) ) {
				if ( $this->mapping_supports_pagination( $m ) ) {
					$this->append_rule( $this->build_paginated_match( $m ), add_query_arg( 'paged', '$matches[1]', $query ) );
					$this->append_rule( $this->build_match( $m ), $query );
				}
				$this->add_auto_map_posts_rule( $m, Arr::get( $m, 'url_path' ) );
				$this->append_rule( $this->build_match( $m ), $query );
				continue;
			} elseif ( $this->mapping_supports_pagination( $m ) ) {
				$this->append_rule( $this->build_paginated_match( $m ), add_query_arg( 'paged', '$matches[1]', $query ) );
				$this->append_rule( $this->build_match( $m ), $query );
				continue;
			}

			if ( $this->mapping_supports_page_breaks( $m ) ) {
				$query = add_query_arg( 'page', '$matches[1]', $query );
				$this->append_rule( $this->build_match( $m, true ), $query );
				continue;
			}

			$this->append_rule( $this->build_match( $m ), $query );
		}
	}

	private function mapping_supports_page_breaks( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		return in_array( Arr::get( $mapping, 'resource_type' ), [
			'single-post',
			'single-page'
		] );
	}

	private function handle_fallback_mapping() {
		if ( $m = $this->domain->fallback_mapping() ) {
			$query = $this->build_query( $m );

			if ( $this->mapping_supports_woo_pages( $m ) ) {
				$this->add_woo_page_rule( $m, '' );
			}

			if ( $this->mapping_supports_edd_pages( $m ) ) {
				$this->add_edd_page_rule( $m, '' );
			}

			if ( $this->mapping_supports_dokan_pages( $m ) ) {
				$this->add_dokan_page_rule( $m, '' );
			}

			if ( $this->mapping_supports_sub_pages( $m ) ) {
				$this->add_sub_page_rule( $m, '' );
			}

			if ( $this->mapping_supports_pagination( $m ) ) {
				$this->append_rule(
					self::FALLBACK_MATCH . '/' . self::PAGINATION_PART_MATCH . '/?$',
					add_query_arg( 'paged', '$matches[1]', $query ) );
			}

			if ( $this->mapping_supports_auto_map_posts( $m ) ) {
				$this->add_auto_map_posts_rule( $m, '' );
			}

			$this->append_rule( self::FALLBACK_MATCH, $query );
		}
	}

	/**
	 * Setup rewrite rules associated with matching sub pages. The $base here is the custom URL defined in the mapping.
	 * The query string used in tandem with the rule here dictates the mapped post ID as well as the mapped path that
	 * comes after the $base. This information is then used in DomainIntercept\Context::_handle_sub_page_mapping() in
	 * order to correctly prepare the query.
	 *
	 * The query variables are namespaced here to prevent a conflict around wp-includes/class-wp.php:228 where WordPress
	 * looks for the 'pagename' query var via a string match.
	 *
	 * @param array $mapping
	 * @param string $base The base of the match string. Leave empty to map sub pages against the root.
	 */
	private function add_sub_page_rule( array $mapping, $base = '' ) {
		$base and $base = trailingslashit( $base );
		$base           = $this->append_language_rule( $base, $mapping );

		$this->append_rule(
			"^$base(.?.+?)(?:/([0-9]+))?/?$",
			add_query_arg( Request::WPLK_QUERY_VAR, [
				'is_sub_page_mapping' => true,
				'root_post_id' => $this->get_mapped_post_id( $mapping ),
				'pagename' => '$matches[1]',
				'page' => '$matches[2]'
			], 'index.php' )
		);
	}

	/**
	 * Setup rewrite rules associated with matching WooCommerce pages. The $base here is the custom URL defined in the mapping.
	 * The query string used in tandem with the rule here dictates the mapped post ID as well as the mapped path that
	 * comes after the $base. This information is then used in DomainIntercept\Context::_handle_sub_page_mapping() in
	 * order to correctly prepare the query.
	 *
	 * The query variables are namespaced here to prevent a conflict around wp-includes/class-wp.php:228 where WordPress
	 * looks for the 'pagename' query var via a string match.
	 *
	 * @param array $mapping
	 * @param string $base The base of the match string. Leave empty to map WooCommerce pages against the root.
	 */
	private function add_woo_page_rule( array $mapping, $base = '' ) {
		$base and $base = trailingslashit( $base );
		$map_woo_pages  = Arr::get( $mapping, 'map_woo_pages', false ) ? true : false;
		$base           = $this->append_language_rule( $base, $mapping );

		$this->append_rule(
			"^$base(.?.+?)(?:/([0-9]+))?/?$",
			add_query_arg( Request::WPLK_QUERY_VAR, [
				'is_woo_page_mapping' => $map_woo_pages,
				'pagename' => '$matches[1]',
				'page' => '$matches[2]',
			], 'index.php' )
		);
	}

	/**
	 * Setup rewrite rules associated with matching posts. The $base here is the custom URL defined in the mapping.
	 * The query string used in tandem with the rule here dictates the mapped post ID as well as the mapped path that
	 * comes after the $base. This information is then used in DomainIntercept\Context::_handle_sub_page_mapping() in
	 * order to correctly prepare the query.
	 *
	 * The query variables are namespaced here to prevent a conflict around wp-includes/class-wp.php:228 where WordPress
	 * looks for the 'pagename' query var via a string match.
	 *
	 * @param array $mapping
	 * @param string $base The base of the match string. Leave empty to map posts against the root.
	 */
	private function add_auto_map_posts_rule( array $mapping, $base = '' ) {
		$base and $base = trailingslashit( $base );
		$base           = $this->append_language_rule( $base, $mapping );
		$map_all_posts  = Arr::get( $mapping, 'auto_map_all_posts', false ) ? true : false;
		$post_type      = Arr::get( $mapping, 'post_type' );
		if ( empty( $post_type ) ) {
			$public_types   = array_filter(
				get_post_types(
					array(
						'public' => true,
					),
					'names'
				),
				function ( $name ) {
					return ! in_array( $name, array( 'attachment', 'page' ), true );
				}
			);
			$post_type    = $public_types;
		}
		if ( ! empty( $base ) && ( $map_all_posts && $post_type ) ) {
			$server       = new Server();
			$request_path = wp_parse_url( $server->request_uri(), PHP_URL_PATH );
			if ( is_array( $post_type ) ) {
				foreach ( $post_type as $type ) {
					$request_path = str_replace( $type . '/', '', $request_path );
				}
			} else {
				$request_path = str_replace( $post_type . '/', '', $request_path );
			}
			$post_exist = \get_page_by_path( $request_path, OBJECT, $post_type );
			if ( ! empty( $post_exist ) ) {
				$post_type = $post_exist->post_type;
				$base      = '';
			}
		}
		$this->append_rule(
			"^$base(.?.+?)(?:/([0-9]+))?/?$",
			add_query_arg( Request::WPLK_QUERY_VAR, [
				'post_type' => $post_type,
				'map_all_posts' => $map_all_posts,
				'pagename' => '$matches[1]',
				'page' => '$matches[2]',
			], 'index.php' )
		);
	}

	/**
	 * Setup rewrite rules associated with matching EDD pages. The $base here is the custom URL defined in the mapping.
	 * The query string used in tandem with the rule here dictates the mapped post ID as well as the mapped path that
	 * comes after the $base. This information is then used in DomainIntercept\Context::_handle_sub_page_mapping() in
	 * order to correctly prepare the query.
	 *
	 * The query variables are namespaced here to prevent a conflict around wp-includes/class-wp.php:228 where WordPress
	 * looks for the 'pagename' query var via a string match.
	 *
	 * @param array $mapping
	 * @param string $base The base of the match string. Leave empty to map EDD pages against the root.
	 */
	public function add_edd_page_rule( array $mapping, $base = '' ) {
		$base and $base = trailingslashit( $base );
		$map_edd_pages  = Arr::get( $mapping, 'map_edd_pages', false ) ? true : false;
		$base		    = $this->append_language_rule( $base, $mapping );

		$this->append_rule(
			"^$base(.?.+?)(?:/([0-9]+))?/?$",
			add_query_arg( Request::WPLK_QUERY_VAR, [
				'is_edd_page_mapping' => $map_edd_pages,
				'pagename' => '$matches[1]',
				'page' => '$matches[2]',
			], 'index.php' )
		);
	}

	/**
	 * Setup rewrite rules associated with matching dokan pages. The $base here is the custom URL defined in the mapping.
	 * The query string used in tandem with the rule here dictates the mapped post ID as well as the mapped path that
	 * comes after the $base. This information is then used in DomainIntercept\Context::_handle_sub_page_mapping() in
	 * order to correctly prepare the query.
	 *
	 * The query variables are namespaced here to prevent a conflict around wp-includes/class-wp.php:228 where WordPress
	 * looks for the 'pagename' query var via a string match.
	 *
	 * @param array $mapping
	 * @param string $base The base of the match string. Leave empty to map dokan pages against the root.
	 */
	public function add_dokan_page_rule( array $mapping, $base = '' ) {
		$base and $base  = trailingslashit( $base );
		$map_dokan_pages = Arr::get( $mapping, 'map_dokan_pages', false ) ? true : false;
		$base		     = $this->append_language_rule( $base, $mapping );

		$this->append_rule(
			"^$base(.?.+?)(?:/([0-9]+))?/?$",
			add_query_arg( Request::WPLK_QUERY_VAR, array(
				'is_dokan_page_mapping' => $map_dokan_pages,
				'pagename' => '$matches[1]',
				'page' => '$matches[2]',
			), 'index.php' )
		);
	}

	/**
	 * This could do with refinement as it could also pay to check if the mapped resources support pagination or not.
	 *
	 * @param array $mapping
	 *
	 * @return bool
	 */
	private function mapping_supports_pagination( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		$type_is_supported = in_array( Arr::get( $mapping, 'resource_type' ), [
			'post-type-archive',
			'taxonomy-term-archive',
			'dokan-store',
		] );

		if ( ! $type_is_supported ) {
			return false;
		}

		return Arr::get( $mapping, 'do_pagination', false );
	}

	private function mapping_supports_sub_pages( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		if ( ! is_post_type_hierarchical( $this->get_mapped_post_type( $mapping ) ) ) {
			return false;
		}

		return Arr::get( $mapping, 'map_sub_pages', false );
	}

	private function mapping_supports_woo_pages( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		if ( 'single-product' !== Arr::get( $mapping, 'resource_type' ) && 'dokan-store' !== Arr::get( $mapping, 'resource_type' ) ) {
			return false;
		}

		if ( Arr::get( $mapping, 'map_woo_pages', false ) ) {
			return true;
		}

		if ( Arr::get( $mapping, 'p', false ) && ( Arr::get( $mapping, 'post_type', false ) && 'product' === Arr::get( $mapping, 'post_type', false ) ) ) {
			return true;
		}

		return Arr::get( $mapping, 'map_woo_pages', false );
	}

	private function handle_language_mapping() {
		global $TRP_LANGUAGE;

		if( ! $TRP_LANGUAGE ) {
			return;
		}

		if ( $m = $this->domain->root_mapping() ) {
			$lang_slug = wplk_get_lang_slug( $TRP_LANGUAGE );
			$query     = $this->build_query( $m );
			$this->append_rule( "(^|\/)$lang_slug\/?$", $query );
		}
	}

	private function mapping_supports_auto_map_posts( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		if ( ! Arr::get( $mapping, 'auto_map_all_posts', false ) ) {
			return false;
		}

		return true;
	}

	private function mapping_supports_edd_pages( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		if ( 'single-download' !== Arr::get( $mapping, 'resource_type' ) ) {
			return false;
		}

		if ( Arr::get( $mapping, 'map_edd_pages', false ) ) {
			return true;
		}

		if ( Arr::get( $mapping, 'p', false ) && ( Arr::get( $mapping, 'post_type', false ) && 'download' === Arr::get( $mapping, 'post_type', false ) ) ) {
			return true;
		}

		return Arr::get( $mapping, 'map_edd_pages', false );
	}

	private function mapping_supports_dokan_pages( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		if( 'dokan-store' !== Arr::get( $mapping, 'resource_type' ) ) {
			return false;
		}

		return Arr::get( $mapping, 'map_dokan_pages', false );
	}

	private function get_mapped_post_id( array $mapping ) {
		$type_id_key_map = [
			'single-page'    => 'page_id',
			'single-post'    => 'post_id',
			'single-product' => 'post_id',
		];
		$id_key = Arr::get( $type_id_key_map, Arr::get( $mapping, 'resource_type' ) );

		if ( empty( $id_key ) ) {
			return null;
		}

		$id = Arr::get( $mapping, $id_key, null );

		// If we still don't have an ID at this point, try to 'p' key as a last attempt before returning null.
		$id or $id = Arr::get( $mapping, 'p', null );

		return $id;
	}

	/**
	 * @param array $mapping
	 *
	 * @return string|null
	 */
	private function get_mapped_post_type( array $mapping ) {
		if ( $post_type = Arr::get( $mapping, 'post_type' ) ) {
			return $post_type;
		}

		if ( 'single-page' === Arr::get( $mapping, 'resource_type' ) ) {
			return 'page';
		}

		if ( $post_id = $this->get_mapped_post_id( $mapping ) ) {
			return is_string( $post_type = get_post_type( $post_id ) ) ? $post_type : null;
		}

		return null;
	}

	private function build_match( array $mapping, $page_break = false ) {
		// If the URL is empty, there isn't a match to be built.
		if ( empty( $url_path = Arr::get( $mapping, 'url_path', '' ) ) ) {
			return '';
		}

		// If the mapping is marked as regex, don't modify it aside from adding pagination support.
		if ( Arr::get( $mapping, 'is_regex', false ) ) {
			return $url_path;
		}

		// If we are this far in, regex isn't being used so format the string accordingly.
		// Note: if we need to process match strings further, this is where we would handle it.
		$match = untrailingslashit( $url_path );
		$match = $this->append_language_rule( $match, $mapping );

		// todo - evaluate whether we should enforce the optional trailing slash

		return $page_break ? "^$match(?:/([0-9]+))?/?$" : "^$match/?$";
	}

	private function build_paginated_match( array $mapping ) {
		// If the URL is empty, there isn't a match to be built.
		if ( empty( $url_path = Arr::get( $mapping, 'url_path', '' ) ) ) {
			return '';
		}

		// If the mapping is marked as regex, don't modify it aside from adding pagination support.
		if ( Arr::get( $mapping, 'is_regex', false ) ) {
			return rtrim( $url_path, '/$' ) . '/' . self::PAGINATION_PART_MATCH;
		}

		// If we are this far in, regex isn't being used so format the string accordingly.
		// Note: if we need to process match strings further, this is where we would handle it.
		$match = untrailingslashit( $url_path ) . '/' . self::PAGINATION_PART_MATCH;
		$match = $this->append_language_rule( $match, $mapping );

		// todo - evaluate whether we should enforce the optional trailing slash
		return "^$match/?$";
	}

	private function build_query( array $mapping ) {
		$action = Arr::get( $mapping, 'action', 'map_to_resource' );

		switch ( $action ) {
			case 'redirect':
				$args = $this->get_redirect_args( $mapping );
				break;
			case 'map_to_resource':
				$args = $this->get_rewrite_args( $mapping );
				break;
			default:
				$args = [];
		}

		return empty( $args ) ? '' : add_query_arg( $args, 'index.php' );
	}

	private function get_redirect_args( array $mapping ) {
		$url = Arr::get( $mapping, 'redirect_url' );
		$status = Arr::get( $mapping, 'redirect_status', 302 );

		if ( empty( $url ) || empty( $status ) ) {
			return [];
		}

		return [
			Request::WPLK_QUERY_VAR => [
				'redirect' => [ 'to' => $url, 'status' => $status, ]
			]
		];
	}

	private function get_rewrite_args( array $mapping ) {
		$args = [];
		switch ( Arr::get( $mapping, 'resource_type' ) ) {
			case 'single-post':
				$args['p'] = Arr::get( $mapping, 'p' );
				$args['post_type'] = Arr::get( $mapping, 'post_type' );
				break;

			case 'single-page':
				$args['page_id'] = Arr::get( $mapping, 'page_id' );
				break;

			case 'post-type-archive':
				$args['post_type'] = Arr::get( $mapping, 'post_type' );
				break;

			case 'taxonomy-term-archive':
				$args[ Request::WPLK_QUERY_VAR ]['taxonomy'] = Arr::get( $mapping, 'taxonomy' );
				$args[ Request::WPLK_QUERY_VAR ]['term_id'] = Arr::get( $mapping, 'term_id' );
				break;

			case 'single-product':
			case 'single-download':
				$args['p'] = Arr::get( $mapping, 'p' );
				$args['post_type'] = Arr::get( $mapping, 'post_type' );
				break;
			case 'dokan-store':
				$store_url        = get_option( 'dokan_general' );
				$store_url        = isset( $store_url['custom_store_url'] ) ? $store_url['custom_store_url'] : 'store';
				$args[$store_url] = Arr::get( $mapping, 'vendor' );
				break;
		}
		return $args;
	}

	/**
	 * Appends a rule to the rules array provided all necessary data points are available to do so.
	 *
	 * @param $regex
	 * @param $query
	 */
	private function append_rule( $regex, $query ) {
		if ( $regex and $query ) {
			$this->rules[ $regex ] = $query;
		}
	}

	/**
	 * Append language slug to the current path.
	 *
	 * @param string $path    The path of the match string.
	 * @param array  $mapping The mapping array.
	 *
	 * @return string
	 */
	private function append_language_rule( $path, $mapping ) {
		if ( ! class_exists( 'TRP_Translate_Press' ) ) {
			return $path;
		}

		global $TRP_LANGUAGE;

		if ( ! $TRP_LANGUAGE ) {
			return $path;
		}

		$mapped_lang  = Arr::get( $mapping, 'language', '' );
		$trp_settings = get_option( 'trp_settings' );

		if ( ( isset( $trp_settings['add-subdirectory-to-default-language'] ) && 'yes' === $trp_settings['add-subdirectory-to-default-language'] ) || $TRP_LANGUAGE !== $mapped_lang ) {
			$locale_slug = wplk_get_lang_slug( $TRP_LANGUAGE );
			$path        = $locale_slug . '/' . $path;
		}

		return $path;
	}
}