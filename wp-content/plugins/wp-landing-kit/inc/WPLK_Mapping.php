<?php

use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Traits\HasReadOnlyProps;
use WpLandingKit\Utils\Log;
use WpLandingKit\Utils\Error;

/**
 * Class WPLK_Mapping
 *
 * Public PHP API class for working with a single domain mapping. This object provides a fluent API for creating
 * mappings for us in conjunction with the WPLK_Domain object. The object provides a central, sane place for building
 * mapping arrays via PHP.
 *
 * The properties on this object won't exactly match the config stored in the DB so it is always best to use this class
 * than rolling a custom array as this will handle any discrepancies/mutations required both in and out of the class.
 *
 * Also note that the properties on this object are read-only. They can be read from directly (via magic method) but
 * cannot be set outside the provided methods.
 *
 * @property-read $mapping_id
 * @property-read $url_path
 * @property-read $is_regex
 * @property-read $action
 * @property-read $resource_type
 * @property-read $post_type
 * @property-read $post_id
 * @property-read $taxonomy
 * @property-read $term_id
 * @property-read $do_pagination
 * @property-read $map_sub_pages
 * @property-read $redirect_url
 * @property-read $redirect_status
 */
class WPLK_Mapping {

	use HasReadOnlyProps;

	private $mapping_id;
	private $url_path;
	private $is_regex;
	private $action;
	private $resource_type;
	private $post_type;
	private $post_id;
	private $taxonomy;
	private $term_id;
	private $do_pagination;
	private $map_sub_pages;
	private $redirect_url;
	private $redirect_status;

	/**
	 * Make an instance from a mapping array. This facilitates both: 1. arrays with keys matching the object properties
	 * and also 2. arrays with key structures matching that which we store in the DB.
	 *
	 * @param array $arr
	 *
	 * @return WPLK_Mapping
	 */
	public static function from_array( array $arr ) {
		$instance = new self( '' );
		foreach ( self::normalize( $arr ) as $prop => $value ) {
			$instance->$prop = $value;
		}

		return $instance;
	}

	/**
	 * Normalize an array of mapping args to ensure only the necessary data is there for the given mapping. The mapping
	 * args supported are as per the data stored in the
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function normalize( array $args ) {
		$args = wp_parse_args( $args, [
			'mapping_id' => uniqid(),
			'url_path' => '',
			'is_regex' => false,
			'action' => 'map_to_resource',
			'resource_type' => '',
			'post_type' => '',
			'post_id' => '',
			'taxonomy' => '',
			'term_id' => '',
			'do_pagination' => true,
			'map_sub_pages' => false,
			'redirect_url' => '',
			'redirect_status' => 302,
		] );

		// Format stored arguments into normalized equivalents.
		if ( $p = Arr::get( $args, 'page_id' ) ) {
			$args['post_id'] = $p;
			$args['post_type'] = 'page';

		} elseif ( $p = Arr::get( $args, 'p' ) ) {
			$args['post_id'] = $p;
			// If the ID is passed in via 'p', it isn't going to be a page. If page is set as the post type, empty it
			// so we can use the post ID to determine the post type further down.
			if ( $args['post_type'] === 'page' ) {
				$args['post_type'] = '';
			}
		}

		// If the post ID is set, the mapping is for a single post. Ensure necessary props are also set.
		if ( ! empty( $args['post_id'] ) ) {
			if ( empty( $args['post_type'] ) ) {
				$args['post_type'] = get_post_type( $args['post_id'] ) ?: 'post';
			}

			$args['resource_type'] = ( $args['post_type'] === 'page' )
				? 'single-page'
				: 'single-post';
		}

		// If the term ID is set, the mapping is for a term archive page. Ensure necessary props are also set.
		if ( ! empty( $args['term_id'] ) ) {
			if ( empty( $args['taxonomy'] ) ) {
				if ( ( $term = get_term( $args['term_id'] ) ) instanceof WP_Term ) {
					$args['taxonomy'] = $term->taxonomy;
				} else {
					Log::warning( 'Could not determine taxonomy from term ID %d. "category" is infered. Mapping for URL "%s" may not function as expected.', $args['term_id'], $args['url_path'] );
					$args['taxonomy'] = 'category';
				}
				$args['resource_type'] = 'taxonomy-term-archive';
				$args['do_pagination'] = empty( $args['do_pagination'] ) ? true : (bool) $args['do_pagination'];
			}
		}

		// Return the appropriate subset of arguments depending on the mapping's configuration.
		if ( $args['action'] === 'redirect' ) {
			return Arr::only( $args, [
				'mapping_id',
				'url_path',
				'is_regex',
				'action',
				'redirect_url',
				'redirect_status'
			] );

		} elseif ( $args['action'] === 'map_to_resource' ) {
			switch ( $args['resource_type'] ) {
				case 'single-post':
				case 'single-page':
					return Arr::only( $args, [
						'mapping_id',
						'url_path',
						'is_regex',
						'action',
						'resource_type',
						'post_type',
						'post_id',
						'map_sub_pages',
					] );
				case 'taxonomy-term-archive':
					return Arr::only( $args, [
						'mapping_id',
						'url_path',
						'is_regex',
						'action',
						'resource_type',
						'taxonomy',
						'term_id',
						'do_pagination',
					] );
				case 'post-type-archive':
					return Arr::only( $args, [
						'mapping_id',
						'url_path',
						'is_regex',
						'action',
						'resource_type',
						'post_type',
						'do_pagination',
					] );
			}

		}

		return $args;
	}

	/**
	 * @param string $url_path
	 * @param bool $is_regex
	 */
	public function __construct( $url_path = '', $is_regex = false ) {
		$this->mapping_id = $this->id();

		if ( $url_path ) {
			$this->set_url_path( $url_path, $is_regex );
		}
	}

