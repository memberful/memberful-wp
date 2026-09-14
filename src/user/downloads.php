<?php
require MEMBERFUL_DIR.'/src/user/entity.php';

/**
 * Interface for interacting with a user's downloads
 *
 */
class Memberful_Wp_User_Downloads extends Memberful_Wp_User_Entity { 

  static public function sync( $user_id, $entities ) {
    $syncer = new Memberful_Wp_User_Downloads($user_id);
    return $syncer->set($entities);
  }

  protected function entity_type() {
    return 'product';
  }

  protected function format( $entity ) {
    return array( 'id' => $entity->product->id );
  }
}
