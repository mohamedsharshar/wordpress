<?php

use WpLandingKit\Facades\DomainMap;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\DomainRegistry;
use WpLandingKit\Utils\Error;

class WPLK_Domain {

	/** @var Domain */
	private $model = null;

	/** @var string */
	private $host = '';

	/** @var bool */
	private $is_active = false;

	/** @var int */
	private $owner_id = 0;

	/** @var string '', 'http', or 'https' */
	private $enforced_protocol = '';

	/** @var int|null */
	private $site_icon = null;

	/** @var WPLK_Mapping */
	private $root;

	/** @var WPLK_Mapping[] */
	private $mappings = [];

	/** @var WPLK_Mapping */
	private $fallback;

	/** @var string|null */
	private $site_script = null;

	/**
	 * @param int|string $domain The post ID of the domain, the domain host name, or a URL containing the host name.
	 *
	 * @param bool $fresh Whether to get a fresh copy from the DB or pull from the cache/registry. Pass TRUE to force a
	 *      DB query and get the latest from the database.
	 *
	 * @return WPLK_Domain|null Return instance if found or NULL if no matching domain post could be found.
	 */
	public static function get_instance( $domain, $fresh = false ) {

		if ( ! $fresh and $cached = DomainRegistry::get( $domain ) ) {
			return $cached;
		}

		if ( is_numeric( $domain ) ) {
			$domain = Domain::find( $domain );

		} elseif ( is_string( $domain ) ) {
			$domain = Domain::find_by_domain( $domain );
		}

		if ( ! $domain instanceof Domain ) {
			return null;
		}

		// Domain posts that are in the trash don't exist to us.
		if ( $domain->post_status === 'trash' ) {
			return null;
		}

		$instance = new static( $domain );
		DomainRegistry::set( $instance );

		return $instance;
	}

	/**
	 * Create new domain instances. To query existing domains, use the \WPLK_Domain::get_instance() static method.
	 *
	 * @param string|array|Domain $domain
	 */
	public function __construct( $domain = '' ) {
		if ( empty( $domain ) ) {
			$this->model();

		} elseif ( is_array( $domain ) ) {
			$args = wp_parse_args( $domain, [
				'host' => $this->host,
				'owner_id' => $this->owner_id,
				'enforced_protocol' => $this->enforced_protocol,
			] );
			$this->model();
			$this->set_host( $args['host'] );
			$this->set_owner( $args['owner_id'] );
			$this->set_enforced_protocol( $args['enforced_protocol'] );

		} elseif ( is_string( $domain ) ) {
			$this->model();
			$this->set_host( $domain );

		} elseif ( $domain instanceof Domain ) {
			$this->model = $domain;
			$this->hydrate_from_model();
		}
	}

	/**
	 * Get the post ID for this domain. Null if not yet saved.
	 *
	 * @return int|null
	 */
	public function post_id() {
		return $this->model()->ID;
	}

	/**
	 * Extract and set the host name of any given URL. This setter will accept URLs containing protocols and paths but
	 * will extract the host name and set only that.
	 *
	 * e.g; If http://sub.domain.com/path is passed in, sub.domain.com is set on the host property.
	 *
	 * @param $host
	 */
	public function set_host( $host ) {
		$this->host = Domain::post_type_obj()->sanitize_title( $host );
	}

	/**
	 * Get the host name.
	 *
	 * @return string
	 */
	public function host() {
		return $this->host;
	}

	/**
	 * Add a dynamic mapping to this domain.
	 *
	 * @param string|array|WPLK_Mapping|callable $mapping Either a URL path, an array of mapping args, a WPLK_Mapping
	 *      object, or a callback function that takes a WPLK_Mapping object as a parameter.
	 * @param null|int|WP_Post $post The post ID or WP_Post object for a post to map to.
	 * @param bool $is_regex
	 *
	 * @return WPLK_Mapping
	 */
	public function add_mapping( $mapping = '', $post = null, $is_regex = false ) {
		if ( $mapping instanceof WPLK_Mapping ) {
			$m = $mapping;
			if ( is_callable( $post ) ) {
				$post( $m );
			}

		} elseif ( is_callable( $mapping ) ) {
			$m = new WPLK_Mapping();
			$mapping( $m );

		} elseif ( is_array( $mapping ) ) {
			$m = WPLK_Mapping::from_array( $mapping );
			if ( is_callable( $post ) ) {
				$post( $m );
			}

		} elseif ( is_callable( $post ) ) {
			$m = new WPLK_Mapping( $mapping, $is_regex );
			$post( $m );

		} else {
			$m = new WPLK_Mapping( $mapping, $is_regex );
			if ( $post ) {
				$m->maps_to_post( $post );
			}
		}

		return $this->mappings[] = $m;
	}

