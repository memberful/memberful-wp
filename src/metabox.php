<?php

add_action( 'add_meta_boxes', 'memberful_add_metabox' );
add_action( 'save_post', 'myplugin_save_postdata' );


function memberful_metabox_types() { 
	$types = get_post_types();

	unset( $types['attachment'], $types['revision'], $types['nav_menu_item'] );

	return $types;
}

function memberful_add_metabox() { 
	if ( ! get_option('memberful_site', FALSE) )
		return;

	foreach ( memberful_metabox_types() as $type ) { 
		add_meta_box(
			'memberful_acl',
			'Memberful: Restrict Access',
			'memberful_metabox',
			$type
		);
	}
}

function memberful_metabox( $post ) { 
	wp_nonce_field( plugin_basename( __FILE__ ), 'memberful_nonce' );

	$acl = array();

	$entities = array( Memberful_Post_ACL::PRODUCT, Memberful_Post_ACL::SUBSCRIPTION );

	foreach ( $entities as $entity ) {
		$acl_manager = new Memberful_Post_ACL( $post->ID, $entity);

		$acl[$entity.'s'] = memberful_metabox_acl_format( $acl_manager->get_acl(), $entity );
	}

	memberful_wp_render( 'metabox', $acl);
}

function memberful_metabox_acl_format( $acl_list, $entity ) { 
	$entities = get_option( 'memberful_'.$entity.'s' );

	foreach ( $entities as $id => $product ) { 
		$entities[$id]['checked'] = isset( $acl_list[$id] );
	}

	uasort( $entities, 'memberful_sort_entities_callback' );

	return $entities;
}

function memberful_sort_entities_callback( $a, $b ) { 
	if ( $a['name'] == $b['name'] )
		return 0;

	return $a['name'] < $b['name'] ? - 1 : 1;
}

function myplugin_save_postdata( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
	  return;

	// Verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times

	if ( ! isset( $_POST['memberful_nonce'] ) || ! wp_verify_nonce( $_POST['memberful_nonce'], plugin_basename( __FILE__ ) ) )
	  return;

	if ( ! in_array( $_POST['post_type'], memberful_metabox_types() ) )
		return;

	$permission = $_POST['post_type'] === 'page' ? 'edit_page' : 'edit_post';

	if ( ! current_user_can( $permission, $post_id ) )
		return;

	// Make sure we're using the actual post id and not a revision id
	if ( $parent_id = wp_is_post_revision( $post_id ) ) { 
		$post_id = $parent_id;
	}

	$entities = array( Memberful_Post_ACL::PRODUCT, Memberful_Post_ACL::SUBSCRIPTION );

	foreach ( $entities as $entity ) {
		$field = 'memberful_'.$entity.'_acl';

		$acl_list = empty($_POST[$field]) ? array() : (array) $_POST[$field];

		$acl_manager = new Memberful_Post_ACL( $post_id, $entity );

		$acl_manager->set_acl( $acl_list );
	}
}


/**
 * Class for managing ACLs on a post
 *
 */
class Memberful_Post_ACL { 
	const PRODUCT = 'product';
	const SUBSCRIPTION = 'subscription';

	protected $_id;
	protected $_entity;

	public function __construct( $post_id, $entity ) { 
		$this->_id     = (int) $post_id;
		$this->_entity = $entity;
	}

	public function get_acl() { 
		$restricted_acl = get_post_meta( $this->_id, 'memberful_acl', TRUE );

		return empty( $restricted_acl[$this->_entity] ) ? array() : $restricted_acl[$this->_entity];
	}

	/**
	 * Set a new ACL for this post, based on the entity type.
	 *
	 * @param array $entity_ids An array of numerical ids, indicating the required enitities
	 */
	public function set_acl( array $entity_ids ) { 
		$old_acl = $this->get_acl();
		$new_acl = array();

		if ( ! empty( $entity_ids ) )
			$new_acl = array_combine( $entity_ids, $entity_ids );

		$acl_map = $this->_load_global_acl();

		$acl_map = $this->_remove_deleted_entities( $acl_map, $old_acl, $new_acl );
		$acl_map = $this->_add_new_entities(        $acl_map, $old_acl, $new_acl );

		$this->_update_post_acl( $new_acl );
		$this->_update_global_acl( $acl_map );
	}

	protected function _load_global_acl() { 
		$acl_map = get_option( 'memberful_acl', array() );

		if ( ! isset( $acl_map[$this->_entity] ) )
			$acl_map[$this->_entity] = array();

		return $acl_map[$this->_entity];
	}

	protected function _update_global_acl($entity_acl) {
		$acl_map = get_option( 'memberful_acl', array() );

		$acl_map[$this->_entity] = $entity_acl;

		update_option( 'memberful_acl', $acl_map );
	}


	protected function _update_post_acl( $new_acl ) { 
		$current_acl = $this->get_acl();
		$current_acl[$this->_entity] = $new_acl;
		update_post_meta( $this->_id, 'memberful_acl', $current_acl );
	}

	/**
	 * Remove any entities that were unchecked in the metabox from the global ACL map
	 *
	 */
	protected function _remove_deleted_entities( array $map, array $old_acl, array $new_acl ) { 
		if ( empty( $map ) || empty( $old_acl ) )
			return $map;

		$deleted = array_diff_key( $old_acl, $new_acl );

		if ( empty( $deleted ) )
			return $map;

		foreach ( $deleted as $product ) { 
			unset( $map[$product][$this->_id] );
		}

		return $map;
	}

	/**
	 * Adds any newly checked entities to the global ACL map
	 *
	 * @param array $map The current 
	 */
	protected function _add_new_entities( array $map, array $old_acl, array $new_acl ) { 
		if ( empty( $new_acl ) )
			return $map;

		foreach ( $new_acl as $product ) { 
			if ( ! isset( $map[$product] ) ) { 
				$map[$product] = array();
			}

			$map[$product][$this->_id] = $this->_id;
		}

		return $map;
	}

	protected function meta_field() { 
		return 'memberful_'.$this->_entity;
	}
}
