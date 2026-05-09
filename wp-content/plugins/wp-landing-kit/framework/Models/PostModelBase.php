<?php

namespace WpLandingKit\Framework\Models;

use WpLandingKit\Framework\PostTypes\PostTypeBase;
use WpLandingKit\Framework\Traits\DecoratesAndMutatesPostObject;
use WP_Error;
use WP_Post;

/**
 * Class PostModelBase
 * @package WpLandingKit\Framework\Models
 *
 * Base class for creating models
 */
class PostModelBase {

	use DecoratesAndMutatesPostObject;

	/**
	 * The FQN of the class that registers the post type that underlies this model.
	 */
	const TYPE_CLASS = PostTypeBase::class;

	/**
	 * Get the post type from this model's associated type class.
	 *
	 * @return mixed|null
	 */
	public static function post_type() {
		$constant = static::TYPE_CLASS . "::POST_TYPE";

		return defined( $constant )
			? constant( $constant )
			: PostTypeBase::POST_TYPE;
	}

	/**
	 * Decorate an existing post object.
	 *
	 * @param WP_Post $post
	 *
	 * @return static
	 */
	public static function make( WP_Post $post ) {
		$instance = new static;
		$instance->set_post_object( $post );
		$instance->setup();

		return $instance;
	}

	/**
	 * Find a post by ID. This does not take into consideration the post type that is
	 * specific to the object extending this base class so it is possible to get a post
	 * of any type and decorate it using any concrete post type class in the application.
	 *
	 * @param $post_id
	 *
	 * @return static|null
	 */
	public static function find( $post_id ) {
		$post = get_post( $post_id );

		return ( $post instanceof WP_Post )
			? self::make( $post )
			: null;
	}


	// Locate a post by post name (slug)
	//public static function find_by_name( $post_name ) {
	//	// todo
	//}

	/**
	 * Set up the object based on the underlying decorated WP_Post object. This is the place to set up any custom domain
	 * properties. Run this immediately after post object has been decorated.
	 */
	public function setup() {
		// noop
	}

	/**
	 * Insert/update this post object.
	 *
	 * @return int|WP_Error
	 */
	public function save() {
		return wp_insert_post( $this->post->to_array(), true );
	}

}