	/**
	 * Set/get the root mapping object. The first instantiation of this method will create a new instance and assign it
	 * to the `root` property. Subsequent calls made to this method without args will return the same instance. If the
	 * first arg to this method contains a value on subsequent calls, the mapping object will be recreated and will need
	 * to be reconfigured.
	 *
	 * @param null|int|WP_Post|array|callable $mapping Null for an empty mapping object. Post ID or WP_Post object to map to a
	 *      post. An array of mapping args to create a new mapping object from.
	 * @param callable|null $callback A callback that receives the WPLK_Mapping instance as an arg.
	 *
	 * @return WPLK_Mapping
	 */
	public function root( $mapping = null, callable $callback = null ) {
		if ( ! $this->root or $mapping ) {
			$this->root = $this->make_mapping_obj( $mapping, $callback );
			$this->root->set_url_path( '', false );
		}

		return $this->root;
	}

	/**
	 * Set/get the root mapping object. The first instantiation of this method will create a new instance and assign it
	 * to the `fallback` property. Subsequent calls made to this method without args will return the same instance. If
	 * the first arg to this method contains a value on subsequent calls, the mapping object will be recreated and will
	 * need to be reconfigured.
	 *
	 * @param null|int|WP_Post|array|callable $mapping Null for an empty mapping object. Post ID or WP_Post object to map to a
	 *      post. An array of mapping args to create a new mapping object from.
	 * @param callable|null $callback A callback that receives the WPLK_Mapping instance as an arg.
	 *
	 * @return WPLK_Mapping
	 */
	public function fallback( $mapping = null, callable $callback = null ) {
		if ( ! $this->fallback or $mapping ) {
			$this->fallback = $this->make_mapping_obj( $mapping, $callback );
			$this->fallback->set_url_path( '^.+', true );

			if ( ! $this->fallback->is_mapped() ) {
				$this->fallback->redirects_to( '/' );
			}
		}

		return $this->fallback;
	}

	/**
	 * @return WPLK_Mapping[]
	 */
	public function mappings() {
		return $this->mappings ?: [];
	}

	/**
	 * Find the first mapping object that matches the given condition.
	 *
	 * @param string|callable $mapping_id The mapping ID, or a callback that receives the current mapping object for
	 *      evaluation. If the callback returns true, the search ends and the matching WPLK_Mapping instance is returned.
	 *
	 * @return WPLK_Mapping|false
	 */
	public function find_mapping( $mapping_id ) {

		// If $mapping_id is not a callback, make one.
		$callback = is_callable( $mapping_id ) ? $mapping_id : function ( WPLK_Mapping $m ) use ( $mapping_id ) {
			return $m->mapping_id === $mapping_id;
		};

		foreach ( $this->mappings as $m ) {
			if ( (bool) $callback( $m ) ) {
				return $m;
			}
		}

		return false;
	}

	/**
	 * Find an array of mapping instances that pass a function check.
	 *
	 * @param callable $callback Callable that accepts a WPLK_Mapping instance as its parameter. If this callback
	 *      returns TRUE, the mapping being checked is returned within an array of other mappings that also match the
	 *      conditions.
	 *
	 * @return WPLK_Mapping[]
	 */
	public function find_mappings( callable $callback ) {
		return array_values( array_filter( $this->mappings, $callback ) );
	}

	/**
	 * @param string|WPLK_Mapping|callable $mapping The mapping ID, the mapping object, or a callback that receives
	 *      the current mapping object for evaluation. If the callback returns true, the instance if removed from the
	 *      mappings array.
	 *
	 * @return WPLK_Mapping|bool The mapping removed or FALSE on failure.
	 */
	public function remove_mapping( $mapping ) {
		if ( is_string( $mapping ) ) {
			$callback = function ( WPLK_Mapping $m ) use ( $mapping ) {
				return $m->id() === $mapping;
			};

		} elseif ( $mapping instanceof WPLK_Mapping ) {
			$callback = function ( WPLK_Mapping $m ) use ( $mapping ) {
				return $m === $mapping;
			};

		} elseif ( is_callable( $mapping ) ) {
			$callback = $mapping;
		}

		if ( ! empty( $callback ) ) {
			foreach ( $this->mappings as $i => $m ) {
				if ( (bool) $callback( $m ) ) {
					// Remove the matched mapping.
					unset( $this->mappings[ $i ] );
					// Reindex back to zero.
					$this->mappings = array_values( $this->mappings );

					return $m;
				}
			}
		}

		return false;
	}

