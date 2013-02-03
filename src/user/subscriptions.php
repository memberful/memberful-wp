<?php
require_once MEMBERFUL_DIR.'/src/user/entity.php';

/**
 * Interface for interacting with a user's products
 *
 */
class Memberful_Wp_User_Subscriptions extends Memberful_Wp_User_Entity { 

	static public function sync( $user_id, $entities ) {
		$syncer = new Memberful_Wp_User_Subscriptions($user_id);
		return $syncer->set($entities);
	}

	protected function entity_type() {
		return 'subscription';
	}

	protected function format( $entity ) {
		return array(
			'id'         => $entity->subscription->id,
			'expires'    => $entity->expires,
			'expires_at' => $entity->expires_at,
		);
	}
}
