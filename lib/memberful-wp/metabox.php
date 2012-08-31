<?php

add_action('add_meta_boxes', 'memberful_add_metabox');
add_action('save_post', 'myplugin_save_postdata');


function memberful_metabox_types()
{
	$types = get_post_types();

	unset($types['attachment'], $types['revision'], $types['nav_menu_item']);

	return $types;
}

function memberful_add_metabox()
{
	foreach(memberful_metabox_types() as $type)
	{
		add_meta_box(
			'memberful_acl',
			'Restrict Access',
			'memberful_metabox',
			$type
		);
	}
}

function memberful_metabox($post)
{
	wp_nonce_field( plugin_basename( __FILE__ ), 'memberful_nonce' );

	$acl      = new Memberful_Post_ACL($post->ID, $post->post_type);

	memberful_wp_render(
		'metabox',
		array(
			'products' => memberful_metabox_acl_list($acl->get_acl())
		)
	);
}

function memberful_metabox_acl_list($acl_list)
{
	$products = get_option('memberful_products');

	foreach($products as $id => $product)
	{
		$products[$id]['checked'] = isset($acl_list[$id]);
	}

	uasort($products, 'memberful_sort_products_callback');

	return $products;
}

function memberful_sort_products_callback($a, $b)
{
	if($a['name'] == $b['name'])
		return 0;

	return $a['name'] < $b['name'] ? -1 : 1;
}

function myplugin_save_postdata( $post_id ) {
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
	  return;

	// Verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times

	if( ! isset($_POST['memberful_nonce']) || ! wp_verify_nonce( $_POST['memberful_nonce'], plugin_basename( __FILE__ )))
	  return;

	if( ! in_array($_POST['post_type'], memberful_metabox_types()))
		return;

	$permission = $_POST['post_type'] === 'page' ? 'edit_page' : 'edit_post';

	if( ! current_user_can($permission, $post_id))
		return;

	// Make sure we're using the actual post id and not a revision id
	if($parent_id = wp_is_post_revision($post_id))
	{
		$post_id = $parent_id;
	}

	$acl_list = empty($_POST['memberful_acl']) ? array() : $_POST['memberful_acl'];

	$acl = new Memberful_Post_ACL($post_id);
	$acl->set_acl((array) $acl_list);
}


class Memberful_Post_ACL
{
	protected $_id;

	public function __construct($post_id)
	{
		$this->_id = (int) $post_id;
	}

	public function get_acl()
	{
		$restricted_acl = get_post_meta($this->_id, 'memberful_acl', TRUE);

		return empty($restricted_acl) ? array() : $restricted_acl;
	}

	public function set_acl(array $new_acl)
	{
		$old_acl = $this->get_acl();

		if( ! empty($new_acl))
			$new_acl = array_combine($new_acl, $new_acl);

		$acl_map = get_option('memberful_acl');

		if(empty($acl_map))
			$acl_map = array();

		$acl_map = $this->_remove_deleted_acl($acl_map, $old_acl, $new_acl);
		$acl_map = $this->_add_new_acl($acl_map, $old_acl, $new_acl);

		$this->_update_post_meta($new_acl);
		update_option('memberful_acl', $acl_map);
	}

	protected function _update_post_meta($new_acl)
	{
		update_post_meta($this->_id, 'memberful_acl', $new_acl);
	}

	protected function _remove_deleted_acl(array $map, array $old_acl, array $new_acl)
	{
		if(empty($map) || empty($old_acl))
			return $map;

		$deleted = array_diff_key($old_acl, $new_acl);

		if(empty($deleted))
			return $map;

		foreach($deleted as $product)
		{
			unset($map[$product][$this->_id]);
		}

		return $map;
	}

	protected function _add_new_acl(array $map, array $old_acl, array $new_acl)
	{
		if(empty($new_acl))
			return $map;

		foreach($new_acl as $product)
		{
			if( ! isset($map[$product]))
			{
				$map[$product] = array();
			}

			$map[$product][$this->_id] = $this->_id;
		}

		return $map;
	}
}