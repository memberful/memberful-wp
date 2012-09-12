<?php

class Memberful_Wp_User_Subscriptions { 

	protected $subscriptions;
	protected $user_id;

	public function __construct( $user_id ) { 
		$this->user_id = $user_id;
	}

	/**
	 * Get the IDs of the subscriptions this user has
	 *
	 * @return array
	 */
	public function get() { 
		if ( $this->subscriptions === NULL ) { 
			$this->subscriptions = get_user_meta( $this->user_id, 'memberful_subscriptions' );
		}

		return $this->subscriptions;
	}

	/**
	 * Add a set of subscriptions to the subscriptions the user has
	 *
	 * @param array $subscriptions
	 */
	public function add( array $subscriptions ) { 
		$ids = array();

		foreach ( $subscriptions as $subscription ) { 
			$ids[$subscription->id] = array(
				'expires_at' => $subscription->expires_at
			);
		}

		return $this->addIds( $ids );
	}

	/**
	 * Add a set of subscription ids to the subscriptions the user has
	 *
	 * @param array $subscription_ids
	 */
	public function addIds( array $subscription_ids ) { 
		$new_ids = array_combine( $subscription_ids, $subscription_ids );

		update_user_meta( $this->user_id, 'memberful_subscriptions', $this->get() + $new_ids );
	}
}
