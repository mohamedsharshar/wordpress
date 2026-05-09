<?php
/**
 * Base class for Module classes.
 *
 * @package Codeinwp\Sparks\Modules
 */
namespace Codeinwp\Sparks\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Core\Style;
use Codeinwp\Sparks\Core\Compatibility\Type\Theme;
use Codeinwp\Sparks\Core\Dynamic_Styles;

/**
 * Base_Module class
 */
abstract class Base_Module {
	/**
	 * Define prefix of the option keys.
	 *
	 * @var string
	 */
	const OPTION_PLUGIN_PREFIX = 'sparks';

	const SETTING_GROUP = 'sparks_settings';

	const ENABLE_MODULE_OPTION = 'enabled';

	/**
	 * Module name
	 *
	 * TODO: will be removed permanently.
	 *
	 * @deprecated That property is deprecated, please do not use it.
	 * @var string
	 */
	public $module_name;

	/**
	 * Module slug
	 *
	 * @var string|null
	 */
	protected $module_slug;

	/**
	 * Setting prefix, should be defined without sparks prefix (is used for get_option calls.)
	 *
	 * @var string|null
	 */
	protected $setting_prefix = null;

	/**
	 * Can be managed on dashboard?
	 *
	 * @var bool
	 */
	protected $manage_on_dashboard = true;

	/**
	 * If module has configuration options or not.
	 *
	 * @var bool
	 */
	protected $has_dashboard_config = false;

	/**
	 * Default module activation status
	 *
	 * @var bool|null
	 */
	protected $default_status = null;

	/**
	 * Dependencies
	 *
	 * @var \Codeinwp\Sparks\Core\Compatibility\Base[]
	 */
	protected $dependencies = [];

	/**
	 * Dependency Errors
	 *
	 * @var string[]
	 */
	protected $dependency_errors = [];

	/**
	 * Current Theme
	 *
	 * @var Theme
	 */
	private static $current_theme;

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = '';

	/**
	 * Get human readable module name.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Abstraction of the initialize method.
	 *
	 * @return void
	 */
	abstract public function init();

	/**
	 * Abstraction of the should_load method. The method decides whatever the module run or not.
	 *
	 * @return bool
	 */
	abstract protected function should_load();