	/**
	 * Set the is_active flag. This is just a boolean handler which calls on other behavioural-style methods.
	 *
	 * @param $bool
	 *
	 * @return bool|WP_Error
	 */
	public function set_is_active( $bool ) {
		return $bool ? $this->activate() : $this->deactivate();
	}

	/**
	 * Check to see if this domain can be activated.
	 *
	 * @return bool
	 */
	public function can_activate() {
		return isset( $this->root ) ? $this->root->is_mapped() : false;
	}

	/**
	 * Set the active flag to TRUE on this domain. This does not persist to the database so this won't take effect until
	 * the $this->save() method is invoked.
	 *
	 * @return bool|WP_Error Returns TRUE on success, error if cannot be activated.
	 */
	public function activate() {
		if ( ! $this->can_activate() ) {
			return Error::make( 'Unable to activate %s domain — root mapping missing.', $this->host() );
		}

		return $this->is_active = true;
	}

	/**
	 * Set the active flag to FALSE on this domain. This does not persist to the database so this won't take effect
	 * until the $this->save() method is invoked.
	 *
	 * @return bool
	 */
	public function deactivate() {
		$this->is_active = false;

		return true;
	}

	/**
	 * Check whether this domain is flagged as active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->is_active;
	}

	/**
	 * Control protocol enforcement.
	 *
	 * @param string $protocol Either 'http', 'https', or FALSE to allow either protocol.
	 */
	public function set_enforced_protocol( $protocol ) {
		if ( empty( $protocol ) ) {
			$this->enforced_protocol = '';

		} else if ( is_string( $protocol ) ) {
			$opts = join( '|', Arr::sort_by_str_length( Domain::ENFORCABLE_PROTOCOLS, 'desc' ) );
			preg_match( "/($opts)/", strtolower( $protocol ), $matched );
			$this->enforced_protocol = isset( $matched[1] ) ? $matched[1] : '';
		}
	}

	/**
	 * Check to see if this domain has an enforced protocol.
	 *
	 * @return bool
	 */
	public function has_enforced_protocol() {
		return ! empty( $this->enforced_protocol );
	}

	/**
	 * Get this domain's enforced protocol.
	 *
	 * @return string
	 */
	public function enforced_protocol() {
		return $this->enforced_protocol;
	}

	/**
	 * Set the user ID of the domain post author.
	 *
	 * @param int|WP_User $user
	 */
	public function set_owner( $user ) {
		$user_id = $user instanceof WP_User ? $user->ID : $user;
		$this->owner_id = (int) $user_id;
	}

	/**
	 * Get the domain post's author ID.
	 *
	 * @return int
	 */
	public function owner_id() {
		return (int) $this->owner_id;
	}

	/**
	 * Set the attachment ID that serves as the domain's site icon.
	 *
	 * @param $attachment_id
	 */
	public function set_site_icon( $attachment_id ) {
		$this->site_icon = (int) $attachment_id;
	}

	/**
	 * Get the site icon attachment ID.
	 *
	 * @return int
	 */
	public function site_icon() {
		return (int) $this->site_icon;
	}

	/**
	 * Set the site Google Analytics & other scripts.
	 *
	 * @param string $script Google Analytics & other scripts.
	 */
	public function set_site_script( $script ) {
		$this->site_script = $script;
	}

	/**
	 * Set the site Google Analytics & other scripts.
	 *
	 * @return string|null
	 */
	public function site_script() {
		return $this->site_script;
	}

	/**
	 * Update the model and save to the DB.
	 *
	 * @return int|WP_Error
	 */
	public function save() {
		// No host name, no insert.
		if ( ! $this->host() ) {
			return Error::make( 'Unable to save domain as not host name has is not set. Use the set_host() method or pass the host name to the constructor.' );
		}

		// If a different post is already in the DB with the same host name, no insert.
		if ( $this->is_duplicate() ) {
			return Error::make( 'Unable to save %s domain. Host name already exists in DB.', $this->host() );
		}

		$this->update_model();
		$saved = $this->model->save();

		if ( ! is_wp_error( $saved ) ) {
			$this->update_map();
		}

		DomainRegistry::set( $this );

		return $saved;
	}

