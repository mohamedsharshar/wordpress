<?php
/**
 * ...
 *
 * @package Codeinwp\Sparks\Modules\Comparison_Table\View
 */

namespace Codeinwp\Sparks\Modules\Comparison_Table\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Modules\Comparison_Table\Product_Fields;
use Codeinwp\Sparks\Modules\Comparison_Table\Data_Store;
use Codeinwp\Sparks\Modules\Comparison_Table\Functions;
use Codeinwp\Sparks\Modules\Comparison_Table\Related_Products;
use Codeinwp\Sparks\Modules\Comparison_Table\Options;
use Codeinwp\Sparks\Core\Traits\Conditional_Asset_Loading_Utilities;
use Codeinwp\Sparks\Modules\Comparison_Table\Fields\Abstract_Field;
use WC_Product;

/**
 * ...
 */
class Table {
	use Conditional_Asset_Loading_Utilities;

	const ENABLE_RELATED_PRODUCTS         = 'enable_related_products';
	const IS_ALTERNATING_BG_COLOR_ENABLED = 'enable_striped_table';
	const PRODUCT_LISTING_TYPE            = 'product_listing_type';
	const SHORTCODE_TAG                   = 'sparks_comparison_table';

	const ENABLE_HIDE_IDENTICAL = 'enable_hide_identical';

	/**
	 * Is related product for comparison table enabled?
	 *
	 * @var bool
	 */
	private $is_related_products_enabled;

