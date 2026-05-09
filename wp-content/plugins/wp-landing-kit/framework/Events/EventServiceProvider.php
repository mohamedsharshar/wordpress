<?php

namespace WpLandingKit\Framework\Events;

use WpLandingKit\Framework\Providers\ServiceProviderBase;

class EventServiceProvider extends ServiceProviderBase {

	protected $listen = [];

	public function register() {

		// bind the dispatcher
		$this->app->singleton( 'events', Dispatcher::class );

		/** @var Dispatcher $dispatcher */
		$dispatcher = $this->app->make( 'events' );

		// bind all event classes and register listeners with dispatcher
		foreach ( $this->listens() as $event => $listeners ) {

			// bind the event if it is a class
			if ( class_exists( $event ) and ! $this->app->is_bound( $event ) ) {
				$this->app->factory( $event );
			}

			// bind each of the listeners
			foreach ( $listeners as $listener ) {
				if ( class_exists( $listener ) and ! $this->app->is_bound( $listener ) ) {
					$this->app->singleton( $listener );
				}

				$dispatcher->listen( $event, $listener );
			}
		}
	}

	public function listens() {
		return $this->listen ?: [];
	}

}