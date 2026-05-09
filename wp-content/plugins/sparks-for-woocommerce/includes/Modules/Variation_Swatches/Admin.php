<?php
/**
 *  Class that add variation swatches admin part.
 *
 * @package Codeinwp\Sparks\Modules\Variation_Swatches
 */

namespace Codeinwp\Sparks\Modules\Variation_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Variation_Swatches
 */
class Admin {

	/**
	 * Taxonomy attributes.
	 *
	 * @var array
	 */
	private $attr_taxonomies = array();

	/**
	 * Init function.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'define_admin_hooks' ) );
	}

	/**
	 * Admin hooks.
	 */
	public function define_admin_hooks() {
		add_filter( 'product_attributes_type_selector', array( $this, 'add_attribute_types' ) );
		$attribute_taxonomies  = wc_get_attribute_taxonomies();
		$this->attr_taxonomies = $attribute_taxonomies;
		foreach ( $attribute_taxonomies as $tax ) {
			add_action( 'pa_' . $tax->attribute_name . '_add_form_fields', array( $this, 'add_attribute_fields' ) );
			add_action( 'pa_' . $tax->attribute_name . '_edit_form_fields', array( $this, 'edit_attribute_fields' ), 10, 2 );
			add_filter( 'manage_edit-pa_' . $tax->attribute_name . '_columns', array( $this, 'add_attribute_column' ) );
			add_filter( 'manage_pa_' . $tax->attribute_name . '_custom_column', array( $this, 'add_attribute_column_content' ), 10, 3 );
		}
		add_action( 'created_term', array( $this, 'save_term_meta' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_term_meta' ), 10, 3 );

		add_action( 'woocommerce_product_option_terms', array( $this, 'render_product_option_terms' ), 20, 2 );


		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add types of the variation swatches.
	 *
	 * @param array $types Variation types.
	 *
	 * @return array
	 */
	public function add_attribute_types( $types ) {
		$more_types = array(
			'color' => __( 'Color', 'sparks-for-woocommerce' ),
			'image' => __( 'Image', 'sparks-for-woocommerce' ),
			'label' => __( 'Label', 'sparks-for-woocommerce' ),
		);

		$types = array_merge( $types, $more_types );
		return $types;
	}

	/**
	 * Add the root div for custom fields that are added in react.
	 */
	public function add_attribute_fields( $taxonomy ) {
		$attribute_type = $this->get_attribute_type( $taxonomy );
		if ( false === $attribute_type ) {
			return false;
		}

		$settings = array(
			'attribute_type' => $attribute_type,
			'input_name'     => 'product_' . $taxonomy,
		);

		$this->render_custom_attributes( $settings );
		return true;
	}

	/**
	 * Render the custom controls when editing a swatch type term.
	 *
	 * @param \WP_Term $term Term object.
	 * @param string   $taxonomy Taxonomy name.
	 */
	public function edit_attribute_fields( $term, $taxonomy ) {
		$settings = array(
			'attribute_type' => $this->get_attribute_type( $taxonomy ),
			'term_value'     => get_term_meta( $term->term_id, 'product_' . $taxonomy, true ),
			'is_edit'        => true,
			'input_name'     => 'product_' . $taxonomy,
		);

		$this->render_custom_attributes( $settings );
	}

	/**
	 * Render custom controls based on the swatch type.
	 *
	 * @param array $settings Custom attribute settings.
	 *
	 * @return bool
	 */
	private function render_custom_attributes( $settings ) {

		if ( ! array_key_exists( 'attribute_type', $settings ) ) {
			return false;
		}

		if ( ! array_key_exists( 'input_name', $settings ) ) {
			return false;
		}

		$attribute_type      = $settings['attribute_type'];
		$is_edit             = array_key_exists( 'is_edit', $settings ) ? $settings['is_edit'] : false;
		$custom_field_markup = $this->get_custom_field_markup_wrapper( $settings );

		$label = $this->get_label_by_type( $attribute_type );
		if ( $is_edit ) {
			echo '<tr class="form-field gbl-attr-terms gbl-attr-terms-edit" >';
			echo '<th>' . esc_html( $label ) . '</th>';
			echo '<td>';
			echo wp_kses_post( $custom_field_markup );
			echo '</td>';
			echo '</tr>';
		} else {
			echo '<div class="form-field term-' . esc_attr( $attribute_type ) . '-wrap">';
			echo '<label>' . esc_html( $label ) . '</label>';
			echo wp_kses_post( $custom_field_markup );
			echo '</div>';
		}
		wp_nonce_field( 'add_swatch', 'swatches_nonce' );
		return true;
	}

	/**
	 * Get custom field markup wrapper.
	 *
	 * @param array $settings Field settings.
	 *
	 * @return string
	 */
	private function get_custom_field_markup_wrapper( $settings ) {
		$input_name     = $settings['input_name'];
		$attribute_type = $settings['attribute_type'];

		$markup = '<div id="sp-swatches-custom-fields" data-name="' . esc_attr( $input_name ) . '" data-type="' . esc_attr( $attribute_type ) . '" ';
		if ( array_key_exists( 'term_value', $settings ) ) {
			$markup .= 'data-value="' . esc_attr( $settings['term_value'] ) . '"';
		}
		$markup .= '></div>';
		return $markup;
	}

	/**
	 * Get label by input type.
	 *
	 * @param string $type Input type.
	 *
	 * @return string
	 */
	private function get_label_by_type( $type ) {
		switch ( $type ) {
			case 'color':
				return __( 'Color', 'sparks-for-woocommerce' );
			case 'image':
				return __( 'Image', 'sparks-for-woocommerce' );
			case 'label':
				return __( 'Label', 'sparks-for-woocommerce' );
			default:
				return '';
		}
	}

	/**
	 * Add the preview column for terms.
	 *
	 * @param array $columns Current columns.
	 *
	 * @return array
	 */
	public function add_attribute_column( $columns ) {
		return $this->array_insert_after( $columns, 'cb', array( 'nv_preview' => '' ) );
	}

	/**
	 * Insert a value or key/value pair after a specific key in an array.  If key doesn't exist, value is appended
	 * to the end of the array.
	 *
	 * @param array  $array Array where needed to insert.
	 * @param string $key Key after to insert.
	 * @param array  $new What to inset.
	 *
	 * @return array
	 */
	private function array_insert_after( array $array, $key, array $new ) {
		$keys  = array_keys( $array );
		$index = array_search( $key, $keys, true );
		$pos   = false === $index ? count( $array ) : $index + 1;

		return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
	}

	/**
	 * Render preview for terms.
	 *
	 * @param string $columns Current columns output.
	 * @param string $column  Current column.
	 * @param int    $term_id Term id.
	 *
	 * @return string
	 */
	public function add_attribute_column_content( $columns, $column, $term_id ) {
		if ( 'nv_preview' !== $column ) {
			return $columns;
		}

		if ( ! isset( $_REQUEST['taxonomy'] ) ) {
			return $columns;
		}

		$taxonomy    = sanitize_text_field( $_REQUEST['taxonomy'] );
		$attr_type   = $this->get_attribute_type( $taxonomy );
		$value       = get_term_meta( $term_id, 'product_' . $taxonomy, true );
		$has_value   = ! empty( $value );
		$empty_value = $has_value ? '' : 'sp-vswatch-empty';
		switch ( $attr_type ) {
			case 'color':
				$columns .= '<div class="sp-vswatch-preview-wrap color round ' . esc_attr( $empty_value ) . '">';
				if ( $has_value ) {
					$columns .= '<span class="sp-vswatch-color-preview" style="background-color:' . esc_attr( $value ) . ';"></span>';
				}
				$columns .= '</div>';
				break;
			case 'image':
				$columns .= '<div class="sp-vswatch-preview-wrap image round ' . esc_attr( $empty_value ) . '">';
				if ( $has_value ) {
					$columns .= '<img class="sp-vswatch-image-preview" src="' . esc_url( $value ) . '"/>';
				}
				$columns .= '</div>';
				break;
			case 'label':
				$term_name = get_term( $term_id )->name;
				$value     = empty( $value ) ? $term_name : $value;
				$columns  .= '<div class="sp-vswatch-preview-wrap label"><label class="sp-vswatch-label-preview"> ' . wp_kses_post( $value ) . ' </label>';
				break;
		}

		return $columns;
	}

	/**
	 * Save swatch term meta.
	 *
	 * @param int    $term_id Term ID being saved.
	 * @param int    $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function save_term_meta( $term_id, $tt_id, $taxonomy ) {

		if ( ! isset( $_POST['swatches_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['swatches_nonce'] ), 'add_swatch' ) ) {
			return false;
		}

		if ( isset( $_POST[ 'product_' . $taxonomy ] ) ) {
			$attr_type = $this->get_attribute_type( $taxonomy );
			$value     = wp_kses_post( $_POST[ 'product_' . $taxonomy ] );
			$value     = $this->sanitize_term_value( $value, $attr_type );
			update_term_meta( $term_id, 'product_' . $taxonomy, $value );
		}
	}

	/**
	 * Sanitize term value by type.
	 *
	 * @param string $value Term value.
	 * @param string $type Term type.
	 *
	 * @return string
	 */
	public function sanitize_term_value( $value, $type ) {
		switch ( $type ) {
			case 'color':
				if ( preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $value ) ) {
					return $value;
				}
				return '';
			case 'image':
				$attachment_id = $this->attachment_url_to_postid( $value );
				if ( ! empty( $attachment_id ) ) {
					$image_source = wp_get_attachment_image_src( $attachment_id );
					if ( is_array( $image_source ) ) {
						return $image_source[0];
					}
				}
				return '';
			case 'label':
				return wp_kses_post( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Moved into here from \Neve_Pro\Traits\Core. TODO: consider to move that to a common place.
	 * Wrapper for attachment_url_to_postid.
	 */
	public function attachment_url_to_postid( $url ) {
		return function_exists( 'wpcom_vip_attachment_url_to_postid' )
			? wpcom_vip_attachment_url_to_postid( $url )
			: attachment_url_to_postid( $url ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid
	}

	/**
	 * Get taxonomy's type attribute.
	 *
	 * @param string $taxonomy Taxonomy.
	 *
	 * @return mixed
	 */
	public function get_attribute_type( $taxonomy ) {
		foreach ( $this->attr_taxonomies as $tax ) {
			if ( 'pa_' . $tax->attribute_name === $taxonomy ) {
				return( $tax->attribute_type );
			}
		}
		return false;
	}

	/**
	 * Display the select field in attributes type.
	 *
	 * @param object $attribute_taxonomy Attribute taxonomy.
	 * @param int    $i Index.
	 *
	 * @return bool
	 */
	public function render_product_option_terms( $attribute_taxonomy, $i ) {
		if ( 'select' === $attribute_taxonomy->attribute_type ) {
			return false;
		}

		global $post;

		$product_id = $post->ID;

		if ( is_null( $product_id ) && isset( $_POST['security'] ) && isset( $_POST['action'] ) && 'woocommerce_save_attributes' === $_POST['action'] && wp_verify_nonce( sanitize_key( $_POST['security'] ), 'save-attributes' ) && isset( $_POST['post_id'] ) ) {
			$product_id = absint( $_POST['post_id'] );
		}

		$taxonomy = wc_attribute_taxonomy_name( $attribute_taxonomy->attribute_name );

		echo '<select multiple="multiple" data-placeholder="' . esc_attr__( 'Select terms', 'sparks-for-woocommerce' ) . '" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[' . esc_attr( (string) $i ) . '][]">';
		$args      = array(
			'orderby'    => 'name',
			'hide_empty' => 0,
		);
		$all_terms = get_terms( $taxonomy, apply_filters( 'woocommerce_product_attribute_terms', $args ) );  // @phpstan-ignore-line TODO:phpstan refactor the function params. (2n arg is deprecated on wp core and moved into first argument))
		if ( ! empty( $all_terms ) ) {
			foreach ( $all_terms as $term ) {
				$selected = wc_selected( (int) has_term( absint( $term->term_id ), $taxonomy, $product_id ), 1 );
				echo '<option value="' . esc_attr( $term->term_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
			}
		}
		echo '</select>';

		echo '<button class="button plus select_all_attributes">';
		esc_html_e( 'Select all', 'sparks-for-woocommerce' );
		echo '</button>';

		echo '<button class="button minus select_no_attributes">';
		esc_html_e( 'Select none', 'sparks-for-woocommerce' );
		echo '</button>';

		echo '<button class="button fr plus add_new_attribute">';
		esc_html_e( 'Add new', 'sparks-for-woocommerce' );
		echo '</button>';

		return true;
	}

	/**
	 * Enqueue admin script.
	 *
	 * @param string $current_page Current page.
	 *
	 * @return bool
	 */
	public function enqueue_admin_scripts( $current_page ) {
		if ( 'edit-tags.php' !== $current_page && 'term.php' !== $current_page ) {
			return false;
		}

		if ( ! isset( get_current_screen()->post_type ) || get_current_screen()->post_type !== 'product' ) {
			return false;
		}

		// wp-components script is not loaded by WooCommerce on versions lower than 5.0 so we need to load it for the color picker
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '4.9.2', '<=' ) ) {
			sparks_enqueue_style( 'wp-components' );
		}

		wp_enqueue_media();

		$asset_file = include SPARKS_WC_PATH . 'includes/assets/build/variation_swatches/components.asset.php';
		wp_register_script(
			'sp-vswatches-script',
			SPARKS_WC_URL . 'includes/assets/build/variation_swatches/components.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sp-variation-swatches-script', 'sparks-for-woocommerce' );
		}

		wp_register_style(
			'sp-vswatches-editor-style',
			SPARKS_WC_URL . 'includes/assets/variation_swatches/css/editor-style.min.css',
			array(),
			SPARKS_WC_VERSION
		);
		wp_style_add_data( 'sp-vswatches-editor-style', 'rtl', 'replace' );
		wp_style_add_data( 'sp-vswatches-editor-style', 'suffix', '.min' );

		sparks_enqueue_script( 'sp-vswatches-script' );
		sparks_enqueue_style( 'sp-vswatches-editor-style' );

		sparks_enqueue_script( 'sp-vswatches-field-reset', SPARKS_WC_URL . 'includes/assets/variation_swatches/js/reset-fields.js', array(), SPARKS_WC_VERSION, true );
		return true;
	}
}
