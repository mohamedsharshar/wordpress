<?php

namespace WpLandingKit\Framework\Ajax;

use WpLandingKit\Framework\Traits\ClassNameAsIdentifier;
use WpLandingKit\Framework\Traits\NonceCreationAndVerification;

/**
 * Class AjaxHandlerBase
 * @package WpLandingKit\Framework\Ajax
 *
 * A base class for convenient creation of AJAX handlers. Extend this class to create your own AJAX handler and
 * initialise appropriately.
 */
abstract class AjaxHandlerBase {

	use ClassNameAsIdentifier;

	use NonceCreationAndVerification {
		verify_nonce as protected _verify_nonce;
	}

	/**
	 * @var string The AJAX action name
	 */
	protected $action;

	/**
	 * @var bool Whether or not to look for and verify a nonce when handling requests.
	 */
	protected $use_nonce = true;

	/**
	 * @var bool Whether or not to print the inline script containing JSON encoded variables.
	 */
	protected $print_inline_script = true;

	/**
	 * @var null|string If defined, this will be used as the id attribute of the inline script containing the
	 *                  JSON-encoded variables. If not defined, the AJAX action will be used.
	 */
	protected $inline_script_id = null;

	/**
	 * @var string|mixed    Set the name of the method that is hooked to wp_ajax_{$action} for handling AJAX requests
	 *                      made by authenticated users. If set to anything other than a string – e.g; FALSE, NULL, … –
	 *                      the priv handler won't be hooked and this AJAX action will not handle requests made by
	 *                      authenticated users.
	 */
	protected $priv_handler_method_name = 'handle_priv';

	/**
	 * @var string|mixed    Set the name of the method that is hooked to wp_ajax_nopriv_{$action} for handling AJAX
	 *                      requests made by non-authenticated users. If set to anything other than a string – e.g;
	 *                      FALSE, NULL, … – the priv handler won't be hooked and this AJAX action will not handle
	 *                      requests made by non-authenticated users.
	 */
	protected $nopriv_handler_method_name = 'handle_nopriv';

	/**
	 * Offers control over how the class name is converted to an identifier.
	 *
	 * @see \WpLandingKit\Framework\Traits\ClassNameAsIdentifier for more information.
	 *
	 * @var bool
	 */
	protected $class_name_has_consecutive_ucase_chars = false;

	/**
	 * Initialise the AJAX endpoint by hooking both priv and no priv handlers where configured. This is the only method
	 * that must be called in order to get the implementation running.
	 */
	public function register() {
		$action = $this->get_action();

		if ( is_string( $this->priv_handler_method_name ) ) {
			add_action( "wp_ajax_{$action}", [ $this, '_handle' ] );
		}

		if ( is_string( $this->nopriv_handler_method_name ) ) {
			add_action( "wp_ajax_nopriv_{$action}", [ $this, '_handle' ] );
		}

		if ( $this->print_inline_script ) {
			add_action( 'wp_head', [ $this, '_print_inline_script' ] );
		}
	}

	/**
	 * The full URL for this AJAX action. If nonces are being used, the nonce is also added to the URL.
	 *
	 * @param bool $with_nonce Whether or not to include the nonce parameter in the URL
	 *
	 * @return string
	 */
	public function get_url( $with_nonce = true ) {
		$url = add_query_arg( 'action', $this->get_action(), admin_url( 'admin-ajax.php' ) );

		if ( $with_nonce and $this->use_nonce ) {
			$url = $this->add_nonce_to_url( $url );
		}

		return $url;
	}

	/**
	 * If no $action property is set on a child class, use the fully-qualified class name to generate a snake-cased
	 * action for the implementation.
	 *
	 * @return string
	 */
	public function get_action() {
		if ( ! $this->action ) {
			$this->action = $this->get_class_name_as_id();
		}

		return $this->action;
	}

	/**
	 * The hooked handler method.
	 */
	public function _handle() {
		$this->verify_nonce();

		if ( $handler = $this->get_handler_method_name() ) {
			$this->$handler();
			die();
		}

		wp_die( 'AJAX hooked handler method called directly outside of appropriate hook.', '', [ 'response' => 400 ] );
	}

	/**
	 * Hooked method that prints the inline script tag containing JSON-decoded variables for JavaScript consumption.
	 */
	public function _print_inline_script() {
		echo $this->get_inline_script() . "\n";
	}

	/**
	 * An array of variables that will be encoded for JavaScript consumption in the DOM.
	 *
	 * @return array
	 */
	protected function get_script_vars() {
		$vars = [
			'ajax_url' => $this->get_url( false ),
			'action' => $this->get_action(),
			'nonce' => $this->get_nonce(),
		];

		return array_merge( $vars, $this->get_custom_script_vars() );
	}

	/**
	 * An associative array of variables to merge into the script vars for encoding and consumption in the DOM. These
	 * are merged atop of the base vars so if you need to override any you can do so be defining them in this array.
	 *
	 * @return array
	 */
	protected function get_custom_script_vars() {
		return [];
	}

	protected function get_inline_script() {
		$data = json_encode( $this->get_script_vars(), JSON_UNESCAPED_SLASHES );
		$id = $this->get_script_id();

		return sprintf( '<script type="application/json" id="%s">%s</script>', esc_attr( $id ), $data );
	}

	protected function get_script_id() {
		return empty( $this->inline_script_id )
			? $this->get_action()
			: $this->inline_script_id;
	}

	protected function verify_nonce() {
		if ( $this->use_nonce and ! $this->_verify_nonce() ) {
			wp_die( 'Nonce invalid' );
		}
	}

	/**
	 * Determines which handler method should be invoked for this request.
	 *
	 * @return string|null The method name on success or NULL on failure
	 */
	protected function get_handler_method_name() {
		$action = $this->get_action();

		if ( doing_action( "wp_ajax_{$action}" ) ) {
			return $this->priv_handler_method_name;

		} elseif ( doing_action( "wp_ajax_nopriv_{$action}" ) ) {
			return $this->nopriv_handler_method_name;
		}

		return null;
	}

	protected function handle_priv() {
		wp_die( 'No endpoint handler defined for this action and context.', '', [ 'response' => 400 ] );
	}

	protected function handle_nopriv() {
		wp_die( 'No endpoint handler defined for this action and context.', '', [ 'response' => 400 ] );
	}

}