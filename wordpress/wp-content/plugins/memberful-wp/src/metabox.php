<?php

add_action( 'add_meta_boxes', 'memberful_wp_add_metabox' );
add_action( 'save_post', 'memberful_wp_save_postdata' );

function memberful_setup_taxonomy_hooks( $taxonomy, $object_type, $arguments ) {
  if ( $arguments['public'] && $arguments['show_in_menu'] ) {
    add_action( "{$taxonomy}_edit_form", 'memberful_wp_add_term_metabox' );
    add_action( "edited_{$taxonomy}", 'memberful_wp_save_term_metadata' );
  }
}
add_action( 'registered_taxonomy', 'memberful_setup_taxonomy_hooks', 10, 3 );

function memberful_wp_metabox_types() {
  $types = get_post_types();

  unset( $types['attachment'], $types['revision'], $types['nav_menu_item'], $types['forum'], $types['topic'], $types['reply'] );

  return apply_filters( 'memberful_metabox_post_types', $types );
}

function memberful_wp_add_metabox() {
  if ( ! get_option('memberful_site', FALSE) )
    return;

  foreach ( memberful_wp_metabox_types() as $type ) {
    add_meta_box(
      'memberful_acl',
      'Memberful: Restrict Access',
      'memberful_wp_metabox',
      $type
    );
  }
}

function memberful_wp_metabox( $post ) {
  wp_nonce_field( plugin_basename( __FILE__ ), 'memberful_nonce' );

  $view_vars = array();

  $entities = array( Memberful_ACL::DOWNLOAD, Memberful_ACL::SUBSCRIPTION );

  foreach ( $entities as $entity ) {
    $acl_manager = new Memberful_Post_ACL( $entity );

    $view_vars[$entity.'s'] = memberful_wp_metabox_acl_format( $acl_manager->get_acl( $post->ID ), $entity );
  }

  $marketing_content = array_filter(array(
    memberful_post_marketing_content( $post->ID ),
    memberful_wp_marketing_content_explanation()
  ));

  $view_vars['marketing_content'] = reset($marketing_content);
  $view_vars['viewable_by_any_registered_users'] = memberful_wp_get_post_available_to_any_registered_users( $post->ID );
  $view_vars['viewable_by_anybody_subscribed_to_a_plan'] = memberful_wp_get_post_available_to_anybody_subscribed_to_a_plan( $post->ID );

  memberful_wp_render( 'metabox', $view_vars );
}

function memberful_wp_metabox_acl_format( $acl_list, $entity ) {
  $entities = get_option( 'memberful_'.$entity.'s' );

  foreach ( $entities as $id => $_entity ) {
    $entities[$id]['checked'] = isset( $acl_list[$id] );
  }

  uasort( $entities, 'memberful_wp_sort_entities_callback' );

  return $entities;
}

function memberful_wp_sort_entities_callback( $a, $b ) {
  if ( $a['name'] == $b['name'] )
    return 0;

  return $a['name'] < $b['name'] ? - 1 : 1;
}

function memberful_wp_save_postdata( $post_id ) {
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
    return;

  // Verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( ! memberful_wp_valid_nonce( plugin_basename( __FILE__ ) ) )
    return;

  if ( ! in_array( $_POST['post_type'], memberful_wp_metabox_types() ) )
    return;

  $permission = $_POST['post_type'] === 'page' ? 'edit_page' : 'edit_post';

  if ( ! current_user_can( $permission, $post_id ) )
    return;

  // Make sure we're using the actual post id and not a revision id
  if ( $parent_id = wp_is_post_revision( $post_id ) ) {
    $post_id = $parent_id;
  }

  $download_ids = empty($_POST['memberful_product_acl']) ? array() : (array) $_POST['memberful_product_acl'];
  $download_ids = array_map( 'intval', $download_ids );
  $download_ids = array_intersect( $download_ids, array_keys(memberful_downloads()) );
  $acl_manager = new Memberful_Post_ACL( 'product' );
  $acl_manager->set_acl( $post_id, $download_ids );

  $subscription_plan_ids = empty($_POST['memberful_subscription_acl']) ? array() : (array) $_POST['memberful_subscription_acl'];
  $subscription_plan_ids = array_map( 'intval', $subscription_plan_ids );
  $subscription_plan_ids = array_intersect( $subscription_plan_ids, array_keys(memberful_subscription_plans()) );
  $acl_manager = new Memberful_Post_ACL( 'subscription' );
  $acl_manager->set_acl( $post_id, $subscription_plan_ids );

  $viewable_by_any_registered_users = isset($_POST['memberful_viewable_by_any_registered_users']) && $_POST['memberful_viewable_by_any_registered_users'] === '1';
  memberful_wp_set_post_available_to_any_registered_users( $post_id, $viewable_by_any_registered_users );

  $viewable_by_anybody_subscribed_to_a_plan = isset($_POST['memberful_viewable_by_anybody_subscribed_to_a_plan']) && $_POST['memberful_viewable_by_anybody_subscribed_to_a_plan'] === '1';
  memberful_wp_set_post_available_to_anybody_subscribed_to_a_plan( $post_id, $viewable_by_anybody_subscribed_to_a_plan );

  if(!isset($_POST['memberful_marketing_content']))
    return;

  $marketing_content = trim( memberful_wp_kses_post( $_POST['memberful_marketing_content'] ) );

  memberful_wp_update_post_marketing_content( $post_id, $marketing_content );

}

