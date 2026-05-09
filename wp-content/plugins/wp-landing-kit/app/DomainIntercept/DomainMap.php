<?php

namespace WpLandingKit\DomainIntercept;

use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Framework\Utils\Str;
use WpLandingKit\Models\Domain;

class DomainMap {

	/**
	 * @var array [
	 *      'domains' => [
	 *          '{domain_post_id}' => 'mydomain.com',
	 *          '{domain_post_id}' => 'mydomain2.com',
	 *      ],
	 *      'posts' => [
	 *          '{post_id}' => [
	 *              '{domain_post_id}:/some/url',
	 *              '{domain_post_id}:/some/other/url',
	 *              '{domain_post_id}:/some/nested/url:subpages=1', // this demonstrates an option string
	 *          ],
	 *      ],
	 *      'terms' => [
	 *          '{term_id}' => [
	 *              '{domain_post_id}:/some/url',
	 *              '{domain_post_id}:/some/other/url',
	 *          ],
	 *      ],
	 *      'post_type_archives' => [
	 *          '{post_type}' => [
	 *              '{domain_post_id}:/some/url'
	 *              '{domain_post_id}:/some/other/url'
	 *          ],
	 *      ]
	 * ]
	 */
	private $map = [
		'domains' => [],
		'posts' => [],
		'terms' => [],
		'post_type_archives' => [],
		'woocommerce' => [],
		'edd' => [],
		'dokan' => [],
	];

	/**
	 * @var array Track whether or not mappings have been loaded.
	 */
	private $loaded = [
		'domains' => false,
		'posts' => false,
		'terms' => false,
		'post_type_archives' => false,
		'woocommerce' => false,
		'edd' => false,
		'dokan' => false,
	];

	public function init() {
		$this->load_domain_mappings();

		// At this stage, we only need these in the admin.
		if ( is_admin() ) {
			$this->load_post_mappings();
			$this->load_term_mappings();
			$this->load_woocommerce_mappings();
			$this->load_edd_mappings();
		}
	}

	public function load_domain_mappings() {
		$this->load_mappings_for( 'domains' );
	}

	public function load_post_mappings() {
		$this->load_mappings_for( 'posts' );
	}

	public function load_term_mappings() {
		$this->load_mappings_for( 'terms' );
	}

	public function load_post_type_archive_mappings() {
		$this->load_mappings_for( 'post_type_archives' );
	}

	public function load_woocommerce_mappings() {
		$this->load_mappings_for( 'woocommerce' );
	}

	public function load_edd_mappings() {
		$this->load_mappings_for( 'edd' );
	}

	public function load_dokan_mappings() {
		$this->load_mappings_for( 'dokan' );
	}

	public function reset() {
		$this->map = [
			'domains' => [],
			'posts' => [],
			'terms' => [],
			'post_type_archives' => [],
			'woocommerce' => [],
			'edd' => [],
			'dokan' => [],
		];
		$this->loaded = [
			'domains' => false,
			'posts' => false,
			'terms' => false,
			'post_type_archives' => false,
			'woocommerce' => false,
			'edd' => false,
			'dokan' => false,
		];
	}

	public function update_domain( Domain $domain ) {
		$this->unmap_domain( $domain );

		if ( $domain->is_active() ) {
			$this->map_domain( $domain );
		}
	}

	public function get_domain_id( $host_name ) {
		if ( $domains = Arr::get( $this->map, 'domains', [] ) ) {
			return Arr::get( array_flip( $domains ), $host_name, null );
		}

		return null;
	}

	public function save() {
		update_option( 'wplk-map-domains', $this->map['domains'], true );
		update_option( 'wplk-map-posts', $this->map['posts'], false );
		update_option( 'wplk-map-terms', $this->map['terms'], false );
		update_option( 'wplk-map-post_type_archives', $this->map['post_type_archives'], false );
		update_option( 'wplk-map-woocommerce', $this->map['woocommerce'], false );
		update_option( 'wplk-map-edd', $this->map['edd'], false );
		update_option( 'wplk-map-dokan', $this->map['dokan'], false );
	}

