<?php

namespace WpLandingKit\Framework\Container;

use ArrayAccess;
use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * Class Container
 */
class Container implements ArrayAccess {

	/**
	 * The raw bindings.
	 * e.g; ['some.key' => 'some value', 'another.key' => function(){…} ]
	 *
	 * @var Closure array
	 */
	protected $bindings = [];

	/**
	 * An array of key/bool pairs for tracking which keys are singletons.
	 * e.g; [ 'some.key' => bool(TRUE) ]
	 *
	 * @var array
	 */
	protected $singletons = [];

	/**
	 * An array of key/bool pairs for tracking which keys are factories.
	 * e.g; [ 'some.key' => bool(TRUE) ]
	 *
	 * @var array
	 */
	protected $factories = [];

	/**
	 * An array of resolved bindings against their keys.
	 *
	 * @var array
	 */
	protected $instances = [];

	/**
	 * An array of key/bool pairs for tracking which keys are protected. Protected bindings cannot be overriden unless
	 * they are unbound first.
	 * e.g; [ 'some.key' => bool(TRUE) ]
	 *
	 * @var array
	 */
	protected $protected = [];

	/**
	 * An array of key/bool pairs for tracking which bindings have been resolved.
	 * e.g; [ 'some.key' => bool(TRUE) ]
	 *
	 * @var array
	 */
	protected $resolved = [];

	/**
	 * An array of key/value pairs mapping aliases to bound class names
	 * e.g; [ 'alias_1' => Some\ClassName ]
	 *
	 * @var array
	 */
	protected $aliases = [];

	/**
	 * Tracks the previously-bound key for any chained method usage. e.g; $this->bind('SomeClass')->alias('some_class')
	 *
	 * @var null
	 */
	protected $last_bound_key = null;

