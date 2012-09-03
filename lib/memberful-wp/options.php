<?php

function memberful_wp_register_options()
{
	add_option('memberful_client_id');
	add_option('memberful_client_secret');
	add_option('memberful_site');
	add_option('memberful_api_key');
	add_option('memberful_products', array());
	add_option('memberful_acl', array());
}


/**
 * Displays the memberful options page
 *
 */
function memberful_options()
{
	$options = array();

	if ( ! get_option('memberful_client_id') ) {
		memberful_wp_render('setup');
	}
	else {
		memberful_wp_render('options');
	}
}

function memberful_sync_products()
{
	$url = memberful_admin_products_url(MEMBERFUL_JSON);

	$full_url = add_query_arg('auth_token', get_option('memberful_api_key'), $url);

	$response = wp_remote_get($full_url, array('sslverify' => MEMBERFUL_SSL_VERIFY));

	if(is_wp_error($response))
	{
		var_dump($response, $full_url, $url);
		die();
	}

	if($response['response']['code'] != 200 OR ! isset($response['body']))
	{
		return new WP_Error('memberful_product_sync_fail', "Couldn't retrieve list of products from memberful");
	}

	$raw_products = json_decode($response['body']);
	$products = array();

	foreach($raw_products as $product)
	{
		$products[$product->id] = array(
			'id'       => $product->id,
			'name'     => $product->name,
			'for_sale' => $product->for_sale,
		);
	}

	update_option('memberful_products', $products);

	return TRUE;
}
