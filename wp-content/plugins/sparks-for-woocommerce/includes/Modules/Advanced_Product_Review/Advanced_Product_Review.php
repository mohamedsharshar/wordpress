<?php
/**
 * Class that controls and adds advanced product review options.
 *
 * @since 3.1.0
 * @package Codeinwp\Sparks\Modules\Advanced_Product_Review
 */
namespace Codeinwp\Sparks\Modules\Advanced_Product_Review;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Comment;
use Codeinwp\Sparks\Modules\Base_Module;

/**
 * Class Advanced_Product_Review
 *
 * @since 3.1.0
 */
class Advanced_Product_Review extends Base_Module {

	const ENABLE_REVIEW_TITLE               = 'enable_review_title';
	const ENABLE_REVIEW_IMAGES              = 'enable_review_images';
	const ENABLE_REVIEW_ANONYMIZE           = 'enable_anonymize_reviewer';
	const ENABLE_HIDE_AVATAR                = 'enable_hide_avatar';
	const ENABLE_REVIEW_VOTING              = 'enable_review_voting';
	const ENABLE_REVIEW_UNREGISTERED_VOTING = 'enable_unregistered_voting';

	/**
	 * Default module activation status
	 *
	 * @var bool
	 */
	protected $default_status = false;

	/**
	 * Define module setting prefix.
	 *
	 * @var string
	 */
	protected $setting_prefix = 'apr';

	/**
	 * Define module slug.
	 *
	 * @var string
	 */
	protected $module_slug = 'advanced_product_review';

	/**
	 * Holds the value for the title checkbox.
	 *
	 * @var boolean $enable_title
	 */
	private $enable_title = false;

	/**
	 * Holds the value for the hide avatar checkbox.
	 *
	 * @var boolean $enable_hide_avatar
	 */
	private $enable_hide_avatar = false;

	/**
	 * Holds the value for anonymize username checkbox.
	 *
	 * @var boolean $enable_anonymize
	 */
	private $enable_anonymize = false;

	/**
	 * Holds the value for attachments checkbox.
	 *
	 * @var boolean $enable_attachments
	 */
	private $enable_attachments = false;

	/**
	 * Specify a limit for the number of attachments allowed
	 *
	 * @var int $attachments_limit;
	 */
	private $attachments_limit = 5;

	/**
	 * Holds the value for review voting checkbox.
	 *
	 * @var boolean $enable_voting
	 */
	private $enable_voting = false;

	/**
	 * Holds the value for review unregistered voting checkbox.
	 *
	 * @var boolean $enable_unregistered_vote
	 */
	private $enable_unregistered_vote = false;

	/**
	 * Help URL
	 *
	 * @var string
	 */
	protected $help_url = 'https://docs.themeisle.com/article/1479-advanced-product-review-in-neve?utm_source=sparks&utm_medium=dashboard&utm_campaign=admin';

	/**
	 * Module name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Advanced Product Reviews', 'sparks-for-woocommerce' );
	}

	/**
	 * Should the module be loaded?
	 *
	 * @return bool
	 */
	public function should_load() {
		return $this->get_status();
	}