	/**
	 * Convert this object into an array correctly formatted for storage in the DB. Note that the output keys don't
	 * exactly match the properties on the object. This is due to the stored data more closely resembling WordPress'
	 * query args. If you need a normalized version of this object as an array, see \WPLK_Mapping::to_array();
	 *
	 * @return array
	 */
	public function to_db_array() {
		$m['mapping_id'] = $this->id();
		$m['url_path'] = $this->url_path;
		$m['is_regex'] = $this->is_regex;
		$m['action'] = $this->action;

		if ( $this->action === 'map_to_resource' ) {
			$m['resource_type'] = $this->resource_type;

			switch ( $this->resource_type ) {
				case 'single-post':
					$m['post_type'] = $this->post_type;
					$m['p'] = $this->post_id;
					$m['map_sub_pages'] = $this->map_sub_pages;
					break;

				case 'single-page':
					$m['post_type'] = $this->post_type;
					$m['page_id'] = $this->post_id;
					$m['map_sub_pages'] = $this->map_sub_pages;
					break;

				case 'taxonomy-term-archive':
					$m['taxonomy'] = $this->taxonomy;
					$m['term_id'] = $this->term_id;
					$m['do_pagination'] = $this->do_pagination;
					break;

				case 'post-type-archive':
					$m['post_type'] = $this->post_type;
					$m['do_pagination'] = $this->do_pagination;
					break;
			}

		} elseif ( $this->action === 'redirect' ) {
			$m['redirect_url'] = $this->redirect_url;
			$m['redirect_status'] = $this->redirect_status;
		}

		return $m;
	}

	/**
	 * @return array
	 */
	public function to_array() {
		return self::normalize( $this->to_db_array() );
	}

	/**
	 * @param $url_path
	 * @param bool $is_regex
	 */
	public function set_url_path( $url_path, $is_regex = false ) {
		$this->url_path = $url_path;
		$this->set_is_regex( $is_regex );
	}

	/**
	 * @param bool $bool
	 */
	public function set_is_regex( $bool = true ) {
		$this->is_regex = (bool) $bool;
	}

	/**
	 * Map to a given post.
	 *
	 * @param int|WP_Post $post Post ID or WP_Post object.
	 * @param bool $map_sub_pages Specified whether sub pages should be handled. Only works with hierarchical post types.
	 *
	 * @return bool|WP_Error
	 */
	public function maps_to_post( $post, $map_sub_pages = false ) {
		$post = get_post( $post );
		$post_type = get_post_type( $post );

		if ( false === $post_type ) {
			return Error::make( 'Unable to detect post type. Could not map %s to %s', $this->url_path, $post );
		}

		$this->reset();

		$this->action = 'map_to_resource';
		$this->post_type = $post_type;
		$this->post_id = $post->ID;

		$this->resource_type = ( $post_type === 'page' )
			? 'single-page'
			: 'single-post';

		$this->map_sub_pages = is_post_type_hierarchical( $post_type ) ? $map_sub_pages : false;

		return true;
	}

	/**
	 * @param int|WP_Term $term Term ID or WP_Term object.
	 *
	 * @param bool $support_pagination
	 *
	 * @return bool|WP_Error
	 */
	public function maps_to_term_archive( $term, $support_pagination = true ) {
		$term = get_term( $term );

		if ( ! $term instanceof WP_Term ) {
			return Error::make( 'Unable to get term object. Could not map %s to %s', $this->url_path, $term );
		}

		$this->reset();

		$this->action = 'map_to_resource';
		$this->resource_type = 'taxonomy-term-archive';
		$this->taxonomy = $term->taxonomy;
		$this->term_id = $term->term_id;
		$this->do_pagination = $support_pagination;

		return true;
	}

	/**
	 * @param string $post_type The name of the registered post type.
	 * @param bool $support_pagination
	 *
	 * @return bool
	 */
	public function maps_to_post_type_archive( $post_type, $support_pagination = true ) {
		$this->reset();
		$this->action = 'map_to_resource';
		$this->resource_type = 'post-type-archive';
		$this->post_type = $post_type;
		$this->do_pagination = $support_pagination;

		return true;
	}

	/**
	 * @param string $url Either a relative or absolute URL.
	 * @param string $http_status
	 *
	 * @return true
	 */
	public function redirects_to( $url, $http_status = '302' ) {
		$this->reset();
		$this->action = 'redirect';
		$this->redirect_url = $url;
		$this->redirect_status = $http_status;

		return true;
	}

	public function id() {
		if ( ! $this->mapping_id ) {
			$this->mapping_id = uniqid();
		}

		return $this->mapping_id;
	}

	/**
	 * Nullify props on this object that are resettable.
	 */
	public function reset() {
		$this->action = null;
		$this->resource_type = null;
		$this->post_type = null;
		$this->post_id = null;
		$this->taxonomy = null;
		$this->term_id = null;
		$this->do_pagination = null;
		$this->map_sub_pages = null;
		$this->redirect_url = null;
		$this->redirect_status = null;
	}

	/**
	 * Simple check to see if this object is set up to map or redirect to a resource.
	 *
	 * @return bool
	 */
	public function is_mapped() {
		// If any one of these is not null, we can consider this mapping as being mapped or redirected to a resource.
		$checks = [
			$this->post_type,
			$this->post_id,
			$this->term_id,
			$this->redirect_url,
		];

		$mapped = array_filter( $checks, function ( $prop ) {
			return $prop !== null;
		} );

		return ! empty( $mapped );
	}

}