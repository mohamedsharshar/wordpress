<?php
/**
 * Dynamic Styles
 *
 * @package Codeinwp\Sparks\Core
 */
namespace Codeinwp\Sparks\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Theme;

/**
 * Class Dynamic_Styles
 */
final class Dynamic_Styles {
	/**
	 * Instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Current Theme
	 *
	 * @var object
	 */
	private $current_theme;

	/**
	 * Subscribers
	 *
	 * @var array
	 */
	private static $subscribers = [];

	const LEFT_CURLY_BRACKET  = '{';
	const RIGHT_CURLY_BRACKET = '}';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		$this->current_theme = sparks_current_theme();

		$this->handle_theme_custom_styles();
		$this->handle_theme_css_vars();
	}

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Handle theme custom styles.
	 *
	 * @return void
	 */
	private function handle_theme_css_vars() {
		$css_vars = $this->current_theme->get_css_vars();

		$this->push( ':root', $css_vars );
	}

	/**
	 * Handle theme custom styles.
	 *
	 * @return void
	 */
	private function handle_theme_custom_styles() {
		$theme_custom_css = $this->current_theme->get_custom_styles();

		foreach ( $theme_custom_css as $selector => $rules ) {
			$this->push( $selector, $rules );
		}
	}

	/**
	 * Push a style
	 *
	 * @param  string                $selector CSS Selectors.
	 * @param  array<string, string> $rules keeps css property and value.
	 * @return void
	 */
	public function push( $selector, $rules ) {
		self::$subscribers[] = [
			'rules'    => $rules,
			'selector' => $selector,
		];
	}

	/**
	 * Build styles and minify it.
	 *
	 * @return string
	 */
	private function build_css() {
		return array_reduce(
			self::$subscribers,
			function( $css, $subscriber ) {
				$css .= $subscriber['selector'] . self::LEFT_CURLY_BRACKET;

				foreach ( $subscriber['rules'] as $css_var => $css_value ) {
					$css .= sprintf( '%s:%s;', $css_var, $css_value );
				}

				$css .= self::RIGHT_CURLY_BRACKET;

				return $css;
			},
			''
		);
	}

	/**
	 * Render dynamic styles.
	 *
	 * @return void
	 */
	public function render() {
		add_action(
			'admin_head',
			array( $this, 'load_admin_dynamic_css' )
		);

		// TODO: improvement: just dump the dynamic styles are used in the admin side.
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'load_frontend_dynamic_css' )
		);
	}

	/**
	 * Load admin dynamic CSS
	 *
	 * @return void
	 */
	public function load_admin_dynamic_css() {
		printf( '<style>%s</style>', esc_html( $this->build_css() ) );
	}

	/**
	 * Load frontend dynamic CSS
	 *
	 * @return void
	 */
	public function load_frontend_dynamic_css() {
		if ( ! wp_style_is( 'sparks-style', 'enqueued' ) ) {
			/**
			 * Filter to print dynamic styles as standalone (without a enqueued head stylesheet)
			 * That is used for if the 'sparks-style' style is not enqueued by Common module on Codeinwp\Sparks\Modules\Common::enqueue_core_assets
			 *
			 * @since 1.1.4
			 */
			if ( apply_filters( 'sparks_print_dynamic_styles_standalone', false ) ) {
				return;
			}

			add_action( 'wp_head', array( $this, 'print_dynamic_css_without_stylesheet' ) );
			return;
		}

		wp_add_inline_style( 'sparks-style', $this->build_css() );
	}

	/**
	 * Print dynamic CSS without a enqueued head stylesheet
	 *
	 * @return void
	 */
	public function print_dynamic_css_without_stylesheet() {
		printf( '<style>%s</style>', esc_html( $this->build_css() ) );
	}
}