	/**
	 * Register class hooks and init options.
	 *
	 * @since 3.1.0
	 */
	public function init() {
		// We use this hook to register additional hooks only for single product page
		add_action( 'wp', array( $this, 'add_hooks' ) );

		add_filter( 'woocommerce_get_settings_products', array( $this, 'woocommerce_settings_products_filter' ), 10, 2 );
		add_filter( 'woocommerce_product_review_comment_form_args', array( $this, 'change_comment_form' ) );

		$this->enable_title = $this->get_setting( self::ENABLE_REVIEW_TITLE, 'no' ) === 'yes';
		if ( $this->enable_title ) {
			add_action( 'woocommerce_review_before_comment_text', array( $this, 'display_comment_title' ) );
			add_action( 'comment_post', array( $this, 'add_review_title_meta' ), 1 );
		}

		$this->enable_hide_avatar = $this->get_setting( self::ENABLE_HIDE_AVATAR, 'no' ) === 'yes';
		if ( $this->enable_hide_avatar ) {
			remove_action( 'woocommerce_review_before', 'woocommerce_review_display_gravatar', 10 );
		}

		$this->enable_anonymize = $this->get_setting( self::ENABLE_REVIEW_ANONYMIZE, 'no' ) === 'yes';
		if ( $this->enable_anonymize ) {
			add_filter( 'get_comment_author', array( $this, 'anonymize_author_name' ), 10, 3 );
		}

		$this->enable_attachments = $this->get_setting( self::ENABLE_REVIEW_IMAGES, 'no' ) === 'yes';
		if ( $this->enable_attachments ) {
			add_action( 'comment_post', array( $this, 'save_attachments' ), 1 );
			add_action( 'woocommerce_review_after_comment_text', array( $this, 'display_attachments' ), 10 );
			add_action( 'wp_footer', array( $this, 'render_modal' ), 100 );
		}

		$this->enable_voting            = $this->get_setting( self::ENABLE_REVIEW_VOTING, 'no' ) === 'yes';
		$this->enable_unregistered_vote = $this->get_setting( self::ENABLE_REVIEW_UNREGISTERED_VOTING, 'no' ) === 'yes';
		if ( $this->enable_voting ) {
			add_action( 'woocommerce_review_after_comment_text', array( $this, 'display_upvote' ), 10 );
			add_action( 'rest_api_init', array( $this, 'register_vote_endpoints' ) );
		}
	}

	/**
	 * Register Dynamic Styles
	 *
	 * @return void
	 */
	public function register_dynamic_styles(){}

