<?php
/**
 * Style
 *
 * @package Codeinwp\Sparks\Modules\Core
 */
namespace Codeinwp\Sparks\Modules\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Dynamic_Styles;

/**
 * Class Style
 *
 * Prepares dynamic styles and enqueue that to global Sparks Dynamic Styles.
 */
class Style {
	/**
	 * Module
	 *
	 * @var \Codeinwp\Sparks\Modules\Base_Module
	 */
	private $module;

	/**
	 * Constructor
	 *
	 * @param  \Codeinwp\Sparks\Modules\Base_Module $module Represents the owner Module of the style.
	 * @return void
	 */
	public function __construct( $module ) {
		$this->module = $module;
	}

	/**
	 * Push a Dynamic Style Group.
	 *
	 * @param  string                                             $css_selector full css selector.
	 * @param  array<string, array{key: string, default: string}> $css_rules that contains option keys that keeps the dynamic values.
	 * @return void
	 */
	public function add( $css_selector, $css_rules ) {
		/**
		 * CSS Rules
		 *
		 * @var array<string, string>
		 */
		$css_rules = array_map( [ $this, 'transform_to_css_val' ], $css_rules );

		Dynamic_Styles::get_instance()->push(
			$css_selector,
			$css_rules
		);
	}

	/**
	 * Process the css rules and transform it to a css readable value.
	 *
	 * @param  array{key: string, default: string} $rule $css_rules that contains option keys that keeps the dynamic values.
	 * @return string css friendly value.
	 */
	public function transform_to_css_val( $rule ) {
		return $this->module->get_setting( $rule['key'], $rule['default'] );
	}

	/**
	 * Enqueue Admin CSS.
	 * 
	 * @return void
	 */
	public static function enqueue_general_admin_css( $inline_style = '' ) {
		$dependencies = include_once SPARKS_WC_PATH . 'includes/assets/build/tw.asset.php';
		sparks_enqueue_style( 'spk-admin-style', SPARKS_WC_URL . 'includes/assets/build/tw.css', [], $dependencies['version'] );

		if ( ! empty( $inline_style ) ) {
			wp_add_inline_style( 'spk-admin-style', $inline_style );
		}
	}
}