	/**
	 * Checks to see if this domain is a duplicate.
	 *
	 * @return bool
	 */
	public function is_duplicate() {
		$stored = Domain::find_by_domain( $this->host() );

		return $stored and $stored->post_status !== 'trash' and $stored->ID !== $this->post_id();
	}

	/**
	 * Delete the domain model from the database.
	 *
	 * @return true|WP_Error
	 */
	public function delete() {
		$deleted = (bool) wp_delete_post( $this->post_id(), true );

		if ( ! $deleted ) {
			return Error::make( 'Failed to delete domain.' );
		}

		DomainRegistry::purge( $this );

		$this->model->deactivate();

		// This needs to be before ID modifications but after model deactivation. If we don't do it this way, the map
		// isn't purged correctly.
		$this->update_map();

		$this->model->ID = null;
		$this->model->post_status = null;
		$this->is_active = false;

		return true;
	}

	/**
	 * Update the model with the data on this object.
	 */
	private function update_model() {
		$model = $this->model();

		$model->set_host( $this->host() );
		$this->is_active() ? $model->activate() : $model->deactivate();
		$model->set_enforced_protocol( $this->enforced_protocol() ?: Domain::DEFAULT_PROTOCOL );
		$model->post_author = $this->owner_id();
		$model->set_site_icon( $this->site_icon() );
		$model->set_site_script( $this->site_script() );

		// ** Update model with other data/props as needed, here ** //

		// Set mappings on model.
		$model->set_mappings( array_map( function ( $m ) {
			return $m->to_db_array();
		}, $this->get_all_mappings() ) );
	}

	/**
	 * Get this object's model. If it doesn't yet have one, generate one.
	 *
	 * @return Domain
	 */
	private function model() {
		if ( ! $this->model ) {
			$this->model = Domain::generate();
		}

		return $this->model;
	}

	/**
	 * @param null|int|WP_Post|array|callable $mapping Null for an empty mapping object. Post ID or WP_Post object to map to a
	 *      post. An array of mapping args to create a new mapping object from.
	 * @param callable|null $callback A callback that receives the WPLK_Mapping instance as an arg.
	 *
	 * @return WPLK_Mapping
	 */
	private function make_mapping_obj( $mapping = null, callable $callback = null ) {
		if ( is_callable( $mapping ) ) {
			$m = new WPLK_Mapping();
			$callback = $mapping;

		} elseif ( is_array( $mapping ) ) {
			$m = WPLK_Mapping::from_array( $mapping );

		} else {
			$m = new WPLK_Mapping();
			if ( $mapping ) {
				$m->maps_to_post( $mapping );
			}
		}

		if ( is_callable( $callback ) ) {
			$callback( $m );
		}

		return $m;
	}

	/**
	 * Populate this object's properties based on the model.
	 */
	private function hydrate_from_model() {
		$model = $this->model();

		// Reset anything that needs resetting.
		$this->mappings = [];

		$this->set_host( $model->host() );
		$this->set_enforced_protocol( $model->is_enforcing_protocol() ? $model->enforced_protocol() : '' );
		$this->set_owner( $model->post_author );
		$this->set_site_icon( $model->site_icon() );
		$this->set_site_script( $model->site_script() );

		// ** Hydrate any other properties on this object from the model, here ** //

		// Hydrate mappings.
		$this->root( $model->root_mapping() );
		$this->fallback( $model->fallback_mapping() );

		foreach ( $model->dynamic_mappings() as $mapping ) {
			$this->add_mapping( $mapping );
		}

		$this->set_is_active( $model->is_active() );
	}

	/**
	 * Return all mapping objects — including the root and fallback — in one ordered array.
	 *
	 * @return WPLK_Mapping[]
	 */
	private function get_all_mappings() {
		$mappings = $this->mappings();
		array_unshift( $mappings, $this->root() );
		array_push( $mappings, $this->fallback() );

		return $mappings;
	}

	private function update_map() {
		DomainMap::update_domain( $this->model() );
		DomainMap::save();
	}

}