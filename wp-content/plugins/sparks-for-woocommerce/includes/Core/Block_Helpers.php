<?php
/**
 * Block template helper utilities.
 *
 * @package Codeinwp\Sparks\Core
 */

namespace Codeinwp\Sparks\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Helpers
 *
 * Helper class for block template functionality.
 *
 * @package Codeinwp\Sparks\Core
 */
class Block_Helpers {
	const TEMPLATE_SINGLE_PRODUCT  = 'single-product';
	const TEMPLATE_ARCHIVE_PRODUCT = 'archive-product';
	const TEMPLATE_CART            = 'cart';
	const TEMPLATE_CHECKOUT        = 'checkout';

	/**
	 * All supported template names.
	 *
	 * @var array
	 */
	private static $templates = array(
		self::TEMPLATE_SINGLE_PRODUCT,
		self::TEMPLATE_ARCHIVE_PRODUCT,
		self::TEMPLATE_CART,
		self::TEMPLATE_CHECKOUT,
	);

	/**
	 * Templates that are blockified by default in WooCommerce 7.9+.
	 *
	 * @var array
	 */
	const BLOCKIFIED_TEMPLATES = array(
		'archive-product',
		'product-search-results',
		'single-product',
		'taxonomy-product_attribute',
		'taxonomy-product_cat',
		'taxonomy-product_tag',
	);

	/**
	 * Static cache for template block usage checks.
	 *
	 * @var array
	 */
	private static $use_blocks_cache = array();

	/**
	 * Check if minimum requirements are met for block templates.
	 *
	 * @return bool True if WooCommerce 7.9+ is active and site uses a block theme.
	 */
	private static function meets_minimum_requirements(): bool {
		if ( ! function_exists( 'WC' ) || version_compare( WC()->version, '7.9.0', '<' ) ) {
			return false;
		}

		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Check if content is a valid block template for the given template name.
	 *
	 * @param string $content       The template content to check.
	 * @param string $template_name The template name being checked.
	 *
	 * @return bool True if content contains proper blocks for the template type.
	 */
	private static function is_block_template_content( string $content, string $template_name ): bool {
		switch ( $template_name ) {
			case self::TEMPLATE_CART:
				return has_block( 'woocommerce/cart', $content );
			case self::TEMPLATE_CHECKOUT:
				return has_block( 'woocommerce/checkout', $content );
			default:
				return ! has_block( 'woocommerce/legacy-template', $content );
		}
	}

	/**
	 * Get block templates for a given template name.
	 *
	 * @param string $template_name The template name to retrieve.
	 *
	 * @return array Block templates matching the template name.
	 */
	private static function get_template_blocks( string $template_name ): array {
		return get_block_templates( array( 'slug__in' => array( $template_name ) ) );
	}

	/**
	 * Check if patterns within content are valid block templates.
	 *
	 * @param string $content       The content containing patterns to check.
	 * @param string $template_name The template name being validated.
	 *
	 * @return bool True if all patterns are valid block templates, false otherwise.
	 */
	private static function check_patterns_for_blocks( string $content, string $template_name ): bool {
		if ( ! has_block( 'core/pattern', $content ) ) {
			return true;
		}

		$blocks   = parse_blocks( $content );
		$registry = \WP_Block_Patterns_Registry::get_instance();

		foreach ( $blocks as $block ) {
			if ( 'core/pattern' !== $block['blockName'] ) {
				continue;
			}

			$slug = $block['attrs']['slug'] ?? '';

			if ( empty( $slug ) || ! $registry->is_registered( $slug ) ) {
				continue;
			}

			$pattern = $registry->get_registered( $slug );
			if ( ! self::is_block_template_content( $pattern['content'], $template_name ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if template should fallback to blockified status.
	 *
	 * @param string $template_name The template name to check.
	 *
	 * @return bool True if template is in the blockified templates list.
	 */
	private static function should_fallback_to_blockified( string $template_name ): bool {
		return in_array( $template_name, self::BLOCKIFIED_TEMPLATES, true );
	}

	/**
	 * Check if a specific WooCommerce template is using block-based rendering.
	 *
	 * This method determines whether a WooCommerce template page uses the modern
	 * block-based template system rather than legacy PHP templates. Results are
	 * cached statically for performance.
	 *
	 * Requires WooCommerce 7.9.0+ and WordPress 5.9+ with a block theme active.
	 *
	 * @param string $template_name Template identifier. Use class constants:
	 *                              TEMPLATE_SINGLE_PRODUCT, TEMPLATE_ARCHIVE_PRODUCT,
	 *                              TEMPLATE_CART, or TEMPLATE_CHECKOUT.
	 *
	 * @return bool True if the template uses blocks, false otherwise.
	 */
	public static function using_block_template_in( string $template_name ): bool {
		// Validate template name.
		if ( ! in_array( $template_name, self::$templates, true ) ) {
			return false;
		}

		// Return cached result if available.
		if ( isset( self::$use_blocks_cache[ $template_name ] ) ) {
			return self::$use_blocks_cache[ $template_name ];
		}

		// Check minimum requirements (WooCommerce 7.9+, block theme).
		if ( ! self::meets_minimum_requirements() ) {
			self::$use_blocks_cache[ $template_name ] = false;
			return false;
		}

		// Retrieve block templates for this template name.
		$templates = self::get_template_blocks( $template_name );

		if ( ! empty( $templates[0] ) ) {
			// Template found - validate it uses blocks.
			$content     = $templates[0]->content;
			$uses_blocks = self::is_block_template_content( $content, $template_name )
						&& self::check_patterns_for_blocks( $content, $template_name );

			self::$use_blocks_cache[ $template_name ] = $uses_blocks;
		} else {
			// Template not found - check if it's a default blockified template.
			self::$use_blocks_cache[ $template_name ] = self::should_fallback_to_blockified( $template_name );
		}

		return self::$use_blocks_cache[ $template_name ];
	}
}