	/**
	 * Constructor
	 *
	 * @throws \Exception If self::setting_prefix is missing.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->set_current_theme();

		if ( is_null( $this->default_status ) ) {
			/* translators:%s: a */
			throw new \Exception( sprintf( esc_html__( 'default_status class property of %s is missing.', 'sparks-for-woocommerce' ), get_class( $this ) ) );
		}

		if ( is_null( $this->setting_prefix ) ) {
			/* translators:%s: a */
			throw new \Exception( sprintf( esc_html__( 'Sparks module setting prefix of %s is missing.', 'sparks-for-woocommerce' ), get_class( $this ) ) );
		}

		if ( is_null( $this->module_slug ) ) {
			/* translators:%s: a */
			throw new \Exception( sprintf( esc_html__( 'Sparks module slug of %s is missing.', 'sparks-for-woocommerce' ), get_class( $this ) ) );
		}

		if ( $this->can_be_managed_on_dashboard() ) {
			add_action( 'init', [ $this, 'register_settings' ] );
		}
	}

	/**
	 * First initialization method that is fired by Loader.
	 *
	 * @return void|false
	 */
	final public function start() {
		if ( ! apply_filters( 'sparks_module_check_dependencies', $this->check_dependencies(), $this->get_slug() ) ) {
			return false;
		}

		$this->init();

		if ( apply_filters( 'sparks_needs_module_dynamic_style', true, $this->get_slug() ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'register_all_dynamic_styles' ) );
		}
	}

	/**
	 * Needs frontend assets or not.
	 *
	 * @return bool
	 */
	protected function needs_frontend_assets() {
		return true;
	}

	/**
	 * Register all dynamic CSS rules to centralized \Codeinwp\Sparks\Core\Dynamic_Styles class.
	 *
	 * @return void
	 */
	public function register_all_dynamic_styles() {
		if ( ! $this->needs_frontend_assets() ) {
			return;
		}

		$this->register_dynamic_theme_styles();
		$this->register_dynamic_styles();
	}

	/**
	 * Register the theme based dynamic module styles.
	 * If the current theme compatibility class has dynamic styles for this module, load.
	 *
	 * @return void
	 */
	private function register_dynamic_theme_styles() {
		$current_theme = $this->get_current_theme();

		if ( method_exists( $current_theme, $this->get_slug() ) ) {
			// class that theme based module style
			$module_theme = $current_theme->{$this->get_slug()}();

			$dynamic_styles = $module_theme->get_dynamic_styles();

			foreach ( $dynamic_styles as $selector => $rules ) {
				Dynamic_Styles::get_instance()->push(
					$selector,
					$rules
				);
			}
		}
	}

	/**
	 * Set current theme
	 *
	 * @return void
	 */
	private function set_current_theme() {
		self::$current_theme = sparks_current_theme();
	}

	/**
	 * Get current theme
	 *
	 * @return Theme
	 */
	protected function get_current_theme() {
		return self::$current_theme;
	}

	/**
	 * Get help URL
	 *
	 * @return string
	 */
	public function get_help_url() {
		return $this->help_url;
	}

	/**
	 * Check dependencies at module level. That method checks compatibilities of dependencies that needed by this module.
	 * May be dispatches an notice on Sparks dashboard.
	 *
	 * @return bool
	 */
	protected function check_dependencies() {
		if ( empty( $this->dependencies ) ) {
			return true;
		}

		$status = true;

		foreach ( $this->dependencies as $dependency ) {
			$dependency = $dependency::get_instance();
			if ( ! $dependency->has_activated() ) {
				$this->dependency_errors[] = sprintf(
					/* translators: %1$s: Module name %2$s: Dependency Name %3$s Dependency Type %4$s Module Name */
					esc_html__( '%1$s module needs %2$s (%3$s) to run, otherwise the %4$s module does not work.', 'sparks-for-woocommerce' ),
					$this->get_name(),
					$dependency->get_name(),
					$dependency->get_type_label(),
					$this->get_name()
				);
				if ( true === $status ) {
					$status = false;
				}
			}
		}

		return $status;
	}

	/**
	 * Has dashboard config or not.
	 *
	 * @return bool
	 */
	public function has_dashboard_config() {
		return $this->has_dashboard_config;
	}

	/**
	 * Get dependency errors
	 *
	 * @return array
	 */
	public function get_dependency_errors() {
		return apply_filters( 'sparks_module_dependency_errors', $this->dependency_errors, $this->get_slug() );
	}

	/**
	 * Get setting prefix of the module. That prefix is generally used for to define second prefix of the option keys.
	 *
	 * @return string
	 */
	public function get_setting_prefix() {
		return $this->setting_prefix;
	}

	/**
	 * Can the module managed on dashboard?
	 *
	 * @return bool
	 */
	public function can_be_managed_on_dashboard() {
		return $this->manage_on_dashboard;
	}

	/**
	 * Get module slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->module_slug;
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	abstract public function get_dashboard_description();

	/**
	 * Get admin config URL
	 * 
	 * @return string
	 */
	public function get_admin_config_url() {
		return '';
	}

	/**
	 * Get option_key that specifies the status of the module.
	 *
	 * @return string
	 */
	public function get_status_option_key() {
		return $this->get_option_key( self::ENABLE_MODULE_OPTION );
	}

	/**
	 * Get module status (enabled/disabled)
	 *
	 * @return bool
	 */
	public function get_status() {
		return $this->get_setting( self::ENABLE_MODULE_OPTION, $this->default_status );
	}

	/**
	 * Transforms non prefixed option key to a WP friendly option key.
	 *
	 * @param  string $setting_key Setting key that does not contains module and plugin prefixes.
	 * @return string prefixed option_key can be used to communicate with wp_options.
	 */
	protected function get_option_key( $setting_key ) {
		return sprintf( '%s_%s_%s', self::OPTION_PLUGIN_PREFIX, $this->setting_prefix, $setting_key );
	}

	/**
	 * Get module setting value
	 *
	 * @param  string $setting_key (does not contains main prefix and module prefix.).
	 * @param  mixed  $default_value Default value of the setting.
	 * @return mixed
	 */
	public function get_setting( $setting_key, $default_value ) {
		$default_value = apply_filters( 'sparks_setting_default_value', $default_value, $setting_key );
		return get_option( $this->get_option_key( $setting_key ), $default_value );
	}

	/**
	 * Update setting
	 *
	 * @param  string $setting_key (does not contains main prefix and module prefix.).
	 * @param  mixed  $value The value.
	 * @return bool
	 */
	public function update_setting( $setting_key, $value ) {
		return update_option( $this->get_option_key( $setting_key ), $value );
	}

	/**
	 * Register settings, if the module has not any settings, leave empty the function body.
	 *
	 * @return void
	 */
	public function register_settings() {
		$this->register_setting(
			static::ENABLE_MODULE_OPTION,
			[
				'type'         => 'boolean',
				'show_in_rest' => true,
				'default'      => $this->default_status,
			]
		);
	}

	/**
	 * Force define a register_dynamic_styles function.
	 *
	 * @return void
	 */
	abstract public function register_dynamic_styles();

	/**
	 * Sparks Register Setting
	 * Wrapper for register_setting() of WP.
	 *
	 * @param  string $setting_key non prefixed setting key.
	 * @param  array  $args (optional) args that will be passed into register_setting call.
	 */
	protected function register_setting( $setting_key, $args = [] ) {
		if ( isset( $args['default'] ) ) {
			$args['default'] = apply_filters( 'sparks_setting_default_value', $args['default'], $setting_key );
		}

		register_setting( self::SETTING_GROUP, $this->get_option_key( $setting_key ), $args );
	}

	/**
	 * Return a Style instance.

	 * @return Style
	 */
	public function style() {
		return ( new Style( $this ) );
	}
}
