<?php

namespace WpLandingKit\Compat;

use WpLandingKit\Settings;
use WpLandingKit\Http\Server;
use WpLandingKit\DomainIntercept\DomainMap;

/**
 * Class RankMathCompat
 *
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with RankMath.
 */
class RankMathCompat {

	/**
	 * Domain map instance for retrieving mapped URLs.
	 *
	 * @var DomainMap
	 */
	private $map;

	/**
	 * RankMathCompat constructor.
	 *
	 * @param DomainMap $map Domain mapping handler instance.
	 */
	public function __construct( DomainMap $map ) {
		$this->map = $map;
	}

	/**
	 * Initialize compatibility filters for RankMath SEO.
	 */
	public function init() {
		if ( $this->is_rankmath_running() ) {
			add_filter( 'rank_math/opengraph/facebook/image', array( $this, 'wplk_change_rankmath_opengraph_url' ) );
			add_filter( 'rank_math/opengraph/twitter/image', array( $this, 'wplk_change_rankmath_opengraph_url' ) );
		}
	}

	/**
	 * Check if RankMath SEO plugin is active.
	 *
	 * @return bool True if RankMath SEO is running, false otherwise.
	 */
	public function is_rankmath_running() {
		return defined( 'RANK_MATH_VERSION' );
	}

	/**
	 * Replace OpenGraph and sitemap URLs with domain-mapped URLs.
	 *
	 * @param string $url Original URL provided by RankMath.
	 *
	 * @return string Modified URL with correct domain.
	 */
	public function wplk_change_rankmath_opengraph_url( $url ) {
		return str_replace( wp_parse_url( $url, PHP_URL_HOST ), wp_parse_url( get_site_url(), PHP_URL_HOST ), $url );
	}
}
