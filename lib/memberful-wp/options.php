<?php

function memberful_wp_register_options()
{
	add_option('memberful_client_id');
	add_option('memberful_client_secret');
	add_option('memberful_site');
	add_option('memberful_api_key');
	add_option('memberful_products', array());
	add_option('memberful_acl', array());

	add_settings_section(
		'memberful_settings_general',
		'Integration Settings',
		'memberful_wp_options_section_text',
		'memberful_wp_settings'
	);

	// register_setting($option_group, $option_name, $sanitize_callback)
	register_setting(
		'memberful_wp',
		'memberful_client_id',
		'memberful_wp_opt_sanitize'
	);
	register_setting(
		'memberful_wp',
		'memberful_client_secret',
		'memberful_wp_opt_sanitize'
	);
	register_setting(
		'memberful_wp',
		'memberful_site',
		'memberful_wp_opt_sanitize_site'
	);
	register_setting(
		'memberful_wp',
		'memberful_api_key',
		'memberful_wp_opt_sanitize'
	);

	// add_settings_field($id, $title, $callback, $page, $section, $args)
	//
	// $callback renders the field
	// $page is the page ID
	// $section is the section ID, see add_settings_section
	add_settings_field(
		'memberful_client_id',
		'Client ID',
		'memberful_wp_setting_client_id',
		'memberful_wp_settings',
		'memberful_settings_general'
	);
	add_settings_field(
		'memberful_client_secret',
		'Client Secret',
		'memberful_wp_setting_client_secret',
		'memberful_wp_settings',
		'memberful_settings_general'
	);
	add_settings_field(
		'memberful_site',
		'Memberful Site',
		'memberful_wp_setting_site',
		'memberful_wp_settings',
		'memberful_settings_general'
	);
	add_settings_field(
		'memberful_api_key',
		'Memberful API Key',
		'memberful_wp_setting_api_key',
		'memberful_wp_settings',
		'memberful_settings_general'
	);
}

function memberful_wp_register_options_panel()
{
	// $page_title, $menu_title, $capability, $menu_slug, $callback
	add_options_page(
		'Memberful Integration Options',
		'Memberful',
		'administrator',
		'memberful_wp_settings',
		'memberful_wp_options_panel'
	);
}

function memberful_wp_options_section_text()
{
	echo '<p>Here are the settings for your Oauth connection</p>';
}

function memberful_wp_opt_sanitize($option)
{
	return preg_replace('/([^a-zA-Z0-9]+)/', '', $option);
}

function memberful_wp_opt_sanitize_site($site)
{
	return preg_replace('/([^a-z_\-0-9\.\/:]+)/i', '', $site);
}

function memberful_wp_setting_client_id()
{
	echo '<input type="text" name="memberful_client_id" value="'.esc_attr(get_option('memberful_client_id')).'">';
}

function memberful_wp_setting_client_secret()
{
	echo '<input type="text" name="memberful_client_secret" value="'.esc_attr(get_option('memberful_client_secret')).'">';
}

function memberful_wp_setting_site()
{
	echo '<input type="text" name="memberful_site" value="'.esc_attr(get_option('memberful_site')).'" />';
}
function memberful_wp_setting_api_key()
{
	echo '<input type="text" name="memberful_api_key" value="'.esc_attr(get_option('memberful_api_key')).'" />';
}

function memberful_wp_options_panel()
{
  $api_key = get_option('memberful_api_key');

  if(isset($_POST['refresh_products']) && $api_key != NULL)
  {
    memberful_sync_products();
  }

  $options = array(
    'show_products' => ($api_key != NULL),
    'products'      => get_option('memberful_products')
  );
	memberful_wp_render('options', $options);
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
