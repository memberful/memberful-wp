<?php
require_once MEMBERFUL_DIR.'/src/user/entity.php';

class Memberful_Wp_User_Feeds extends Memberful_Wp_User_Entity { 

  static public function sync( $user_id, $entities ) {
    $syncer = new Memberful_Wp_User_Feeds($user_id);
    return $syncer->set($entities);
  }

  protected function entity_type() {
    return 'feed';
  }

  protected function format( $entity ) {
    return array(
      'id'              => $entity->feed->id,
      'url'             => $entity->url
    );
  }
}
