<?php
/**
 * Name field of the comparison table.
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table\Fields;
 */
namespace Codeinwp\Sparks\Modules\Comparison_Table\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field that provides add to cart the product for products where in comparison table.
 */
class Add_To_Cart_Button extends Abstract_Field {
	/**
	 * Set label
	 */
	public function set_label() {
		$this->label = esc_html__( 'Add to Cart Button', 'sparks-for-woocommerce' );
	}

	/**
	 * When the value is true, the heading does not shows.
	 *
	 * @var bool
	 */
	public $hide_table_title = true;

	/**
	 * Get field value of the product.
	 *
	 * @param  \WC_Product $current_product is product instance.
	 * @return void
	 */
	public function render( \WC_Product $current_product ) {
		$product_ids = isset( $_GET['product_ids'] ) ? esc_attr( implode( ',', wp_parse_id_list( $_GET['product_ids'] ) ) ) : '';

		$button_url = add_query_arg(
			array(
				'product_ids' => $product_ids,
				'add-to-cart' => $current_product->get_id(),
			),
			\Codeinwp\Sparks\Modules\Comparison_Table\Main::get_comparison_link()
		);

		if ( isset( $_GET['comparison-table-iframe'] ) ) {
			$button_url = add_query_arg(
				array(
					'comparison-table-iframe' => 1,
					'product_ids'             => $product_ids,
					'add-to-cart'             => $current_product->get_id(),
				),
				$button_url
			);
		}

		global $product;
		$product = $current_product;

		// if redirect feature is not enabled, update the url.
		if ( get_option( 'woocommerce_cart_redirect_after_add' ) !== 'yes' ) {
			add_filter(
				'woocommerce_product_add_to_cart_url',
				function( $current_url, $product ) use ( $current_product, $button_url ) {
					if ( $product->get_id() === $current_product->get_id() ) {
						return $button_url;
					}

					return $current_url;
				},
				10,
				2
			);
		}

		add_filter(
			'woocommerce_product_add_to_cart_url',
			function( $current_url, $product ) {
				if ( $product->get_type() !== 'variable' ) {
					return $current_url;
				}

				return get_permalink( $product->get_id() );
			},
			10,
			2
		);

		woocommerce_template_loop_add_to_cart();
	}

	/**
	 * Get the display value of the field for the product.
	 *
	 * @param \WC_Product $product the product instance.
	 * 
	 * @return string
	 */
	public function get_display_value( \WC_Product $product ) {
		return '';
	}
}
