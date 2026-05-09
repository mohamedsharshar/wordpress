<?php
/**
 * Base
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Platform;
use Codeinwp\Sparks\Core\Compatibility\Type\Theme;
use Codeinwp\Sparks\Core\Compatibility\Type\Plugin;

/**
 * Class Base
 */
abstract class Base {
	/**
	 * Human readable name such as Neve Pro etc.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Compatibility alert messages
	 *
	 * @var array<int, array{title:string, message:string}> keeps messages to show to the user.
	 */
	private $alerts = [];

	/**
	 * Compatibility instances.
	 *
	 * @var array $instances stores instances of the \Codeinwp\Sparks\Core\Compatibility\Base
	 */
	public static $instances = [];

	/**
	 * If this compatibility is required by Sparks as mandatory or not.
	 *
	 * @var bool|null
	 */
	protected $needed_for_core = null;

	/**
	 * Constructor
	 *
	 * @throws \Exception If needed_for_core class property is missing.
	 * @return void
	 */
	final private function __construct() {
		if ( is_null( $this->needed_for_core ) ) {
			/* translators: %s: compatibility class */
			throw new \Exception( sprintf( esc_html__( 'needed_for_core property must be defined, missing on %s.', 'sparks-for-woocommerce' ), static::class ) );
		}

		$this->init();
	}

	/**
	 * Get Instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		$class_name = get_called_class();

		if ( ! array_key_exists( $class_name, self::$instances ) ) {
			self::$instances[ $class_name ] = new $class_name(); // @phpstan-ignore-line
		}

		return self::$instances[ $class_name ];
	}

	/**
	 * Initialization method - should be implemented as optionally
	 *
	 * @return void
	 */
	public function init() {}

	/**
	 * Get label of the dependency type.
	 *
	 * TODO: Evaluate if there is need to this method.
	 *
	 * Human readable label of the dependency type.
	 *
	 * @throws \Exception If compatibility type label cannot be found.
	 *
	 * @return string suchh as Plugin, Theme, PHP Version etc.
	 */
	public function get_type_label() {
		$map = [
			__( 'Platform', 'sparks-for-woocommerce' ) => Platform::class,
			__( 'Theme', 'sparks-for-woocommerce' )    => Theme::class,
			__( 'Plugin', 'sparks-for-woocommerce' )   => Plugin::class,
		];

		foreach ( $map as $label => $class ) {
			if ( is_subclass_of( get_class( $this ), $class ) ) {
				return $label;
			}
		}

		throw new \Exception( __( 'Compatibility class type label could not found.', 'sparks-for-woocommerce' ) );
	}

	/**
	 * Get type of the dependency
	 *
	 * TODO: Evaluate if there is need to this method.
	 *
	 * Only can consist from lowercase letters and underscore.
	 *
	 * @throws \Exception If compatibility type cannot be found.
	 *
	 * @return string Such as plugin,theme,php_version etc.
	 */
	public function get_type() {
		$map = [
			'platform' => Platform::class,
			'theme'    => Theme::class,
			'plugin'   => Plugin::class,
		];

		foreach ( $map as $type => $class ) {
			if ( is_subclass_of( get_class( $this ), $class ) ) {
				return $type;
			}
		}

		throw new \Exception( __( 'Compatibility class type cannot found.', 'sparks-for-woocommerce' ) );
	}

	/**
	 * Push a compatibility alert.
	 *
	 * @param  string $title Title of the WP Admin notice.
	 * @param  string $message Message content of the WP Admin notice.
	 * @return void
	 */
	protected function push_alert( $title, $message ) {
		$this->alerts[] = [
			'title'   => $title,
			'message' => $message,
		];
	}

	/**
	 * Get compatibility alerts
	 *
	 * @return array<int, array{title: string, message: string}>
	 */
	public function get_alerts() {
		return $this->alerts;
	}

	/**
	 * Get human readable name of the compatibility.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	abstract public function has_activated();

	/**
	 * Check if the dependency compatible with Sparks or not.
	 *
	 * @return bool
	 */
	abstract public function check();

	/**
	 * Are there any blocker in terms of compatibility, for loading of the Sparks Core.
	 *
	 * @return bool If there is no blocker return true
	 */
	public function allow_core_loading() {
		if ( $this->needed_for_core ) {
			if ( ! $this->has_activated() ) {
				return false;
			}

			return $this->check();
		}

		if ( $this->has_activated() && ! $this->check() ) {
			return false;
		}

		return true;
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
	final public function __sleep() {
		return [];
	}

	/**
	 * Not allow the unserialize the instance.
	 *
	 * @return void
	 */
	final public function __wakeup() {

	}
}