function memberful_wp_add_term_metabox( $term ) {
  if ( ! get_option('memberful_site', FALSE) )
    return;

    wp_nonce_field( plugin_basename( __FILE__ ), 'memberful_nonce' );

    $view_vars = array();
    $entities = array( Memberful_ACL::DOWNLOAD, Memberful_ACL::SUBSCRIPTION );

    foreach ( $entities as $entity ) {
      $acl_manager = new Memberful_Term_ACL( $entity );

      $view_vars[$entity.'s'] = memberful_wp_metabox_acl_format( $acl_manager->get_acl( $term->term_id ), $entity );
    }

    $marketing_content = array_filter(array(
      memberful_term_marketing_content( $term->term_id ),
      memberful_wp_marketing_content_explanation()
    ));

    $view_vars['marketing_content'] = reset($marketing_content);
    $view_vars['viewable_by_any_registered_users'] = memberful_wp_is_term_available_to_any_registered_users( $term->term_id );
    $view_vars['viewable_by_anybody_subscribed_to_a_plan'] = memberful_wp_is_term_available_to_anybody_subscribed_to_a_plan( $term->term_id );

    memberful_wp_render( 'metabox', $view_vars );
}

function memberful_wp_save_term_metadata( $term_id ) {
  if ( ! memberful_wp_valid_nonce( plugin_basename( __FILE__ ) ) )
    return;

  $download_ids = empty($_POST['memberful_product_acl']) ? array() : (array) $_POST['memberful_product_acl'];
  $download_ids = array_map( 'intval', $download_ids );
  $download_ids = array_intersect( $download_ids, array_keys( memberful_downloads() ) );
  $acl_manager = new Memberful_Term_ACL( 'product' );
  $acl_manager->set_acl( $term_id, $download_ids );

  $subscription_plan_ids = empty($_POST['memberful_subscription_acl']) ? array() : (array) $_POST['memberful_subscription_acl'];
  $subscription_plan_ids = array_map( 'intval', $subscription_plan_ids );
  $subscription_plan_ids = array_intersect( $subscription_plan_ids, array_keys( memberful_subscription_plans() ) );
  $acl_manager = new Memberful_Term_ACL( 'subscription' );
  $acl_manager->set_acl( $term_id, $subscription_plan_ids );

  $viewable_by_any_registered_users = isset($_POST['memberful_viewable_by_any_registered_users']) && $_POST['memberful_viewable_by_any_registered_users'] === '1';
  memberful_wp_set_term_available_to_any_registered_users( $term_id, $viewable_by_any_registered_users );

  $viewable_by_anybody_subscribed_to_a_plan = isset($_POST['memberful_viewable_by_anybody_subscribed_to_a_plan']) && $_POST['memberful_viewable_by_anybody_subscribed_to_a_plan'] === '1';
  memberful_wp_set_term_available_to_anybody_subscribed_to_a_plan( $term_id, $viewable_by_anybody_subscribed_to_a_plan );

  if(!isset($_POST['memberful_marketing_content']))
    return;

  $marketing_content = trim( memberful_wp_kses_post( $_POST['memberful_marketing_content'] ) );

  memberful_wp_update_term_marketing_content( $term_id, $marketing_content );
}

/**
 * Base class for managing ACLs on an item (a post or a term).
 *
 * Essentially this class creates two acl maps, one at the item level, and one at
 * the global level.
 *
 * The item level map is simply one of $entity_id => $entity_id (where $entity_id
 * is the id of a download or subscription plan)
 *
 * The global level map is $entity_id => array($item_id => $item_id)
 *
 * When filtering posts on the frontend later on we simply find all the posts that the
 * user is allowed to access (by merging all of the global maps for the entities they have)
 * and then subtract that from the set of acl maps for downloads they don't own.
 *
 * We then use this as a post id exclusion.
 *
 */
