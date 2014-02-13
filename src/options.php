<?php
require MEMBERFUL_DIR.'/src/user/map_stats.php';

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
		'memberful_embed_enabled' => FALSE,
		MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT => NULL
	);
}

/**
 * Options that need to be reset when disconnecting from memberful.com
 * @return array
 */
function memberful_wp_connection_options() {
	return array(
		'memberful_client_id',
		'memberful_client_secret',
		'memberful_api_key',
		'memberful_webhook_secret'
	);
}

function memberful_wp_is_connected_to_site() {
	return !! get_option( 'memberful_client_id', FALSE );
}

function memberful_wp_register_options() {
	foreach ( memberful_wp_all_options() as $option => $default ) {
		add_option( $option, $default );
	}
}

function memberful_wp_option_values() {
	$config = array();
	
	foreach ( memberful_wp_all_options() as $option => $default ) {
		$value = get_option( $option );

		$config[$option] = is_string( $value ) ? stripslashes( $value ) : $value;
	}

	return $config;
}


/**
 * Displays the page for registering the WordPress plugin with memberful.com
 */
function memberful_wp_register() {
	$vars = array();

	if ( ! empty( $_POST['activation_code'] ) ) {
		$activation = memberful_wp_activate( $_POST['activation_code'] );

		if ( $activation === TRUE ) {
			memberful_wp_sync_products();
			memberful_wp_sync_subscriptions();
		}
		else {
			Memberful_Wp_Reporting::report( $activation, 'error' );
		}

		return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
	}

	memberful_wp_render( 'setup', $vars );
}

/**
 * Resets the plugin to its default state
 */
function memberful_wp_reset() {
	$defaults = memberful_wp_all_options();

	foreach ( memberful_wp_connection_options() as $option ) {
		update_option( $option, $defaults[$option] );
	}

	wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
}

function _memberful_wp_debug_all_post_meta() {
	global $wpdb;

	$results = $wpdb->get_results(
		"SELECT posts.ID, meta.meta_value FROM {$wpdb->posts} AS posts ".
		"LEFT JOIN {$wpdb->postmeta} AS meta ON (posts.ID = meta.post_id) ".
		"WHERE meta.meta_key = 'memberful_acl';"
	);

	$meta = array();

	foreach($results as $row) {
		$meta[$row->ID] = $row->meta_value;
	}

	return $meta;
}

function memberful_wp_debug() {
	global $wp_version;

	$mapping_stats = new Memberful_User_Map_Stats(Memberful_User_Map::table());
	$counts = count_users();

	$unmapped_users = $mapping_stats->unmapped_users();
	$total_mapping_records = $mapping_stats->count_mapping_records();

	$total_users           = $counts['total_users'];
	$total_unmapped_users  = count($unmapped_users);
	$total_mapped_users    = $total_users - $total_unmapped_users;
    $config                = memberful_wp_option_values();
	$acl_for_all_posts     = _memberful_wp_debug_all_post_meta();
	$plugins               = get_plugins();

	if($total_users != $total_mapped_users) {
		$mapping_records = $mapping_stats->mapping_records();
	}
	else {
		$mapping_records = array();
	}

	memberful_wp_render(
		'debug',
		compact(
			'unmapped_users',
			'total_users',
			'total_unmapped_users',
			'total_mapped_users',
			'total_mapping_records',
			'mapping_records',
			'config',
			'acl_for_all_posts',
			'wp_version',
			'plugins'
		  )
	);
}


/**
 * Displays the memberful options page
 */
function memberful_wp_options() {
	if ( ! empty( $_POST ) ) {
		if ( ! memberful_wp_valid_nonce( 'memberful_setup' ) )
		  return;

		if ( isset( $_POST['manual_sync'] ) ) {
			if ( is_wp_error( $error = memberful_wp_sync_products() ) ) {
				Memberful_Wp_Reporting::report( $error, 'error' );

				return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
			}

			if ( is_wp_error( $error = memberful_wp_sync_subscriptions() ) ) {
				Memberful_Wp_Reporting::report( $error, 'error' );

				return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
			}

			return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
		}

		if ( isset( $_POST['reset_plugin'] ) ) {
			return memberful_wp_reset();
		}
	}

    if ( ! empty( $_GET['debug'] ) ) {
      return memberful_wp_debug();
    }

	if ( ! memberful_wp_is_connected_to_site() ) {
		return memberful_wp_register();
	}

	$products = get_option( 'memberful_products', array() );
	$subs     = get_option( 'memberful_subscriptions', array() );

	memberful_wp_render (
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
	$activator = new Memberful_Activator( $code, memberful_wp_site_name() );

	$activator
		->require_api_key()
		->require_oauth( memberful_wp_oauth_callback_url() )
		->require_webhook( memberful_wp_webhook_url() );

	$credentials = $activator->activate();

	if ( is_wp_error( $credentials ) ) {
		return $credentials;
	}

	update_option( 'memberful_client_id', $credentials->oauth->identifier );
	update_option( 'memberful_client_secret', $credentials->oauth->secret );
	update_option( 'memberful_api_key', $credentials->api_key->key );
	update_option( 'memberful_site', $credentials->site );
	update_option( 'memberful_webhook_secret', $credentials->webhook->secret );

	return TRUE;
}

function memberful_wp_site_name() {
	$blog_name = wp_specialchars_decode( trim( get_bloginfo( 'name', 'Display' ), ENT_QUOTES ) );

	return empty( $blog_name ) ? 'WordPress Blog' : $blog_name;
}