	public function get_first_url_for_post_id( $id ) {
		if ( has_post_parent( $id ) and $urls = $this->get_urls_for_post_id( $id ) ) {
			return $urls[0];
		}

		if ( $mappings = Arr::get_deep( $this->map, "posts.$id", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}

	/**
	 * Resolve all mapped URLs for a given post ID.
	 *
	 * @param int $id The post ID.
	 * @param array $where_opts An array of key/value pairs (options) to filter the list of URLs by. This only applies
	 *        when a mapped URL has an options query string appended.
	 * @param bool $check_root_visibility Whether or not to omit posts that aren't a root mapping but have sub pages.
	 *        This is only necessary due to the recursive nature of this method.
	 *
	 * @return array An array of mapped URLs.
	 */
	public function get_urls_for_post_id( $id, $where_opts = [], $check_root_visibility = true ) {
		$urls = [];

		// Handle sub page mappings if/when available.
		if ( $parent_id = wp_get_post_parent_id( $id ) and $parent_urls = $this->get_urls_for_post_id( $parent_id, [ 'subpages' => 1 ], false ) ) {
			$name = get_post( $id )->post_name;
			$full_urls = array_map( function ( $url ) use ( $name ) {
				return trailingslashit( $url ) . Str::unleadingslashit( $name );
			}, $parent_urls );
			$urls = array_merge( $urls, $full_urls );
		}

		if ( ! $mappings = Arr::get_deep( $this->map, "posts.$id", [] ) ) {
			return $urls;
		}

		if ( $check_root_visibility ) {
			// Omit any mappings that handle sub pages but are not the root mapping on their domain. This ensures we
			// don't end up showing the domain where it isn't actually accessible in the post list screen.
			$mappings = array_filter( $mappings, function ( $string ) {
				return ! ( Str::contains( $string, ':/:' ) and Str::contains( $string, 'subpages=1' ) and Str::missing( $string, 'root=1' ) );
			} );
		}

		return array_merge( $urls, array_filter( array_map( function ( $mapping ) use ( $where_opts ) {
			return $this->resolve_url_for_mapping( $mapping, $where_opts );
		}, $mappings ) ) );
	}

	public function get_first_url_for_term_id( $id ) {
		if ( $mappings = Arr::get_deep( $this->map, "terms.$id", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}

	public function get_first_url_for_post_type_archive( $type ) {
		if ( $mappings = Arr::get_deep( $this->map, "post_type_archives.$type", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}

	public function get_first_url_for_woocommerce( $parent_page_slug ) {
		if ( $mappings = Arr::get_deep( $this->map, "woocommerce.pages.$parent_page_slug", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}

	public function get_first_url_for_edd( $parent_page_slug ) {
		if ( $mappings = Arr::get_deep( $this->map, "edd.pages.$parent_page_slug", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}

	public function get_first_url_for_dokan( $parent_page_slug ) {
		if ( $mappings = Arr::get_deep( $this->map, "dokan.pages.$parent_page_slug", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}
	}

	/**
	 * Load up mappings for a given type provided they haven't already been loaded.
	 *
	 * @param string $type One of the keys within $this->map.
	 */
	private function load_mappings_for( $type ) {
		if ( $this->loaded[ $type ] ) {
			return;
		}

		$this->map[ $type ] = get_option( "wplk-map-{$type}", [] );
		$this->loaded[ $type ] = true;
	}

	/**
	 * Convert the mapped string — e.g; '{domain_post_id}:/some/url' — to a full URL
	 *
	 * @param string $mapping
	 * @param array $where_opts A hash of options and their values that need to be met for the URL to resolve.
	 *
	 * @return string
	 */
	private function resolve_url_for_mapping( $mapping, $where_opts = [] ) {
		$mapping = explode( ':', $mapping );
		$domain_id = $mapping[0];
		$path = $mapping[1];
		$host = $this->map['domains'][ $domain_id ];

		// Parse options string into array, if available.
		$opts = [];
		if ( isset( $mapping[2] ) ) {
			parse_str( $mapping[2], $opts );
		}

		// If a conditions array is provided, make sure this mapping URL's options string matches the conditions
		// specified. If not, bail early.
		if ( $where_opts ) {
			foreach ( $where_opts as $key => $value ) {
				if ( ! isset( $opts[ $key ] ) or $opts[ $key ] != $value ) {
					return '';
				}
			}
		}

		if ( $domain = Domain::find( $domain_id ) ) {
			return $domain->protocol() . $host . $path;
		}

		return '';
	}

	private function map_domain( Domain $domain ) {
		$this->map['domains'][ $domain->ID ] = $domain->host();

		foreach ( array_values( $domain->mappings() ) as $index => $m ) {
			$query = [];

			if ( Arr::get( $m, 'action' ) !== 'map_to_resource' ) {
				continue;
			}

			switch ( Arr::get( $m, 'resource_type' ) ) {
				case 'single-post':
					$id = Arr::get( $m, 'p' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $id ) {
						// This is basically checking if we are processing a root mapping. It would be better if we add
						// a data point on the mapping itself that defines this so we can check for something more
						// sensible than an index.
						if ( $index === 0 ) {
							$query['root'] = 1;
						}
						if ( Arr::get( $m, 'map_sub_pages' ) ) {
							$query['subpages'] = 1;
						}
						$this->map['posts'][ $id ][] = "$domain->ID:/$path" . ( empty( $query ) ? '' : ':' . http_build_query( $query ) );
					}
					break;

				case 'single-page':
					$id = Arr::get( $m, 'page_id' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $id ) {
						// This is basically checking if we are processing a root mapping. It would be better if we add
						// a data point on the mapping itself that defines this so we can check for something more
						// sensible than an index.
						if ( $index === 0 ) {
							$query['root'] = 1;
						}
						if ( Arr::get( $m, 'map_sub_pages' ) ) {
							$query['subpages'] = 1;
						}
						$this->map['posts'][ $id ][] = "$domain->ID:/$path" . ( empty( $query ) ? '' : ':' . http_build_query( $query ) );
					}
					break;

				case 'taxonomy-term-archive':
					$id = Arr::get( $m, 'term_id' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $id ) {
						$this->map['terms'][ $id ][] = "$domain->ID:/$path";
					}
					break;

				case 'post-type-archive':
					$type = Arr::get( $m, 'post_type' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $type ) {
						$this->map['post_type_archives'][ $type ][] = "$domain->ID:/$path";
					}
					break;

				case 'single-product':
					$id   = Arr::get( $m, 'p' );
					$type = Arr::get( $m, 'map_woo_pages' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $type ) {
						if ( $index === 0 ) {
							$query['root'] = 1;
						}
						$woo_pages = wplk_woocommerce_pages();
						foreach ( $woo_pages as $endpoint ) {
							$domain_url = preg_replace( '/(\/+)/', '/', "$domain->ID:/$path/$endpoint" );

							$this->map['woocommerce']['pages'][ $endpoint ][] = "$domain_url" . ( empty( $query ) ? '' : ':' . http_build_query( $query ) );
						}
					}

					if ( $id ) {
						// This is basically checking if we are processing a root mapping. It would be better if we add
						// a data point on the mapping itself that defines this so we can check for something more
						// sensible than an index.
						if ( $index === 0 ) {
							$query['root'] = 1;
						}

						$this->map['posts'][ $id ][] = "$domain->ID:/$path" . ( empty( $query ) ? '' : ':' . http_build_query( $query ) );
					}
					break;

				case 'single-download':
					$id = Arr::get( $m, 'p' );
					$type = Arr::get( $m, 'map_edd_pages' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $type ) {
						if ( $index === 0 ){
							$query['root'] = 1;
						}
						$edd_pages = wplk_edd_pages();
						foreach ( $edd_pages as $page ) {
							$domain_url = preg_replace( '/(\/+)/', '/', "$domain->ID:/$path/$page" );
							$this->map['edd']['pages'][ $page ][] = "$domain_url" . ( empty( $query ) ? '' : ':' . http_build_query( $query ) );
						}
					}

					if ( $id ) {
						// This is basically checking if we are processing a root mapping. It would be better if we add
						// a data point on the mapping itself that defines this so we can check for something more
						// sensible than an index.
						if ( $index === 0 ) {
							$query['root'] = 1;
						}
						$this->map['posts'][ $id ][] = "$domain->ID:/$path" . ( empty( $query ) ? '' : ':' . http_build_query( $query ) );
					}
					break;

				case 'dokan-store':
					$id   = Arr::get( $m, 'vendor' );
					$type = Arr::get( $m, 'map_woo_pages' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $type ) {
						if ( $index === 0 ) {
							$query['root'] = 1;
						}
						$dokan_pages = wplk_woocommerce_pages();
						foreach( $dokan_pages as $page ) {
							$domain_url = preg_replace( '/(\/+)/', '/', "$domain->ID:/$path/$page" );
							$this->map['dokan']['pages'][ $page ][] = "$domain_url" . ( empty( $query ) ? '' : ':' ) . http_build_query( $query );
						}
					}

					if ( $id ) {
						if ( $index === 0 ) {
							$query['root'] = 1;
						}
						$this->map['dokan']['pages'][ $id ][] = "$domain->ID:/$path" . ( empty( $query ) ? '' : ':' . http_build_query( $query ) );
					}
					break;
			}

		}
	}

	private function unmap_domain( Domain $domain ) {
		if ( ! $this->has_domain_mapped( $domain ) ) {
			return;
		}

		// Remove the domain mapping itself.
		unset( $this->map['domains'][ $domain->ID ] );

		// Inline utility for stripping. This is fine for now but we should tidy up later.
		$strip_from = function ( $key ) use ( $domain ) {
			foreach ( $this->map[ $key ] as $id => $urls ) {
				// Filter out mappings that start with the domain ID we are removing.
				$remaining = array_filter( $urls, function ( $url ) use ( $domain ) {
					if ( is_array( $url ) ) {
						$m = explode( ':', reset( $url ) );
					} else {
						$m = explode( ':', $url );
					}

					return $m[0] != $domain->ID;
				} );

				// if there are any remaining mappings, leave them, otherwise unset the post ID from the mappings.
				if ( $remaining ) {
					$this->map[ $key ][ $id ] = $remaining;
				} else {
					unset( $this->map[ $key ][ $id ] );
				}
			}
		};

		// Remove mappings.
		$strip_from( 'posts' );
		$strip_from( 'terms' );
		$strip_from( 'post_type_archives' );
		$strip_from( 'woocommerce' );
		$strip_from( 'edd' );
		$strip_from( 'dokan' );
	}

	private function has_domain_mapped( Domain $domain ) {
		return Arr::get_deep( $this->map, "domains.{$domain->ID}", false );
	}

}