	/**
	 * Is hide identical enabled?
	 *
	 * @var bool
	 */
	private $enable_hide_identical;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->is_related_products_enabled = sparks()->module( 'comparison_table' )->get_setting( self::ENABLE_RELATED_PRODUCTS, false );
		$this->enable_hide_identical       = sparks()->module( 'comparison_table' )->get_setting( self::ENABLE_HIDE_IDENTICAL, false );
		add_shortcode( self::SHORTCODE_TAG, array( $this, 'show_choosen_page_content' ) );
		add_filter( 'body_class', array( $this, 'add_comparison_table_class_to_body_classes' ) );
	}

	/**
	 * Show chosen page content
	 *
	 * @return string
	 */
	public function show_choosen_page_content() {
		global $post;

		if ( Options::get_comparison_table_page_id() !== $post->ID ) {
			return '';
		}

		ob_start();
		$this->render_comparison_products_table();
		return ob_get_clean();
	}

	/**
	 * Output Remove Product Button
	 *
	 * @param  WC_Product $product WC_Product instance.
	 */
	private function render_remove_button( $product ) {
		printf( '<button value="%d" class="sp-ct-remove-product" type="button">×</button>', esc_html( (string) $product->get_id() ) );
	}

	/**
	 * Render for no products in the comparison table.
	 */
	private function render_no_product_in_the_table() {
		$shop_page_id = get_option( 'woocommerce_shop_page_id' );
		?>

		<div style="margin-bottom: 20px;"><?php esc_html_e( 'There are no products in your comparison list.', 'sparks-for-woocommerce' ); ?></div>

		<div>
			<a href="<?php echo esc_url( get_permalink( $shop_page_id ) ); ?>" class="woocommerce-button wc-forward button sparks-btn sparks-btn-back">
				<?php esc_html_e( 'Back to Shop', 'sparks-for-woocommerce' ); ?>
			</a>
		</div>

		<?php
	}

	/**
	 * Render the comparison table.
	 *
	 * @param bool $related render with related products.
	 */
	public function render_comparison_products_table( $related = true, $block = false, $attrs = array() ) {

		$comparison_table = new Data_Store();
		$total_product    = $comparison_table->get_total_product();
		$products         = $comparison_table->get_products();

		$mods_product_listing_type = $block ? $attrs['listingType'] : sparks()->module( 'comparison_table' )->get_setting( self::PRODUCT_LISTING_TYPE, 'column' );

		if ( ! $total_product ) {
			$this->render_no_product_in_the_table();
			return;
		}

		if ( function_exists( 'wc_print_notices' ) ) {
			wc_print_notices();
		}

		echo '<div class="ct-byline-container">';
		if ( ! $block ) {
			echo '<span class="ct-byline">';
			/* translators: %s: product count */
			printf( esc_html__( 'You have %d product in the list', 'sparks-for-woocommerce' ), esc_html( (string) $total_product ) );
			echo '</span>';
		}

		if ( $this->enable_hide_identical ) {
			echo '<label>' . esc_html__( 'Hide identical values', 'sparks-for-woocommerce' );
			echo '<input type="checkbox" class="sp-ct-filter-identical"/>';
			echo '</label>';
		}


		echo '</div>';

		$fields = ( new Product_Fields( $comparison_table ) )->get_available_fields( $attrs );

		// Filter out fields that are empty on all products.
		$all_empty_fields = $this->get_fields_empty_status( $fields, $products );
		$fields           = array_filter(
			$fields,
			function ( $field ) use ( $all_empty_fields ) {
				return ! isset( $all_empty_fields[ $field->get_key() ] ) || ! $all_empty_fields[ $field->get_key() ];
			}
		);

		$table_classes = array( 'sp-ct' );

		if ( $this->should_the_table_be_wide( $fields, $mods_product_listing_type ) ) {
			$table_classes[] = 'sp-ct-wide';
		}

		// define class for table orientation
		if ( 'row' === $mods_product_listing_type ) {
			$table_classes[] = 'sp-ct-layout-row';
		} else {
			$table_classes[] = 'sp-ct-layout-column';

			$table_classes[] = sprintf( 'sp-ct-%s-product', $total_product );
		}

		$mods_alternative_row = $block ? $attrs['altRow'] : sparks()->module( 'comparison_table' )->get_setting( self::IS_ALTERNATING_BG_COLOR_ENABLED, 0 );

		// define class for striped table.
		if ( $mods_alternative_row ) {
			$table_classes[] = 'sp-ct-striped-table';
		}
		?>
		<div class="sp-ct-container">
			<?php
			$this->render_table( $mods_product_listing_type, $table_classes, $fields, $products, $block );

			if ( $this->is_related_products_enabled && $related ) {
				$this->render_related_products();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Function that decide that table should be wide
	 *
	 * @param  array  $fields result of get_available_fields() function.
	 * @param  string $product_listing_type row or column.
	 * @return bool
	 */
	private function should_the_table_be_wide( $fields, $product_listing_type ) {
		if ( 'row' === $product_listing_type && $this->is_available_fields_contains( $fields, 'Description' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function that checks if the given field in the comparison table exists.
	 *
	 * @param  array  $fields result of get_available_fields() function.
	 * @param  string $searchable_field_class class name to search for.
	 * @return bool
	 */
	private function is_available_fields_contains( $fields, $searchable_field_class ) {
		foreach ( $fields as $field ) {
			if ( is_a( $field, 'Codeinwp\Sparks\Modules\Comparison_Table\Fields\\' . $searchable_field_class ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * View Table.
	 *
	 * @param  string $selected_tableview_option declares table orientation ( show products as row OR show products as column ).
	 * @param  array  $table_classes contains classes of the table tag.
	 * @param  array  $fields that fields of the comparison table.
	 * @param  array  $products that products of comparison table.
	 * @return void
	 */
	private function render_table( $selected_tableview_option, $table_classes, $fields, $products, $block ) {
		?>
		<div class="sp-ct-table-wrap">
			<table table-layout="fixed" class='<?php echo esc_attr( implode( ' ', $table_classes ) ); ?>'>
				<?php
				if ( 'column' === $selected_tableview_option ) {
					$this->render_table_body_column( $fields, $products, $block );
				} else {
					$this->render_table_body_row( $fields, $products, $block );
				}
				?>
			</table>
		</div>
		<?php
	}

	/**
	 * Column Based View Table ( Shows products as column )
	 *
	 * @param  Abstract_Field[] $fields that fields of the comparison table.
	 * @param  array            $products that products of comparison table.
	 * @return void
	 */
	private function render_table_body_column( $fields, $products, $block = false ) {
		$identical_values = $this->get_identical_fields_values( $fields, $products );

		?>
		<thead>
			<tr>
				<th></th>
				<?php foreach ( $products as $product ) { ?>
					<td class="sp-ct-image-container">
						<div>
							<?php
							if ( ! $block ) {
								$this->render_remove_button( $product );
							}
							?>
							<?php $this->render_product_image( $product ); ?>
						</div>
					</td>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php 
			foreach ( $fields as $field ) {
				$classes = '';

				if ( isset( $identical_values[ $field->get_key() ] ) && $identical_values[ $field->get_key() ] ) {
					$classes = 'sp-ct-identical';
				}

				?>
				<tr class="<?php echo esc_attr( $classes ); ?>">
					<th><?php echo esc_html( ( $field->hide_table_title ) ? '' : $field->get_label() ); ?></th>
					<?php foreach ( $products as $product ) { ?>
						<td><?php $field->render( $product ); ?></td>
					<?php } ?>
				</tr>
			<?php } ?>
		</tbody>
		<?php
	}

	/**
	 * Row Based View Table ( Shows products as row )
	 *
	 * @param  array $fields that fields of the comparison table.
	 * @param  array $products that products of comparison table.
	 * @param  bool  $block to check if this call is being made from a Gutenberg block.
	 * @return void
	 */
	private function render_table_body_row( $fields, $products, $block = false ) {
		$identical_values = $this->get_identical_fields_values( $fields, $products );

		?>
		<thead>
			<tr>
				<th colspan="<?php echo $block ? 1 : 2; ?>"></th>
				<?php 
				foreach ( $fields as $field ) {
					$classes = '';

					if ( isset( $identical_values[ $field->get_key() ] ) && $identical_values[ $field->get_key() ] ) {
						$classes = 'sp-ct-identical';
					}
					?>
					<th class="<?php echo esc_attr( $classes ); ?>"><?php echo esc_html( ( $field->hide_table_title ) ? '' : $field->get_label() ); ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $products as $product ) { ?>
				<tr>
					<?php if ( ! $block ) { ?>
						<th><?php $this->render_remove_button( $product ); ?></th>
					<?php } ?>
					<td class="sp-ct-image-container"><?php $this->render_product_image( $product ); ?></td>
					<?php 
					foreach ( $fields as $field ) {
						$classes = '';

						if ( isset( $identical_values[ $field->get_key() ] ) && $identical_values[ $field->get_key() ] ) {
							$classes = 'sp-ct-identical';
						}
						?>
						<td class="<?php echo esc_attr( $classes ); ?>"><?php $field->render( $product ); ?></td>
					<?php } ?>
				</tr>
			<?php } ?>
		</tbody>
		<?php
	}

	/**
	 * Print output of the given product's image.
	 *
	 * @param  WC_Product $product instance of WC_Product.
	 * @return void
	 */
	private function render_product_image( $product ) {
		?>
		<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><img src="<?php echo esc_url( Functions::get_product_image_url( $product ) ); ?>" /></a>
		<?php
	}

	/**
	 * View Related Products of Comparison Table Products
	 */
	private function render_related_products() {
		// related product ids of comparison table products.
		$ct_datastore = new Data_Store();

		$related_product_ids = ( new Related_Products( $ct_datastore ) )->get_related_product_ids();

		$args = array(
			'columns'          => 4,
			'related_products' => array_map( 'wc_get_product', $related_product_ids ),
		);

		// set global loop values.
		wc_set_loop_prop( 'name', 'related' );
		wc_set_loop_prop( 'columns', (string) $args['columns'] );

		?>
		<div id="sp-ct-related-products">
			<?php
			wc_get_template( 'single-product/related.php', $args );
			?>
		</div>
		<?php
	}

	/**
	 * Add body classes if needed.
	 *
	 * @param array $classes that existing classes of the body.
	 * @return array
	 */
	public function add_comparison_table_class_to_body_classes( $classes ) {
		// If the current page contains any products
		if ( $this->current_page_has_loop_products() ) {
			$classes[] = 'sp-ct-enabled';
		}

		// If current page has a Comparison Table render or Comparison Table Block
		if ( Options::current_page_has_ct_page() ) {
			$classes[] = 'woocommerce';
			$classes[] = 'sp-ct-enabled';
			$classes[] = 'sp-ct-comparison-table-content';
		}

		return $classes;
	}

	/**
	 * Get the empty status of the fields.
	 *
	 * @param Abstract_Field[] $fields the fields to check.
	 * @param \WC_Product[]    $products the products to check.
	 * @return array
	 */
	private function get_fields_empty_status( $fields, $products ) {
		$all_empty_fields = [];

		foreach ( $fields as $field ) {
			$all_empty = array_reduce(
				$products,
				function ( $carry, $product ) use ( $field ) {
					return $carry && $field->is_empty( $product );
				},
				true
			);

			$all_empty_fields[ $field->get_key() ] = $all_empty;
		}

		return $all_empty_fields;
	}

	/**
	 * Get the identical status of the fields.
	 *
	 * @param Abstract_Field[] $fields the fields to check.
	 * @param \WC_Product[]    $products the products to check.
	 * @return array <string, boolean>
	 */
	private function get_identical_fields_values( $fields, $products ) {
		$excluded_fields = array( 'add_to_cart_button' );

		$identical_fields_values = [];

		foreach ( $fields as $field ) {
			$identical_fields_values[ $field->get_key() ] = array_reduce(
				$products,
				function ( $carry, $product ) use ( $field, $products ) {
					return $carry && $field->get_display_value( $product ) === $field->get_display_value( $products[0] );
				},
				true
			);
		}

		return array_filter(
			$identical_fields_values,
			function ( $value, $key ) use ( $excluded_fields ) {
				return $value && ! in_array( $key, $excluded_fields, true );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}
}
