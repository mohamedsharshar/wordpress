<?php
/**
 * Class provides option for comparison table module.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Codeinwp\Sparks\Modules\Comparison_Table\Fields;
use Codeinwp\Sparks\Modules\Comparison_Table\View\Table;

/**
 * Fields Class for Comparison Table functions.
 */
class Block_Renderer {
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		// Load assets for block editor
		add_action( 'enqueue_block_editor_assets', array( $this, 'load_assets' ) );
	}

	/**
	 * Get attributes from options
	 */
	public static function get_attr_from_options() {
		$default_fields = wp_json_encode( array_keys( ( ( new Fields() )->get_fields() ) ) );

		$ct             = sparks()->module( 'comparison_table' );
		$default_colors = sparks_current_theme()->comparison_table()->default_colors();

		return array(
			'listingType' => $ct->get_setting( $ct::PRODUCT_LISTING_TYPE, 'column' ),
			'altRow'      => $ct->get_setting( $ct::STRIPED_TABLE_ENABLED, false ),
			'fields'      => $ct->get_setting( $ct::FIELDS, $default_fields ),
			'rowColor'    => $ct->get_setting( $ct::ROWS_BG_COLOR, $default_colors->get( 'table_rows_bg' ) ),
			'headerColor' => $ct->get_setting( $ct::HEADER_TEXT_COLOR, $default_colors->get( 'table_header_text' ) ),
			'textColor'   => $ct->get_setting( $ct::TEXT_COLOR, $default_colors->get( 'table_text' ) ),
			'borderColor' => $ct->get_setting( $ct::BORDERS_COLOR, $default_colors->get( 'table_border' ) ),
			'altRowColor' => $ct->get_setting( $ct::STRIPED_BG_COLOR, $default_colors->get( 'table_striped_bg' ) ),
		);
	}

	/**
	 * Load needed assets
	 *
	 * @return void
	 */
	public function load_assets() {
		if ( defined( 'NEVE_ASSETS_URL' ) ) {
			wp_enqueue_style( 'neve-woocommerce', NEVE_ASSETS_URL . 'css/woocommerce.min.css', array( 'woocommerce-general' ), apply_filters( 'neve_version_filter', NEVE_VERSION ) );
		}

		$table = sparks()->module( 'comparison_table' );

		if ( method_exists( $table, 'enqueue_assets' ) ) {
			$table->enqueue_assets();
		}

		// load common assets
		sparks_enqueue_style( 'sparks-style' );
		sparks_enqueue_script( 'sparks-script' );
	}

	/**
	 * Block render function for server-side.
	 *
	 * This method will pe passed to the render_callback parameter and it will output
	 * the server side output of the block.
	 *
	 * @param array $attributes Block attrs.
	 *
	 * @return mixed|string
	 */
	public function render( $attributes ) {
		$this->load_assets();
		$defaults = self::get_attr_from_options();

		$attributes = array_merge( $defaults, array_filter( $attributes ) );
		ob_start();
		$table = new Table();

		$_GET['is_woo_comparison_block'] = true;
		$_GET['product_ids']             = isset( $attributes['products'] ) ? wp_parse_id_list( $attributes['products'] ) : array();
		$id                              = isset( $attributes['id'] ) ? sanitize_html_class( $attributes['id'] ) : 'sparks-woo-comparison-' . wp_rand( 10, 100 );
		
		$table->render_comparison_products_table( false, true, $attributes );
		
		$class = 'sp-ct-enabled sp-ct-comparison-table-content woocommerce';

		$css = '#' . esc_attr( $id ) . ' .sp-ct-container {';

		if ( ! empty( $attributes['rowColor'] ) ) {
			$css .= '--bgcolor: ' . esc_attr( $attributes['rowColor'] ) . ';';
		}

		if ( ! empty( $attributes['headerColor'] ) ) {
			$css .= '--headercolor: ' . esc_attr( $attributes['headerColor'] ) . ';';
		}

		if ( ! empty( $attributes['textColor'] ) ) {
			$css .= '--color: ' . esc_attr( $attributes['textColor'] ) . ';';
		}

		if ( ! empty( $attributes['borderColor'] ) ) {
			$css .= '--bordercolor: ' . esc_attr( $attributes['borderColor'] ) . ';';
		}

		if ( ! empty( $attributes['altRowColor'] ) ) {
			$css .= '--alternatebg: ' . esc_attr( $attributes['altRowColor'] ) . ';';
		}

		$css .= '}';

		wp_add_inline_style( 'sp-ct-style', $css );

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'id'    => $id,
				'class' => $class,
			)
		);

		$output  = '<div ' . $wrapper_attributes . '>';
		$output .= ob_get_contents();
		$output .= '</div>';
		ob_end_clean();

		$allowed_tags = wp_kses_allowed_html( 'post' );

		$allowed_tags['input'] = array(
			'type'  => array(),
			'class' => array(),
		);

		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_checkbox_input_in_kses' ) );

		return wp_kses_post( $output );
	}

	/**
	 * Summary of allow_checkbox_input_in_kses
	 * 
	 * @param array $allowed_tags Allowed tags.
	 * 
	 * @return array Allowed tags.
	 */
	public function allow_checkbox_input_in_kses( $allowed_tags ) {
		$allowed_tags['input'] = array(
			'type'  => array(),
			'class' => array(),
		);
		return $allowed_tags;
	}
}
