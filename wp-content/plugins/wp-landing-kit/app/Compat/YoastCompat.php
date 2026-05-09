<?php

namespace WpLandingKit\Compat;

use WpLandingKit\Settings;
use WpLandingKit\Http\Server;
use WpLandingKit\DomainIntercept\DomainMap;

/**
 * Class YoastCompat
 *
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with Yoast.
 */
class YoastCompat {

	/**
	 * Domain map instance for retrieving mapped URLs.
	 *
	 * @var DomainMap
	 */
	private $map;

	/**
	 * YoastCompat constructor.
	 *
	 * @param DomainMap $map Domain mapping handler instance.
	 */
	public function __construct( DomainMap $map ) {
		$this->map = $map;
	}

	/**
	 * Initialize compatibility filters for Yoast SEO.
	 */
	public function init() {
		if ( $this->is_yoast_running() ) {
			add_filter( 'wpseo_opengraph_url', array( $this, 'wplk_change_yoast_opengraph_url' ) );
			add_filter( 'wpseo_opengraph_image', array( $this, 'wplk_change_yoast_opengraph_url' ) );
			add_filter( 'wpseo_twitter_image', array( $this, 'wplk_change_yoast_opengraph_url' ) );
		}
	}

	/**
	 * Check if Yoast SEO plugin is active.
	 *
	 * @return bool True if Yoast SEO is running, false otherwise.
	 */
	public function is_yoast_running() {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Replace OpenGraph and sitemap URLs with domain-mapped URLs.
	 *
	 * @param string $url Original URL provided by Yoast.
	 *
	 * @return string Modified URL with correct domain.
	 */
	public function wplk_change_yoast_opengraph_url( $url ) {
		return str_replace( wp_parse_url( $url, PHP_URL_HOST ), wp_parse_url( get_site_url(), PHP_URL_HOST ), $url );
	}
}
