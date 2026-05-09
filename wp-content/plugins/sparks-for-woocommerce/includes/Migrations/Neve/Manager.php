<?php
/**
 * Responsible from the migration of the data of the Neve Pro WooCommerce Booster into Sparks.
 *
 * @package Codeinwp\Sparks\Migrations\Neve
 */
namespace Codeinwp\Sparks\Migrations\Neve;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Migrations\Neve\Theme_Mods;
use Codeinwp\Sparks\Migrations\Neve\Options;
use Codeinwp\Sparks\Migrations\Neve\Jobs;

/**
 * Class Manager
 *
 * Important Maintain Note: The migration module should be run as first priority (before the other modules are initialized) to prevent saving of any Sparks option on dashboard etc. Otherwise, migration module overriddes the Sparks options.
 */
class Manager {
	/**
	 * Option key of the option that keeps migration status
	 *
	 * @var string
	 */
	const MIGRATION_STATUS_OPTION = 'sparks_neve_migrated';

	const MIGRATION_NEEDED_INDICATOR_OPTION_KEY = 'theme_mods_neve';

	const MIGRATIONS = [
		Theme_Mods::class,
		Options::class,
		Jobs::class,
	];

	/**
	 * Needs migration or not.
	 *
	 * If the WP has Neve data on options table, we can try migration.
	 *
	 * Indicator of migration need is the option that has theme_mods_neve key, due to that's only indicator that located in there since v1.0.0
	 *
	 * @return bool
	 */
	private function needs_migration() {
		return ! empty( get_option( self::MIGRATION_NEEDED_INDICATOR_OPTION_KEY, [] ) );
	}

	/**
	 * Run the migrations if that hasn't been done before.
	 *
	 * @return void
	 */
	public function run() {
		if ( $this->migrated() ) {
			return;
		}

		if ( ! $this->needs_migration() ) {
			return;
		}

		$has_error = false;

		foreach ( self::MIGRATIONS as $migration ) {
			$status = ( new $migration() )->run();

			if ( true !== $status ) {
				$has_error = true;
			}
		}

		if ( true !== $has_error ) {
			$this->mark_migration_completed();
		}
	}

	/**
	 * Mark the migration as completed.
	 *
	 * @return bool
	 */
	private function mark_migration_completed() {
		return update_option( self::MIGRATION_STATUS_OPTION, true, 'no' );
	}

	/**
	 * Have the all options and theme mods migrated from Neve to Sparks?
	 *
	 * @return bool
	 */
	private function migrated() {
		return get_option( self::MIGRATION_STATUS_OPTION, false );
	}
}