	/**
	 * @param array $values
	 */
	public function __construct( array $values = [] ) {
		foreach ( $values as $key => $value ) {
			$this->bind( $key, $value );
		}
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function make( $key ) {
		$key = $this->get_alias( $key );

		$resolved = $this->resolve( $key );

		if ( $this->is_singleton( $key ) ) {
			return empty( $this->instances[ $key ] )
				? $this->cache_instance( $key, $resolved )
				: $this->instances[ $key ];
		}

		return ( $this->is_factory( $key ) and is_callable( $resolved ) )
			? $resolved()
			: $resolved;
	}

	/**
	 * Binds an instance directly into the container. This instance will not be subject to resolution.
	 *
	 * @param $key
	 * @param $instance
	 */
	public function instance( $key, $instance ) {
		$this->resolved[ $key ] = true;
		$this->cache_instance( $key, $instance );
	}

	/**
	 * @param $key
	 * @param $concrete
	 *
	 * @return Container
	 */
	public function singleton( $key, $concrete = null ) {
		$this->singletons[ $key ] = true;

		return $this->bind( $key, $concrete );
	}

	/**
	 * @param $key
	 * @param $concrete
	 *
	 * @return Container
	 */
	public function protect( $key, $concrete = null ) {
		$this->protected[ $key ] = true;

		return $this->bind( $key, $concrete );
	}

	/**
	 * @param $key
	 * @param $concrete
	 *
	 * @return Container
	 */
	public function factory( $key, $concrete = null ) {
		$this->factories[ $key ] = true;

		if ( $concrete === null ) {
			$concrete = $key;
		}

		return $this->bind( $key, function () use ( $key, $concrete ) {
			return $concrete;
		}, false );
	}

	/**
	 * Extend an existing binding. This will wrap the existing binding in the supplied closure which will be invoked
	 * after the existing binding effectively allowing modification of the instantiated value immediately after it is
	 * created.
	 *
	 * @param string $key
	 * @param Closure $closure
	 */
	public function extend( $key, Closure $closure ) {
		$binding = $this->get_bound_or_fail( $key );

		if ( ! is_callable( $binding ) ) {
			throw new InvalidArgumentException( "Container binding for key '$key' is not callable and cannot be extended." );
		}

		$extended = function ( $container ) use ( $closure, $binding ) {
			// Keep this as a separate line of code to prevent VaultPress false positives.
			$bound = $binding( $container );

			return $closure( $bound, $this );
		};

		$this->bind( $key, $extended );
	}

	/**
	 * @param string $key
	 * @param mixed $concrete
	 * @param bool $shared
	 *
	 * @return Container
	 */
	public function bind( $key, $concrete = null, $shared = true ) {
		if ( $this->is_protected( $key ) and $this->is_bound( $key ) ) {
			throw new RuntimeException( "Key '$key' is a protected container binding and cannot be overridden." );
		}

		if ( $concrete === null ) {
			$concrete = $key;

		} elseif ( $this->is_abstract_key_and_concrete_class( $key, $concrete ) ) {
			$this->alias( $key, $concrete );
		}

		if ( $shared ) {
			$this->singletons[ $key ] = true;
		}

		$this->bindings[ $key ] = $this->enclose( $concrete );

		$this->last_bound_key = $key;

		return $this;
	}

	/**
	 * Add an alias for a given binding
	 *
	 * @param $key
	 * @param $alias
	 */
	public function alias( $key, $alias = null ) {
		if ( func_num_args() === 1 ) {

			if ( ! $this->last_bound_key ) {
				throw new RuntimeException( __METHOD__ . ' method called with one argument while no last bound key was available.' );
			}

			$alias = $key;
			$key = $this->last_bound_key;
		}

		$this->aliases[ $alias ] = $key;
	}

	/**
	 * Get an aliased key if it exists
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function get_alias( $key ) {
		return empty( $this->aliases[ $key ] )
			? $key
			: $this->aliases[ $key ];
	}

	/**
	 * @param $key
	 */
	public function unbind( $key ) {
		unset(
			$this->bindings[ $key ],
			$this->singletons[ $key ],
			$this->factories[ $key ],
			$this->protected[ $key ],
			$this->instances[ $key ],
			$this->aliases[ $key ],
			$this->resolved[ $key ]
		);
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function is_bound( $key ) {
		return isset( $this->bindings[ $key ] );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function is_singleton( $key ) {
		return isset( $this->singletons[ $key ] );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function is_protected( $key ) {
		return isset( $this->protected[ $key ] );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function is_factory( $key ) {
		return isset( $this->factories[ $key ] );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return $this->is_bound( $offset );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return mixed|null
	 * @throws Exception
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->is_bound( $offset ) ? $this->make( $offset ) : null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->bind( $offset, $value );
	}

	/**
	 * @param mixed $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->unbind( $offset );
	}

	/**
	 * Checks to see if a concrete class name is being bound to an abstract key.
	 *
	 * @param $key
	 * @param $concrete
	 *
	 * @return bool
	 */
	protected function is_abstract_key_and_concrete_class( $key, $concrete ) {
		return ( is_string( $key ) and ! class_exists( $key ) ) and
			   ( is_string( $concrete ) and class_exists( $concrete ) );
	}

	/**
	 * Wrap anything that isn't a closure in a closure
	 *
	 * @param $concrete
	 *
	 * @return Closure
	 */
	protected function enclose( $concrete ) {
		if ( $concrete instanceof Closure ) {
			return $concrete;
		}

		return function ( Container $container ) use ( $concrete ) {
			return $concrete;
		};
	}

	/**
	 * Cache an instance for reuse on subsequent requests to the container
	 *
	 * @param $key
	 * @param $instance
	 *
	 * @return mixed
	 */
	protected function cache_instance( $key, $instance ) {
		return $this->instances[ $key ] = $instance;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	protected function get_bound_or_fail( $key ) {
		if ( ! $this->is_bound( $key ) ) {
			throw new InvalidArgumentException( "Container binding for key '$key' not found." );
		}

		return $this->bindings[ $key ];
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function resolve( $key ) {
		if ( isset( $this->resolved[ $key ], $this->instances[ $key ] ) ) {
			$resolved = $this->instances[ $key ];

		} else {
			$binding = $this->get_bound_or_fail( $key );

			$resolved = ( $binding instanceof Closure )
				? $binding( $this )
				: $binding;

			if ( $this->is_buildable( $resolved ) ) {
				$resolved = $this->build( $resolved );
			}

			$this->resolved[ $key ] = true;
		}

		return $resolved;
	}

	/**
	 * @param $class_name
	 *
	 * @return bool
	 */
	protected function is_buildable( $class_name ) {
		return is_string( $class_name ) and class_exists( $class_name );
	}

	/**
	 * Attempt to build a class and its dependencies (recursively) using reflection.
	 *
	 * @param $class_name
	 *
	 * @return object
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function build( $class_name ) {
		$reflector = new ReflectionClass( $class_name );

		if ( ! $reflector->isInstantiable() ) {
			throw new Exception( "Failed to build container binding – '$class_name' is not instantiable." );
		}

		$constructor = $reflector->getConstructor();

		if ( is_null( $constructor ) ) {
			return new $class_name;
		}

		$resolved = [];
		$args = $constructor->getParameters();

		foreach ( $args as $arg ) {
			// This method is available in PHP 7.0 and later.
			if ( method_exists( $arg, 'getType' ) ) {
				$class = $arg->getType();
			} else {
				$class = $arg->getClass();
			}
			if ( $class ) {
				$resolved[] = $this->make( $class->getName() );

			} else {
				if ( $arg instanceof Closure ) {
					$v = $arg();

				} elseif ( $arg->isDefaultValueAvailable() ) {
					$v = $arg->getDefaultValue();

				} else {
					throw new Exception( "Failed to resolved dependency '{$arg->getName()}' for '$class_name'" );
				}

				$resolved[] = $v;
			}

		}
		return $reflector->newInstanceArgs( $resolved );
	}

}
