<?php
/**
 * Base class for jobs.
 *
 * @package Codeinwp\Sparks\Migrations\Neve\Jobs
 */
namespace Codeinwp\Sparks\Migrations\Neve\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Base
 */
abstract class Base {
	const KEY = 'EXAMPLE';

	/**
	 * Represents the skipped status of the job.
	 * Has been skipped or not.
	 *
	 * @var bool
	 */
	private $skipped = false;

	/**
	 * Skipped reason of the job.
	 *
	 * @var string
	 */
	private $skipped_reason = '';

	/**
	 * Constructors
	 *
	 * @throws \Exception If KEY class constant is missing.
	 *
	 * @return void
	 */
	public function __construct() {
		if ( static::KEY === 'EXAMPLE' ) {
			/* translators: %s: Class name of missing key */
			throw new \Exception( sprintf( esc_html__( 'Missing KEY constant on the %s', 'sparks-for-woocommerce' ), get_class( $this ) ) );
		}
	}

	/**
	 * Abstract function for run the job.
	 *
	 * @return bool
	 */
	abstract public function run();

	/**
	 * If the job is skipped for a reason, mark as skipped.
	 *
	 * @param  string $message Skipping reason.
	 * @return void
	 */
	protected function mark_skipped( $message ) {
		$this->skipped        = true;
		$this->skipped_reason = $message;
	}

	/**
	 * Has been skipped or not.
	 *
	 * @return bool
	 */
	public function skipped() {
		return true === $this->skipped;
	}

	/**
	 * Get skipped reason.
	 *
	 * @return string
	 */
	public function get_skipped_reason() {
		return $this->skipped_reason;
	}
}
