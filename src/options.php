<?php

function memberful_wp_all_options() { 
	return array(
		'memberful_client_id' => NULL,
		'memberful_client_secret' => NULL,
		'memberful_site' => NULL,
		'memberful_api_key' => NULL,
		'memberful_webhook_secret' => NULL,
		'memberful_products' => array(),
		'memberful_subscriptions' => array(),
		'memberful_acl' => array(),
	);
}

function memberful_wp_register_options() { 
	foreach ( memberful_wp_all_options() as $option => $default ) {
		add_option( $option, $default );
	}
}


/**
 * Displays the page for registering the wordpress plugin with memberful.com
 */
function memberful_wp_register() { 
	$vars = array();

	if ( ! empty( $_POST['activation_code'] ) ) {
		$activation = memberful_wp_activate( $_POST['activation_code'] );

		if ( $activation === TRUE ) {
			memberful_sync_products();

			wp_redirect( admin_url( 'admin.php?page=memberful_options' ) );
		}
		else {
			$vars['error'] = $activation->get_error_message();
		}
	}

	memberful_wp_render( 'setup', $vars );
}

/**
 * Resets the plugin to its default state
 */
function memberful_wp_reset() { 
	foreach ( memberful_wp_all_options() as $option => $default ) {
		update_option( $option, $default );
	}

	wp_redirect( admin_url( 'admin.php?page=memberful_options' ) );
}


/**
 * Displays the memberful options page
 */
function memberful_wp_options() { 
	if ( isset( $_POST['sync_products'] ) ) {
		$result = memberful_sync_products();

		if ( is_wp_error($result) ) {
			var_dump($result);
			die('Could not sync products');
		}

		return wp_redirect( admin_url( 'admin.php?page=memberful_options' ) );
	}

	if ( isset( $_POST['reset_plugin'] ) ) {
		return memberful_wp_reset();
	}

	if ( ! get_option( 'memberful_client_id' ) ) {
	  return memberful_wp_register();
	}

	$products = get_option( 'memberful_products', array() );
	$subs     = get_option( 'memberful_subscriptions', array() );

	memberful_wp_render(
		'options',
		array(
			'products'      => $products,
			'subscriptions' => $subs,
		)
	);
}

/**
 * Attempts to get the necessary details from memberful and set them
 * using the wordpress settings API
 *
 * @param $code string The activation code
 */
function memberful_wp_activate( $code ) { 
	$activator = new Memberful_Activator( $code, html_entity_decode( get_bloginfo( 'name' ) ) );

	$activator
		->require_api_key()
		->require_oauth( memberful_wp_oauth_callback_url() )
		->require_webhook( memberful_wp_webhook_url() );

	$credentials = $activator->activate();

	if ( is_wp_error( $credentials ) ) { 
		var_dump ( $credentials );
		die();
	}

	update_option( 'memberful_client_id', $credentials->oauth->identifier );
	update_option( 'memberful_client_secret', $credentials->oauth->secret );
	update_option( 'memberful_api_key', $credentials->api_key->key );
	update_option( 'memberful_site', $credentials->site );

	return TRUE;
}

function memberful_sync_products() { 
	$url = memberful_admin_products_url( MEMBERFUL_JSON );

	$full_url = add_query_arg( 'auth_token', get_option( 'memberful_api_key' ), $url );

	$response = wp_remote_get( $full_url, array( 'sslverify' => MEMBERFUL_SSL_VERIFY ) );

	if ( is_wp_error( $response ) ) { 
		var_dump( $response, $full_url, $url );
		die();
	}

	if ( $response['response']['code'] != 200 OR ! isset( $response['body'] ) ) { 
		return new WP_Error( 'memberful_product_sync_fail', "Couldn't retrieve list of products from memberful" );
	}

	$raw_products = json_decode( $response['body'] );
	$products     = array();

	foreach ( $raw_products as $product ) { 
		$products[$product->id] = array(
			'id'       => $product->id,
			'name'     => $product->name,
			'slug'     => memberful_wp_product_slug($product),
			'for_sale' => $product->for_sale,
		);
	}

	update_option( 'memberful_products', $products );

	return TRUE;
}

function memberful_wp_product_slug($product) {
	return $product->id.'-'.sanitize_title($product->name);
}
