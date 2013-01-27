<?php
require MEMBERFUL_DIR.'/src/user/entity.php';

/**
 * Interface for interacting with a user's products
 *
 */
class Memberful_Wp_User_Products extends Memberful_Wp_User_Entity { 

	protected function entity_type() {
		return 'product';
	}

	protected function format( $entity ) {
		return array( 'id' => $entity->id );
	}
}