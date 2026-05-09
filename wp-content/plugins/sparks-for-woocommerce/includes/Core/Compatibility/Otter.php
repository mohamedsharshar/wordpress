<?php
/**
 * Otter Compatibility
 *
 * @package Codeinwp\Sparks\Core\Compatibility
 */
namespace Codeinwp\Sparks\Core\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Codeinwp\Sparks\Core\Compatibility\Type\Plugin;

/**
 * Class Otter
 */
class Otter extends Base implements Plugin {
	/**
	 * If this compatibility is required by Sparks as mandatory or not.
	 *
	 * @var bool
	 */
	protected $needed_for_core = false;

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		add_action( 'wp_head', [ $this, 'add_styles' ], 10 );
	}

	/**
	 * Get human readable name of the compatibility.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Otter - Gutenberg Blocks';
	}

	/**
	 * If the interlocutor of the compatibility has been enabled or not.
	 * An example: If the interlocutor is a plugin, that method checks if the plugin has been activated or not.
	 *
	 * @return bool
	 */
	public function has_activated() {
		return defined( 'OTTER_BLOCKS_VERSION' );
	}

	/**
	 * If the Sparks is compatible with current Otter setup or not.
	 *
	 * @return bool
	 */
	public function check() {
		return true; // for now, there is no minimum version of Otter required.
	}

	/**
	 * Dynamic styles regarding Sparks&Otter compatibility
	 *
	 * @return void
	 */
	public function add_styles() {
		?>
		<style>.woocommerce-checkout .glide__arrow {width: auto;}</style>
		<?php
	}
}