class Memberful_ACL {
  // This is left over from deprecating
  // When the plugin hits 2.0 write a converter for product -> download
  const DOWNLOAD = 'product';
  const SUBSCRIPTION = 'subscription';

  protected $_entity;

  public function __construct( $entity ) {
    $this->_entity = $entity;
  }

  /**
   * Set a new ACL for this item, based on the entity type.
   *
   * @param array $entity_ids An array of numerical ids, indicating the required enitities
   */
  public function set_acl( $item_id, array $entity_ids ) {
    $old_acl = $this->get_acl( $item_id );
    $new_acl = array();

    if ( ! empty( $entity_ids ) )
      $new_acl = array_combine( $entity_ids, $entity_ids );

    $acl_map = $this->_load_global_acl();

    $acl_map = $this->_remove_deleted_entities( $item_id, $acl_map, $old_acl, $new_acl );
    $acl_map = $this->_add_new_entities(        $item_id, $acl_map, $old_acl, $new_acl );

    $this->_update_item_acl( $item_id, $new_acl );
    $this->_update_global_acl( $acl_map );
  }

  /**
   * Remove any entities that were unchecked in the metabox from the global ACL map
   *
   */
  protected function _remove_deleted_entities( $item_id, array $map, array $old_acl, array $new_acl ) {
    if ( empty( $map ) || empty( $old_acl ) )
      return $map;

    $deleted = array_diff_key( $old_acl, $new_acl );

    if ( empty( $deleted ) )
      return $map;

    foreach ( $deleted as $entity ) {
      unset( $map[$entity][$item_id] );
    }

    return $map;
  }

  /**
   * Adds any newly checked entities to the global ACL map
   *
   * @param array $map The current
   */
  protected function _add_new_entities( $item_id, array $map, array $old_acl, array $new_acl ) {
    if ( empty( $new_acl ) )
      return $map;

    foreach ( $new_acl as $entity ) {
      if ( ! isset( $map[$entity] ) ) {
        $map[$entity] = array();
      }

      $map[$entity][$item_id] = $item_id;
    }

    return $map;
  }

  protected function meta_field() {
    return 'memberful_'.$this->_entity;
  }
}

class Memberful_Post_ACL extends Memberful_ACL {
  public function get_acl( $post_id ) {
    $restricted_acl = get_post_meta( $post_id, 'memberful_acl', TRUE );

    $entity_acl = empty( $restricted_acl[$this->_entity] ) ? array() : $restricted_acl[$this->_entity];

    return is_array($entity_acl) ? $entity_acl : array();
  }

  protected function _load_global_acl() {
    $acl_map = get_option( 'memberful_acl', array() );

    if ( ! isset( $acl_map[$this->_entity] ) )
      $acl_map[$this->_entity] = array();

    return $acl_map[$this->_entity];
  }

  protected function _update_global_acl( array $entity_acl ) {
    $acl_map = get_option( 'memberful_acl', array() );

    $acl_map[$this->_entity] = $entity_acl;

    update_option( 'memberful_acl', $acl_map );
  }


  protected function _update_item_acl( $item_id, array $new_acl ) {
    $current_acl = get_post_meta( $item_id, 'memberful_acl', TRUE );

    if( !is_array( $current_acl ) )
      $current_acl = array();

    $current_acl[$this->_entity] = $new_acl;
    update_post_meta( $item_id, 'memberful_acl', $current_acl );
  }
}

class Memberful_Term_ACL extends Memberful_ACL {
  public function get_acl( $item_id ) {
    $restricted_acl = get_term_meta( $item_id, 'memberful_acl', TRUE );

    $entity_acl = empty( $restricted_acl[$this->_entity] ) ? array() : $restricted_acl[$this->_entity];

    return is_array($entity_acl) ? $entity_acl : array();
  }

  protected function _update_item_acl( $item_id, array $new_acl ) {
    $current_acl = get_term_meta( $item_id, 'memberful_acl', TRUE );

    if( !is_array( $current_acl ) )
      $current_acl = array();

    $current_acl[$this->_entity] = $new_acl;
    update_term_meta( $item_id, 'memberful_acl', $current_acl );
  }

  protected function _update_global_acl( array $entity_acl ) {
    $acl_map = get_option( 'memberful_term_acl', array() );

    $acl_map[$this->_entity] = $entity_acl;

    update_option( 'memberful_term_acl', $acl_map );
  }

  protected function _load_global_acl() {
    $acl_map = get_option( 'memberful_term_acl', array() );

    if ( ! isset( $acl_map[$this->_entity] ) )
      $acl_map[$this->_entity] = array();

    return $acl_map[$this->_entity];
  }
}
