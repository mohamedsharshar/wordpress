<?php
/**
 * Migration Jobs that does stuff for Neve to Sparks migration.
 *
 * @package Codeinwp\Sparks\Migrations\Neve
 */
namespace Codeinwp\Sparks\Migrations\Neve;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Migrations\Neve\Jobs\Base;
use Codeinwp\Sparks\Migrations\Neve\Jobs\CT_Add_Shortcode;

/**
 * Class Jobs
 */
class Jobs {
	/**
	 * List of the jobs that required for Neve->Sparks migration.
	 */
	const JOB_LIST = [
		CT_Add_Shortcode::class,
	];

	/**
	 * Key of the option that keeps data of completed migration jobs.
	 */
	const COMPLETED_JOBS_OPTION = 'sparks_neve_completed_migration_jobs';

	/**
	 * Jobs map
	 *
	 * @var array<string, Base> key of array represents job keys, value of array represents job instances.
	 */
	private $jobs_map = [];

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->parse_job_keys();
	}

	/**
	 * Run uncompleted jobs
	 *
	 * @return bool
	 */
	public function run() {
		$has_error = false;

		foreach ( $this->get_uncompleted_jobs() as $job_key ) {
			$status = $this->run_job( $job_key );

			if ( true !== $status ) {
				$has_error = true;
			}
		}

		return ! $has_error;
	}

	/**
	 * Run a specific job.
	 *
	 * @param  string $job_key Unique identifier key of the Neve Migration Job.
	 * @return bool
	 */
	private function run_job( $job_key ) {
		$job = $this->get_job( $job_key );

		$status = $job->run();

		if ( true === $status ) {
			$this->mark_as_completed( $job_key );
			return true;
		}

		if ( ( false === $status ) && $job->skipped() ) {
			$this->mark_as_completed( $job_key, true, $job->get_skipped_reason() );
			return true;
		}

		return false;
	}

	/**
	 * Get job instance
	 *
	 * @param  string $key Unique identifier key of the Neve Migration Job.
	 * @return Base
	 */
	private function get_job( $key ) {
		return $this->jobs_map[ $key ];
	}

	/**
	 * Fill $this->jobs_map property with job instances and job keys.
	 *
	 * @return void
	 */
	private function parse_job_keys() {
		foreach ( self::JOB_LIST as $job_class ) {
			$this->jobs_map[ $job_class::KEY ] = new $job_class();
		}
	}

	/**
	 * Get job keys of the uncompleted jobs.
	 *
	 * @return string[]
	 */
	private function get_uncompleted_jobs() {
		return array_diff( array_keys( $this->jobs_map ), array_keys( $this->get_completed_jobs() ) );
	}

	/**
	 * Get details of completed jobs.
	 *
	 * @return array<string, array{completed_at: positive-int}> Array keys represents the job name.
	 */
	private function get_completed_jobs() {
		return get_option( self::COMPLETED_JOBS_OPTION, [] );
	}

	/**
	 * Mark the job as completed.
	 *
	 * @param  string $name Name of the job.
	 * @param  bool   $skipped Skipped or not.
	 * @param  string $skipped_reason If the job is skipped, skipped reason can be defined.
	 * @return bool
	 */
	private function mark_as_completed( $name, $skipped = false, $skipped_reason = '' ) {
		$completed_jobs          = $this->get_completed_jobs();
		$completed_jobs[ $name ] = [
			'migrated_at' => time(),
			'skipped'     => $skipped,
		];

		if ( $skipped ) {
			$completed_jobs[ $name ]['skipped_reason'] = $skipped_reason;
		}

		return update_option( self::COMPLETED_JOBS_OPTION, $completed_jobs, 'no' );
	}
}
