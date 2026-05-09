<?php
/**
 * Responsible from the migrations of the Neve/Neve Pro theme mods into Sparks.
 *
 * @package Codeinwp\Sparks\Migrations\Neve
 */
namespace Codeinwp\Sparks\Migrations\Neve;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
/**
 * Class Theme_Mods
 */
class Theme_Mods {
	/**
	 * Represents the option that keeps migrated theme mods from neve/neve pro into here.
	 *
	 * @var string
	 */
	const MIGRATED_THEME_MODS_OPTION = 'sparks_neve_migrated_theme_mods';

	/**
	 * TODO: The following just sample, add all of the options that will be migrated, then re-check the array items.
	 * Map of the theme mods that will be migrated.
	 *
	 * @var array<string, string> keys represents the theme mod that will be migrated, values represents the new option key.
	 */
	const MAP_THEME_MOD_MIGRATION = [
		'neve_comparison_table_number_of_products_limit'  => 'sparks_ct_product_limit',
		'neve_comparison_table_category_restrict_type'    => 'sparks_ct_cat_restrict_type',
		'neve_comparison_table_restricted_categories'     => 'sparks_ct_restricted_cats',
		'neve_comparison_table_open_popup_product_limit'  => 'sparks_ct_product_limit_for_modal',
		'neve_comparison_table_page_id'                   => 'woocommerce_sparks_comparison_table_page_id',
		'neve_comparison_table_compare_checkbox_position' => 'sparks_ct_compare_checkbox_position',
		'neve_comparison_table_enable_alternating_row_bg_color' => 'sparks_ct_enable_striped_table',
		'neve_comparison_table_product_listing_type'      => 'sparks_ct_product_listing_type',
		'neve_comparison_table_view_type'                 => 'sparks_ct_table_view_type',
		'neve_comparison_table_sticky_bar_button_type'    => 'sparks_ct_sticky_bar_button_type',
		'neve_comparison_table_enable_related_products'   => 'sparks_ct_enable_related_products',
		'neve_comparison_table_fields'                    => 'sparks_ct_fields',
		'neve_quick_view'                                 => 'sparks_qv_btn_position',
		'neve_catalog_vs'                                 => 'sparks_vs_show_in_catalog',
		'neve_comparison_table_header_text_color'         => 'sparks_ct_header_text_color',
		'neve_comparison_table_borders_color'             => 'sparks_ct_border_color',
		'neve_comparison_table_text_color'                => 'sparks_ct_text_color',
		'neve_comparison_table_rows_background_color'     => 'sparks_ct_rows_bg_color',
		'neve_comparison_table_alternate_row_bg_color'    => 'sparks_ct_stripe_color',
		'neve_comparison_table_sticky_bar_background_color' => 'sparks_ct_sticky_bar_bg_color',
		'neve_comparison_table_sticky_bar_text_color'     => 'sparks_ct_sticky_bar_text_color',
		'neve_wish_list'                                  => 'sparks_wl_btn_position',
	];

	/**
	 * Run the migrations if that hasn't been done before.
	 *
	 * @return bool
	 */
	public function run() {
		$this->migrate_theme_mods();

		return $this->is_migration_successful();
	}

	/**
	 * Get missing (not migrated) theme mods
	 *
	 * @return string[]
	 */
	private function get_missed_theme_mods() {
		return array_diff( array_keys( self::MAP_THEME_MOD_MIGRATION ), array_keys( $this->get_migrated_theme_mods() ) );
	}

	/**
	 * Has any missed/not migrated theme mod
	 *
	 * @return bool
	 */
	private function has_missed_theme_mod() {
		return ! empty( $this->get_missed_theme_mods() );
	}

	/**
	 * Has the migration completed successfully?
	 *
	 * @return bool
	 */
	private function is_migration_successful() {
		return ! $this->has_missed_theme_mod();
	}

	/**
	 * Get list of theme mods.
	 *
	 * @return array<string, array{migrated_at: positive-int, is_null: string}> Array keys represents the theme mod key.
	 */
	private function get_migrated_theme_mods() {
		return get_option( self::MIGRATED_THEME_MODS_OPTION, [] );
	}

	/**
	 * Check if the given theme mod has been migrated or not.
	 *
	 * @param  string $theme_mod_key Neve Theme Mod key.
	 * @return bool
	 */
	private function has_theme_mod_migrated( $theme_mod_key ) {
		return isset( $this->get_migrated_theme_mods()[ $theme_mod_key ] );
	}

	/**
	 * Mark the theme mod as migrated.
	 *
	 * @param  string $key Option key or Theme mod key where in the Neve/Neve Pro side.
	 * @param bool   $is_null Should be true, if the value of the option/theme mod could not found on Neve/Neve Pro side.
	 * @return bool
	 */
	private function mark_as_migrated( $key, $is_null ) {
		$migrated_theme_mod_keys         = $this->get_migrated_theme_mods();
		$migrated_theme_mod_keys[ $key ] = [
			'migrated_at' => time(),
			'is_null'     => $is_null,
		];
		return update_option( self::MIGRATED_THEME_MODS_OPTION, $migrated_theme_mod_keys, 'no' );
	}

	/**
	 * Migrate theme mods to options.
	 *
	 * @return void
	 */
	private function migrate_theme_mods() {
		foreach ( self::MAP_THEME_MOD_MIGRATION as $theme_mod_key => $sparks_option_key ) {
			if ( $this->has_theme_mod_migrated( $theme_mod_key ) ) {
				continue;
			}

			$old_value = get_theme_mod( $theme_mod_key, ( new WP_Error( 'theme_mod_not_found', esc_html__( 'Theme mod that will be migrated could not find.', 'sparks-for-woocommerce' ) ) ) );

			// if old value doesn't exists, skip.
			if ( is_wp_error( $old_value ) ) {
				$this->mark_as_migrated( $theme_mod_key, true );
				continue;
			}

			if ( add_option( $sparks_option_key, $old_value, '', 'no' ) ) {
				$this->mark_as_migrated( $theme_mod_key, false );
			}
		}
	}
}