	/**
	 * Register endpoint for review voting
	 */
	final public function register_vote_endpoints() {
		register_rest_route(
			SPARKS_WC_REST_NAMESPACE,
			'/products/(?P<product_id>\d+)/vote',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upvote_action' ),
				'permission_callback' => function( \WP_REST_Request $request ) {
					$product_id = $request->get_param( 'product_id' );
					$review_id  = $request->get_param( 'reviewId' );

					if (
						empty( $product_id )
						|| ! isset( $review_id )
						|| 'publish' !== get_post_status( $product_id )
					) {
						return false;
					}

					return is_user_logged_in() || $this->enable_unregistered_vote;
				},
				'args'                => array(
					'product_id' => array(
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Get dashboard description
	 *
	 * @return string
	 */
	public function get_dashboard_description() {
		return esc_html__( 'Allows you to enable an advanced review section, enlarging the basic review options with more capabilities.', 'sparks-for-woocommerce' );
	}

	/**
	 * Register these hooks only for single product pages.
	 *
	 * @since 3.1.0
	 */
	final public function add_hooks() {
		if ( is_product() ) {
			add_action( 'comment_form', array( $this, 'add_review_from_nonce' ), 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		}
	}

	/**
	 * Method to update current user review votes.
	 *
	 * @param int  $user_id The user ID.
	 * @param int  $review_id The review ID.
	 * @param bool $has_voted Flag to specify if user has voted.
	 */
	private function update_voted_status( $user_id, $review_id, $has_voted = true ) {
		if ( empty( $user_id ) ) {
			return;
		}

		$review_votes = get_user_meta( $user_id, 'review_votes', true );
		if ( empty( $review_votes ) ) {
			$review_votes = array();
		}
		$review_votes[ $review_id ] = $has_voted;
		update_user_meta( $user_id, 'review_votes', $review_votes );
	}

	/**
	 * Ajax method to upvote a review.
	 *
	 * @since 3.1.0
	 * @param \WP_REST_Request $request the request.
	 *
	 * @return \WP_REST_Response
	 */
	final public function upvote_action( \WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );
		$review_id  = $request->get_param( 'reviewId' );
		$has_voted  = $request->get_param( 'hasVoted' );

		$current_user = get_current_user_id();
		$review_id    = sanitize_text_field( $review_id );
		$cookie_name  = 'nv_apr_upvote_' . $review_id;

		// if user already voted subtract vote
		if ( ! empty( $has_voted ) ) {
			$upvote_count = get_comment_meta( (int) $review_id, 'review_upvote', true );
			if ( empty( $upvote_count ) ) {
				$upvote_count = 0;
			}
			$upvote_count = (int) $upvote_count - 1;
			if ( $upvote_count < 0 ) {
				$upvote_count = 0;
			}
			$check = update_comment_meta( (int) $review_id, 'review_upvote', $upvote_count );
			$this->update_voted_status( $current_user, (int) $review_id, false );

			$response_array = array(
				'success'     => false,
				'latestCount' => $upvote_count,
			);
			if ( $check ) {
				$response_array['success'] = true;
			}
			return new \WP_REST_Response(
				$response_array,
				200
			);
		}

		$upvote_count = get_comment_meta( (int) $review_id, 'review_upvote', true );
		if ( empty( $upvote_count ) ) {
			$upvote_count = 0;
		}
		$upvote_count = (int) $upvote_count + 1;
		$check        = update_comment_meta( (int) $review_id, 'review_upvote', $upvote_count );

		$this->update_voted_status( $current_user, (int) $review_id, true );

		$response_array = array(
			'success'     => false,
			'latestCount' => $upvote_count,
		);
		if ( $check ) {
			$response_array['success'] = true;
		}

		return new \WP_REST_Response(
			$response_array,
			200
		);
	}

	/**
	 * Display the upvote section for review.
	 *
	 * @since 3.1.0
	 * @param WP_Comment $review The WordPress Comment object.
	 */
	final public function display_upvote( $review ) {
		if ( ! is_product() ) {
			return;
		}

		load_template( SPARKS_WC_PATH . 'includes/templates/advanced_product_review/upvote.php', false, array( 'review' => $review ) );
	}

	/**
	 * Register styles and script assets
	 *
	 * @since 3.1.0
	 */
	final public function register_assets() {
		$asset_file = include_once SPARKS_WC_PATH . 'includes/assets/build/advanced_product_review.asset.php';
		sparks_enqueue_style( 'sparks-apr-style', SPARKS_WC_URL . 'includes/assets/advanced_product_review/css/style.min.css', array(), $asset_file['version'] );
		sparks_enqueue_script( 'sparks-apr-script', SPARKS_WC_URL . 'includes/assets/build/advanced_product_review.js', $asset_file['dependencies'], $asset_file['version'], true );
		wp_localize_script(
			'sparks-apr-script',
			'sparksApr',
			array(
				'adminAjaxUrl'   => rest_url( SPARKS_WC_REST_NAMESPACE . '/products/' ),
				'adminAjaxNonce' => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_script_add_data( 'sparks-apr-script', 'async', true );
	}

	/**
	 * Modify review comment form.
	 *
	 * @since 3.1.0
	 * @param array $comment_form The comment form data.
	 *
	 * @return array
	 */
	final public function change_comment_form( $comment_form ) {
		if ( ! is_product() ) {
			return $comment_form;
		}

		if ( $this->enable_title ) {
			$comment_form['comment_field'] = '<p class="sp-comment-form-title"><label for="title">' . esc_html__( 'Review title', 'sparks-for-woocommerce' ) . '</label><input type="text" name="title" id="title"/></p>' . $comment_form['comment_field'];
		}

		$comment_form['label_submit'] = __( 'Submit review', 'sparks-for-woocommerce' );
		if ( $this->enable_attachments ) {
			$image_form = '<div class="sp-review-upload-section">
				<label for="sp-upload-review-image" > ' . esc_html__( 'Add a Photo (Optional)', 'sparks-for-woocommerce' ) . ' </label >
				<div class="sp-review-upload-preview">
					<ul id="sp-upload-review-list"></ul>
					<input class="" type="button" value="+" title="' . esc_attr__( 'Choose image(s)', 'sparks-for-woocommerce' ) . '" id="sp-do-upload" />
					<input type="file" name="sp-review-images[]" id="sp-upload-review-image" accept="image/*" multiple="true" />
				</div>
				<p id="sp-upload-max">' . __( 'Maximum amount of allowed images reached.', 'sparks-for-woocommerce' ) . '</p>
			</div>';
			if ( ! is_user_logged_in() ) {
				$comment_form['fields']['email'] .= $image_form;
			} else {
				$comment_form['comment_field'] .= $image_form;
			}
		}

		return $comment_form;
	}

	/**
	 * Display the attachments thumbnails for review.
	 *
	 * @since 3.1.0
	 * @param WP_Comment $review The WordPress Comment object.
	 */
	final public function display_attachments( $review ) {
		$is_toplevel   = ( 0 === (int) $review->comment_parent );
		$thumbnail_div = '';

		if ( $is_toplevel && $this->enable_attachments ) {
			$thumbs = get_comment_meta( (int) $review->comment_ID, 'user_review_images', true );
			if ( $thumbs ) {

				$thumbnail_div = '<div class="sp-review-gallery">';

				foreach ( $thumbs as $thumb_id ) {
					$thumbnail_div .= $this->get_review_thumbnail_output( $thumb_id );
				}
				$thumbnail_div .= ' </div> ';
			}
		}

		if ( ! empty( $thumbnail_div ) ) {
			echo wp_kses_post( $thumbnail_div );
		}
	}

	/**
	 * Returns the output of a review thumbnail as wrapped anchor HTML element.
	 *
	 * @param  int $attachment_id   Attachment ID.
	 * @return string
	 */
	protected function get_review_thumbnail_output( $attachment_id ) {
		$file_url = wp_get_attachment_url( $attachment_id );
		/**
		 * Filter the thumbnail "Width" size for review image thumbnails.
		 *
		 * @since 1.1.4
		 *
		 * @param int $size The size of the thumbnail in pixel.
		 * @param string $dimension_type The dimension type of the thumbnail (width or height).
		 * @param int $attachment_id Original attachment ID of the thumbnail.
		 */
		$width = apply_filters( 'sparks_apr_review_thumbnail_size', 100, 'width', $attachment_id ); // unit: px
		/**
		 * Filter the thumbnail "Height" size for review image thumbnails.
		 *
		 * @since 1.1.4
		 *
		 * @param int $size The size of the thumbnail in pixel.
		 * @param string $dimension_type The dimension type of the thumbnail (width or height).
		 * @param int $attachment_id Original attachment ID of the thumbnail.
		 */
		$height      = apply_filters( 'sparks_apr_review_thumbnail_size', 100, 'height', $attachment_id ); // unit: px
		$image_thumb = wp_get_attachment_image_src( $attachment_id, array( $width, $height ), true );

		return "<a class='sp-review-image' href='#' data-image='$file_url'><img class=\"nv_review_thumbnail\" loading=\"lazy\" alt='" . __( 'Review Image', 'sparks-for-woocommerce' ) . "' src='{$image_thumb[0]}'></a>";
	}

	/**
	 * Quick view modal markup
	 *
	 * @since 3.1.0
	 */
	final public function render_modal() {
		if ( ! is_product() ) {
			return;
		}

		echo '<div id="review-image-modal" class="sp-modal" aria-modal="true">';
		echo '<div class="sp-modal-overlay jsOverlay"></div>';
		echo '<div class="sp-modal-container is-loading">';
		echo '<button class="sp-modal-close jsModalClose" aria-label="' . esc_attr__( 'Close Quick View', 'sparks-for-woocommerce' ) . '">&#10005;</button>';
		echo '<div class="sp-modal-inner-content"></div>';
		echo '<div class="sp-loader-wrap"><span class="sp-loader"></span></div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Method to save attachments if they are present.
	 *
	 * @since 3.1.0
	 * @param int $comment_id The comment ID.
	 */
	final public function save_attachments( $comment_id ) {
		if ( ! $this->enable_attachments ) {
			return;
		}

		if ( $_FILES ) {
			$files       = isset( $_FILES['sp-review-images'] ) ? wc_clean( $_FILES['sp-review-images'] ) : array();
			$files_count = isset( $files['name'] ) ? count( $files['name'] ) : 0;

			// Check for attachments limits.
			if ( ( $this->attachments_limit > 0 ) && ( $files_count > $this->attachments_limit ) ) {
				return;
			}

			$attachments_array = array();

			foreach ( $files['name'] as $key => $value ) {
				if ( $files['name'][ $key ] ) {
					$file   = array(
						'name'     => $files['name'][ $key ],
						'type'     => $files['type'][ $key ],
						'tmp_name' => $files['tmp_name'][ $key ],
						'error'    => (int) $files['error'][ $key ],
						'size'     => (int) $files['size'][ $key ],
					);
					$_FILES = array( 'sp-review-images' => $file );

					foreach ( $_FILES as $file => $array ) {
						$attach_id = $this->insert_attachment( $file, $comment_id );
						if ( ! is_wp_error( $attach_id ) && false !== $attach_id && 0 !== $attach_id ) {
							array_push( $attachments_array, $attach_id );
						}
					}
				}
			}

			// save review with attachments array.
			if ( ! empty( $attachments_array ) ) {
				update_comment_meta( $comment_id, 'user_review_images', $attachments_array );
			}
		}
	}

	/**
	 * Upload the file as media.
	 *
	 * @since 3.1.0
	 * @param string $file_handler The file name.
	 * @param int    $post_id The post ID.
	 *
	 * @return false|int|\WP_Error
	 */
	private function insert_attachment( $file_handler, $post_id ) {
		if ( ! isset( $_FILES[ $file_handler ]['error'] ) ) {
			return false;
		}
		$has_file = UPLOAD_ERR_OK !== (int) $_FILES[ $file_handler ]['error'] ? sanitize_text_field( wp_unslash( $_FILES[ $file_handler ]['error'] ) ) : '';
		if ( ! empty( $has_file ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		return media_handle_upload( $file_handler, $post_id );
	}

	/**
	 * Process the comment author for product reviews.
	 * Anonymize the name.
	 *
	 * @since 3.1.0
	 * @param string     $author The author name.
	 * @param int        $comment_id The comment ID.
	 * @param WP_Comment $comment The WordPress comment object.
	 */
	final public function anonymize_author_name( $author, $comment_id, $comment ) {
		if ( ! is_product() ) {
			return $author;
		}

		$words    = explode( ' ', $author );
		$initials = strtoupper( substr( $words[0], 0, 1 ) ) . '. ';
		if ( count( $words ) >= 2 ) {
			$initials .= strtoupper( substr( end( $words ), 0, 1 ) ) . '. ';
		}
		return $initials;
	}

	/**
	 * Add a nonce field to the review form.
	 *
	 * @since 3.1.0
	 * @param int $post_id The post ID.
	 */
	final public function add_review_from_nonce( $post_id ) {
		if ( is_product() ) {
			wp_nonce_field( 'review_nonce' );
		}
	}

	/**
	 * Saves the review title if set.
	 *
	 * @since 3.1.0
	 * @param int $comment_id The WordPress comment ID.
	 */
	final public function add_review_title_meta( $comment_id ) {
		if ( ! isset( $_POST['_wpnonce'] ) || empty( $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'review_nonce' ) === false ) {
			return;
		}

		if ( isset( $_POST['title'], $_POST['comment_post_ID'] ) && 'product' === get_post_type( absint( $_POST['comment_post_ID'] ) ) ) {
			if ( empty( $_POST['title'] ) ) {
				return;
			}
			update_comment_meta( $comment_id, 'title', sanitize_text_field( $_POST['title'] ), true );
		}
	}

	/**
	 * Will display the review title if available.
	 *
	 * @since 3.1.0
	 * @param WP_Comment $comment The WordPress Comment object.
	 */
	final public function display_comment_title( $comment ) {
		$review_title = get_comment_meta( (int) $comment->comment_ID, 'title', true );

		if ( ! empty( $review_title ) ) {
			echo '<h5 class="review_title"> ' . wp_kses_post( $review_title ) . '</h5> ';
		}
	}

	/**
	 * Add a new input field for adding title to a review.
	 *
	 * @since 3.1.0
	 */
	final public function add_title_field_on_comment_form() {
		if ( ! is_product() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		echo '<p class="sp-comment-form-title"><label for="title">' . esc_html__( 'Review title', 'sparks-for-woocommerce' ) . '</label><input type="text" name="title" id="title"/></p>';
	}

	/**
	 * Returns the review section end index.
	 * If it can not find the end it defaults to the end of the array as an offset.
	 *
	 * @since 3.1.0
	 * @param array $settings Array of settings from Woocommerce.
	 *
	 * @return int
	 */
	private function get_review_section_end_key( $settings ) {
		foreach ( $settings as $index => $setting ) {
			if ( empty( $setting ) ) {
				continue;
			}
			if ( ! is_array( $setting ) ) {
				continue;
			}
			if ( ! array_diff( [ 'type', 'id' ], array_keys( $setting ) ) && 'sectionend' === $setting['type'] && 'product_rating_options' === $setting['id'] ) {
				return $index;
			}
		}

		return -1;
	}

	/**
	 * Defines additional settings for Woocommerce product settings.
	 *
	 * @since 3.1.0
	 * @return array[]
	 */
	private function get_advanced_review_settings() {
		return array(
			array(
				'desc'          => __( 'Allow users to attach images to reviews', 'sparks-for-woocommerce' ),
				'id'            => $this->get_option_key( self::ENABLE_REVIEW_IMAGES ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'          => __( 'Display reviewer name anonymized i.e. Jane Doe as J.D.', 'sparks-for-woocommerce' ),
				'id'            => $this->get_option_key( self::ENABLE_REVIEW_ANONYMIZE ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( 'Hide reviewer profile picture', 'sparks-for-woocommerce' ),
				'id'            => $this->get_option_key( self::ENABLE_HIDE_AVATAR ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( 'Enable review title, allow users to add a title when doing a review', 'sparks-for-woocommerce' ),
				'id'            => $this->get_option_key( self::ENABLE_REVIEW_TITLE ),
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'title'           => __( 'Review voting', 'sparks-for-woocommerce' ),
				'desc'            => __( 'Enable review voting', 'sparks-for-woocommerce' ),
				'id'              => $this->get_option_key( self::ENABLE_REVIEW_VOTING ),
				'default'         => 'no',
				'type'            => 'checkbox',
				'checkboxgroup'   => 'start',
				'show_if_checked' => 'option',
			),
			array(
				'desc'            => __( 'Allow unregistered users to vote. Unchecking this will allow only registered users to vote.', 'sparks-for-woocommerce' ),
				'id'              => $this->get_option_key( self::ENABLE_REVIEW_UNREGISTERED_VOTING ),
				'default'         => 'no',
				'type'            => 'checkbox',
				'checkboxgroup'   => '',
				'show_if_checked' => 'yes',
			),
		);
	}

	/**
	 * Filter Woocommerce settings and add our own settings to "general" tab of the Product settings.
	 *
	 * @since 3.1.0
	 * @param array  $settings Product settings.
	 * @param string $section_id The section ID.
	 *
	 * @return array
	 */
	final public function woocommerce_settings_products_filter( $settings, $section_id ) {
		// the section_id of the "general" tab is empty string "".
		if ( '' !== $section_id ) {
			return $settings;
		}

		$review_setting_title_key = $this->get_review_section_end_key( $settings );
		$advanced_review_settings = $this->get_advanced_review_settings();

		array_splice( $settings, $review_setting_title_key, 0, $advanced_review_settings );
		return $settings;
	}
}
