<?php
/**
 * Responsible from the migrations of the Neve/Neve Pro options into Sparks.
 *
 * @package Codeinwp\Sparks\Migrations\Neve
 */
namespace Codeinwp\Sparks\Migrations\Neve;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
/**
 * Class Options
 */
class Options {
	/**
	 * Represents the option that keeps migrated options from neve/neve pro into here.
	 *
	 * @var string
	 */
	const MIGRATED_OPTION_KEYS_OPTION = 'sparks_neve_migrated_options';

	/**
	 * TODO: The following just sample, add all of the options that will be migrated, then re-check the array items
	 * Map of the options that will be migrated from Neve/Neve Pro to Sparks.
	 *
	 * @var array<string, string> keys represents the neve option key that will be migrated, values represents the new(Sparks) option key.
	 */
	const MAP_OPTIONS_MIGRATION = [
		'neve_woocommerce_enable_unregistered_voting' => 'sparks_apr_enable_unregistered_voting',
		'nv_pro_enable_variation_swatches'            => 'sparks_vs_enabled',
		'neve_pt_default_tabs'                        => 'sparks_pt_default_tabs',
		'nv_pro_enable_tab_manager'                   => 'sparks_pt_enabled',
		'nv_pro_enable_custom_thank_you'              => 'sparks_cty_enabled',
		'woocommerce_neve_comparison_table_page_id'   => 'woocommerce_sparks_comparison_table_page_id',
		'nv_pro_enable_comparison_table'              => 'sparks_ct_enabled',
		'neve_woocommerce_enable_review_voting'       => 'sparks_apr_enable_review_voting',
		'neve_woocommerce_enable_review_images'       => 'sparks_apr_enable_review_images',
		'neve_woocommerce_enable_anonymize_reviewer'  => 'sparks_apr_enable_anonymize_reviewer',
		'neve_woocommerce_enable_hide_avatar'         => 'sparks_apr_enable_hide_avatar',
		'neve_woocommerce_enable_review_title'        => 'sparks_apr_enable_review_title',
		'nv_pro_enable_advanced_product_review'       => 'sparks_apr_enabled',
		'nv_pro_enable_cart_notices'                  => 'sparks_cn_enabled',
	];

	/**
	 * Run the migrations if that hasn't been done before.
	 *
	 * @return bool
	 */
	public function run() {
		$this->migrate_options();

		return $this->is_migration_successful();
	}

	/**
	 * Get list of missing (not migrated) options
	 *
	 * @return string[]
	 */
	private function get_missed_options() {
		return array_diff( array_keys( self::MAP_OPTIONS_MIGRATION ), array_keys( $this->get_migrated_options() ) );
	}

	/**
	 * Has any missed/not migrated option
	 *
	 * @return bool
	 */
	private function has_missed_option() {
		return ! empty( $this->get_missed_options() );
	}

	/**
	 * Has the migration completed successfully?
	 *
	 * @return bool
	 */
	private function is_migration_successful() {
		return ! $this->has_missed_option();
	}

	/**
	 * Get list of migrated option keys.
	 *
	 * @return array<string, array{migrated_at: positive-int, is_null: string}> Array keys represents the option key.
	 */
	private function get_migrated_options() {
		return get_option( self::MIGRATED_OPTION_KEYS_OPTION, [] );
	}

	/**
	 * Check if the given option key has been migrated or not.
	 *
	 * @param  string $option_key Option key that located in Neve/Neve Pro side.
	 * @return bool
	 */
	private function has_option_migrated( $option_key ) {
		return isset( $this->get_migrated_options()[ $option_key ] );
	}

	/**
	 * Mark the option as migrated.
	 *
	 * @param  string $key Option key where in the Neve/Neve Pro side.
	 * @param bool   $is_null Should be true, if the value of the option could not found on Neve/Neve Pro side.
	 * @return bool
	 */
	private function mark_as_migrated( $key, $is_null ) {
		$migrated_option_keys         = $this->get_migrated_options();
		$migrated_option_keys[ $key ] = [
			'migrated_at' => time(),
			'is_null'     => $is_null,
		];
		return update_option( self::MIGRATED_OPTION_KEYS_OPTION, $migrated_option_keys, 'no' );
	}

	/**
	 * Migrate options from Neve/Neve Pro into Sparks.
	 *
	 * @return void
	 */
	private function migrate_options() {
		foreach ( self::MAP_OPTIONS_MIGRATION as $neve_option_key => $sparks_option_key ) {
			if ( $this->has_option_migrated( $neve_option_key ) ) {
				continue;
			}

			$old_value = get_option( $neve_option_key, ( new WP_Error( 'option_not_found', esc_html__( 'Option key that will be migrated could not find.', 'sparks-for-woocommerce' ) ) ) );

			// if old value doesn't exists, skip.
			if ( is_wp_error( $old_value ) ) {
				$this->mark_as_migrated( $neve_option_key, true );
				continue;
			}

			if ( add_option( $sparks_option_key, $old_value, '', 'no' ) ) {
				$this->mark_as_migrated( $neve_option_key, false );
			}
		}
	}
}
