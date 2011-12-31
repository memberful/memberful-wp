<?php

add_action('add_meta_boxes', 'memberful_add_metabox');

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

	$metabox = new Memberful_Post_ACL($post->ID, $post->post_type);
	$acl = $metabox->get_products();

	uasort($acl, 'memberful_sort_products_callback');

	memberful_wp_render(
		'metabox',
		array(
			'products' => $acl
		)
	);
}

function memberful_sort_products_callback($a, $b)
{
	if($a['name'] == $b['name'])
		return 0;

	return $a['name'] < $b['name'] ? -1 : 1;
}

class Memberful_Post_ACL
{
	protected $_id;
	protected $_post;

	public function __construct($post_id, $post_type)
	{
		$this->_type = $post_type;
		$this->_id = $post_id;
	}

	public function get_products()
	{
		$acl = $this->get_acl();
		$products = get_option('memberful_products');

		foreach($products as $id => $product)
		{
			$products[$id]['checked'] = isset($acl[$id]);
		}

		return $products;
	}

	public function get_acl()
	{
		$restricted_acl = get_post_meta($this->_id, 'memberful_acl', TRUE);

		return empty($restricted_acl) ? array() : $restricted_acl;
	}

}
