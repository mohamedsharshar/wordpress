<?php

namespace WpLandingKit\Framework\Events;

use WpLandingKit\Framework\Container\Application;

class Dispatcher {

	/** @var Application */
	protected $app;

	/**
	 * The registered event listeners.
	 *
	 * @var array
	 */
	protected $listeners = [];

	/**
	 * @param Application $app
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Register a listener for one or more events.
	 *
	 * @param string|array $events
	 * @param \Closure|string $listener
	 */
	public function listen( $events, $listener ) {
		foreach ( (array) $events as $event ) {
			$this->listeners[ $event ][] = $this->make_listener( $listener );
		}
	}


	//public function subscriber( $subscriber ) {
	//	// todo - add support for subscribers
	//}

	/**
	 * Dispatch an event. This will accept the following configurations:
	 *
	 * 1. An abstract event name (string) and payload array
	 *      - Payload will remain in tact in the listener.
	 * 2. An event object
	 *      - Object FQN will be passed to listener's $event argument.
	 *      - Payload will be overridden to be an array containing the event object. e.g; [$event].
	 *      - Anything passed to the $payload arg is lost. Data should be handed to the event object instead and
	 *        transmitted that way.
	 * 3. An FQN as the event name and a payload array
	 *      - FQN is never resolved from the container so it is treated as just a string in this scenario.
	 *      - Payload will remain in tact.
	 *      - Useful only if you need to use an event class as a signal-only with some custom payload.
	 *      - Listener could attempt to resolve the event from the container if needed.
	 *
	 * @param object|string $event The event to dispatch
	 * @param array $payload Data to dispatch with the event. If dispatching an object, payload should be inside the
	 *                       object. See method description for details.
	 *
	 * @return array An array of listener responses
	 */
	public function dispatch( $event, $payload = [] ) {

		$responses = [];

		if ( is_object( $event ) ) {
			$payload = [ $event ];
			$event = get_class( $event );
		}

		if ( empty( $this->listeners[ $event ] ) ) {
			return $responses;
		}

		foreach ( $this->listeners[ $event ] as $listener ) {

			// If the listener is a string, attempt to resolve from container
			if ( is_string( $listener ) ) {
				$listener = $this->app->make( $listener );
			}

			// If listener is a closure, invoke it
			if ( $listener instanceof \Closure ) {
				$responses[] = $listener( $event, $payload );
				continue;
			}

			// If listener is an object, invoke the handle method
			if ( is_object( $listener ) and method_exists( $listener, 'handle' ) ) {
				$responses[] = $listener->handle( $event, $payload );
				continue;
			}
		}

		return $responses;
	}

	public function make_listener( $listener ) {
		// todo - consider whether we enforce Closures here or not.
		return $listener;
	}

}