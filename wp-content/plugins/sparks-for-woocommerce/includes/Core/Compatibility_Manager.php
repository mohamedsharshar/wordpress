<?php
/**
 * Compatibility Manager
 *
 * @package Codeinwp\Sparks\Core
 */
namespace Codeinwp\Sparks\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Go;
use Codeinwp\Sparks\Core\Compatibility\Php;
use Codeinwp\Sparks\Core\Compatibility\Base;
use Codeinwp\Sparks\Core\Compatibility\Neve;
use Codeinwp\Sparks\Core\Compatibility\Otter;
use Codeinwp\Sparks\Core\Compatibility\Astra;
use Codeinwp\Sparks\Core\Compatibility\Hestia;
use Codeinwp\Sparks\Core\Compatibility\Kadence;
use Codeinwp\Sparks\Core\Compatibility\Oceanwp;
use Codeinwp\Sparks\Core\Compatibility\Flatsome;
use Codeinwp\Sparks\Core\Compatibility\Neve_Pro;
use Codeinwp\Sparks\Core\Compatibility\Storefront;
use Codeinwp\Sparks\Core\Compatibility\Type\Theme;
use Codeinwp\Sparks\Core\Compatibility\Woocommerce;
use Codeinwp\Sparks\Core\Compatibility\Fallback_Theme;

/**
 * Class Compatibility_Manager
 * Checks if there are any incompatible
 */
final class Compatibility_Manager {

	/**
	 * Compatibilities
	 *
	 * @var array
	 */
	private $compatibilities = [];

	/**
	 * Current theme
	 * That keeps compatibility class instance of the currently activated WP theme.
	 *
	 * @var Theme|null
	 */
	private static $current_theme = null;

	/**
	 * Keeps self instance
	 *
	 * @var $this|null
	 */
	private static $instance = null;

	/**
	 * Compatibility alert messages
	 *
	 * @var array<int, array{title:string, message:string}> keeps messages to show to the user.
	 */
	private $alerts = [];

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		$this->set_compatibilities();
		$this->set_current_theme();
	}

	/**
	 * Get Instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			return new static();
		}

		return self::$instance;
	}

	/**
	 * Push a compatibility alert.
	 *
	 * @param  array<int, array{title: string, message: string}> $alerts Alert messages.
	 * @return void
	 */
	protected function push_alerts( $alerts ) {
		$this->alerts = array_merge( $this->get_alerts(), $alerts );
	}

	/**
	 * Get compatibility alerts
	 *
	 * @return array<int, array{title: string, message: string}>
	 */
	protected function get_alerts() {
		return $this->alerts;
	}

	/**
	 * Set Compatibilities
	 *
	 * @return void
	 */
	public function set_compatibilities() {
		// Note: Fallback_Theme::class should not be registered on here.
		$compatibilities = [
			Neve_Pro::class,
			Neve::class,
			Php::class,
			Woocommerce::class,
			Astra::class,
			Hestia::class,
			Kadence::class,
			Oceanwp::class,
			Storefront::class,
			Flatsome::class,
			Go::class,
			Otter::class,
		];

		$this->compatibilities = array_map(
			function( $class ) {
				return $class::get_instance();
			},
			apply_filters( 'sparks_compatibilities', $compatibilities )
		);
	}

	/**
	 * Get compatibility instances.
	 *
	 * @return Base[]
	 */
	private function get_compatibilities() {
		return $this->compatibilities;
	}

	/**
	 * Check if there is any incompatible stuff. ( Only make compatibility check for only activated stuff )
	 *
	 * @return bool
	 */
	public function check() {
		$status = true;

		foreach ( $this->compatibilities as $compatibility ) {
			if ( $compatibility->allow_core_loading() === false ) {
				$this->push_alerts( $compatibility->get_alerts() );

				if ( true === $status ) {
					$status = false;
				}
			}
		}

		return $status;
	}

	/**
	 * Dispatch admin notices if there are.
	 *
	 * @return void
	 */
	public function dispatch_admin_notices() {
		add_action( 'admin_notices', [ $this, 'render_admin_notices' ] );
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public function render_admin_notices() {
		foreach ( $this->get_alerts() as $alert_details ) {
			sparks_get_template( 'compatibility', 'alert', $alert_details );
		}
	}

	/**
	 * Set current theme to $current_theme property.
	 *
	 * @return void
	 */
	private function set_current_theme() {
		foreach ( $this->get_compatibilities() as $compatibility ) {
			if ( ! ( $compatibility instanceof Theme ) ) {
				continue;
			}

			if ( $compatibility->get_stylesheet() === wp_get_theme( get_template() )->get_stylesheet() ) {
				self::$current_theme = $compatibility;
				break;
			}
		}

		if ( is_null( self::$current_theme ) ) {
			/**
			 * Fallback Theme
			 *
			 * @var \Codeinwp\Sparks\Core\Compatibility\Type\Theme $fallback_instance
			 */
			$fallback_instance   = Fallback_Theme::get_instance();
			self::$current_theme = $fallback_instance;
		}
	}

	/**
	 * Get compatibility instance of the current theme.
	 *
	 * @return Theme
	 */
	public function get_current_theme() {
		return self::$current_theme;
	}

	/**
	 * Not allow the cloning the instance.
	 *
	 * @return void
	 */
	private function __clone() {

	}

	/**
	 * Not allow the serialize the instance.
	 *
	 * @return array
	 */
	public function __sleep() {
		return [];
	}

	/**
	 * Not allow the unserialize the instance.
	 *
	 * @return void
	 */
	public function __wakeup() {

	}
}
