<?php

require_once MEMBERFUL_DIR . '/src/options.php';
require_once MEMBERFUL_DIR . '/src/metabox.php';

add_action( 'admin_menu',            'memberful_wp_menu' );
add_action( 'admin_init',            'memberful_wp_register_options' );
add_action( 'admin_init',            'memberful_wp_activation_redirect' );
add_action( 'admin_init',            'memberful_wp_plugin_migrate_db' );
add_action( 'admin_enqueue_scripts', 'memberful_wp_admin_enqueue_scripts' );

/**
 * Ensures the database is up to date
 */
function memberful_wp_plugin_migrate_db() {
	global $wpdb;

	if ( get_option( 'memberful_db_version', 0 ) < 1 ) {
		$result = $wpdb->query(
			'CREATE TABLE `'.Memberful_User_Map::table().'`(
			`wp_user_id` INT UNSIGNED NULL DEFAULT NULL UNIQUE KEY,
			`member_id` INT UNSIGNED NOT NULL PRIMARY KEY,
			`refresh_token` VARCHAR( 45 ) NULL DEFAULT NULL,
			`last_sync_at` INT UNSIGNED NOT NULL DEFAULT 0)'
		);

		if ( $result === false ) {
			echo 'Could not create the memberful mapping table\n';
			$wpdb->print_error();
			exit();
		}

		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM `'.$wpdb->users.'` WHERE `Field` LIKE "memberful_%"' );

		if ( ! empty( $columns ) ) {
			$wpdb->query(
				'INSERT INTO `'.Memberful_User_Map::table().'` '.
				'(`member_id`, `wp_user_id`, `refresh_token`, `last_sync_at`) '.
				'SELECT `memberful_member_id`, `ID`, `memberful_refresh_token`, UNIX_TIMESTAMP() '.
				'FROM `'.$wpdb->users.'` '.
				'WHERE `memberful_member_id` IS NOT NULL'
			);

			$wpdb->query(
				'ALTER TABLE `'.$wpdb->users.'`
				DROP COLUMN `memberful_member_id`,
				DROP COLUMN `memberful_refresh_token`'
			);
		}

		update_option( 'memberful_db_version', 1 );
	}
}

/**
 * Redirects to the Memberful plugin options page after activation
 *
 */
function memberful_wp_activation_redirect() {
	if ( get_option( 'memberful_wp_activation_redirect', false ) ) {
		delete_option( 'memberful_wp_activation_redirect' );

		if ( !isset( $_GET['activate-multi'] ) ) {
			wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
		}
	}
}

/**
 * Add an options page
 */
function memberful_wp_menu() {
	add_options_page( 'Memberful', 'Memberful', 'manage_options', 'memberful_options', 'memberful_wp_options' );
}


/**
 * Enqueues the Memberful admin screen CSS, only on the settings page.
 * Hooked on admin_enqueue_scripts.
 */
function memberful_wp_admin_enqueue_scripts() {
	$screen = get_current_screen();

	if ( strpos( 'memberful', $screen->id ) !== null ) {
		wp_enqueue_style(
			'memberful-admin',
			plugins_url( 'stylesheets/admin.css' , dirname(__FILE__) )
		);
		wp_enqueue_script(
			'memberful-admin',
			plugins_url( 'js/admin.js', dirname( __FILE__ ) ),
			array('jquery'),
			MEMBERFUL_VERSION
		);
	}
}

/**
 * Displays the page for registering the WordPress plugin with memberful.com
 */
function memberful_wp_register() {
	$vars = array();

	if ( ! empty( $_POST['activation_code'] ) ) {
		$activation = memberful_wp_activate( $_POST['activation_code'] );

		if ( $activation === TRUE ) {
			update_option( 'memberful_embed_enabled', TRUE );
			memberful_wp_sync_downloads();
			memberful_wp_sync_subscription_plans();
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
	$error_log             = memberful_wp_error_log();

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
			'plugins',
			'error_log'
		  )
	);
}


/**
 * Displays the memberful options page
 */
function memberful_wp_options() {
	if ( ! function_exists( 'curl_version' ) || isset( $_GET['curl_message'] ) )
		return memberful_wp_render( 'curl_required' );

	if ( ! empty( $_POST ) ) {
		if ( ! memberful_wp_valid_nonce( 'memberful_options' ) )
		  return;

		if ( isset( $_POST['manual_sync'] ) ) {
			if ( is_wp_error( $error = memberful_wp_sync_downloads() ) ) {
				Memberful_Wp_Reporting::report( $error, 'error' );

				return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
			}

			if ( is_wp_error( $error = memberful_wp_sync_subscription_plans() ) ) {
				Memberful_Wp_Reporting::report( $error, 'error' );

				return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
			}

			return wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
		}

		if ( isset( $_POST['reset_plugin'] ) ) {
			return memberful_wp_reset();
		}
	}

	if ( ! memberful_wp_is_connected_to_site() ) {
		return memberful_wp_register();
	}

	if ( ! empty( $_GET['subpage'] ) ) {
		switch ( $_GET['subpage'] ) {
			case 'bulk_protect': 
				return memberful_wp_bulk_protect();
			case 'debug':
				return memberful_wp_debug();
			case 'advanced_settings':
				return memberful_wp_advanced_settings();
		}
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

	// Ideally we'd modify the activation payload to send this info, but it's easier to do this "short-term".
	memberful_wp_send_site_options_to_memberful();

	return TRUE;
}

function memberful_wp_advanced_settings() {
	$allowed_roles         = memberful_wp_roles_that_can_be_mapped_to();
	$current_active_role   = memberful_wp_role_for_active_customer();
	$current_inactive_role = memberful_wp_role_for_inactive_customer();

	if ( ! empty( $_POST ) ) {
		$new_active_role   = isset( $_POST['role_mappings']['active_customer'] ) ? $_POST['role_mappings']['active_customer'] : '';
		$new_inactive_role = isset( $_POST['role_mappings']['inactive_customer'] ) ? $_POST['role_mappings']['inactive_customer'] : '';

		if ( array_key_exists( $new_active_role, $allowed_roles ) && array_key_exists( $new_inactive_role, $allowed_roles ) ) {
			update_option( 'memberful_role_active_customer', $new_active_role );
			update_option( 'memberful_role_inactive_customer', $new_inactive_role );

			memberful_wp_update_customer_roles( $current_active_role, $new_active_role, $current_inactive_role, $new_inactive_role );

			Memberful_Wp_Reporting::report( __('Settings updated') );
		} else {
			Memberful_Wp_Reporting::report( __('The roles you chose aren\'t in the list of allowed roles'), 'error' );
		}

		wp_redirect( memberful_wp_plugin_advanced_settings_url() );
	}

	$vars = array(
		'available_state_mappings' => array(
			'active_customer'   => array(
				'name' => 'Any active subscription plans',
				'current_role' => $current_active_role,
			),
			'inactive_customer' => array(
				'name' => 'No active subscription plans',
				'current_role' => $current_inactive_role,
			),
		),
		'available_roles' => $allowed_roles,
	);
	memberful_wp_render( 'advanced_settings', $vars );
}

function memberful_wp_bulk_protect() {
	if ( ! empty( $_POST ) ) {
		$categories_to_protect = empty( $_POST['memberful_protect_categories'] ) ? array() : (array) $_POST['memberful_protect_categories'];
		$acl_for_products      = empty( $_POST['memberful_product_acl'] ) ? array() : (array) $_POST['memberful_product_acl'];
		$acl_for_subscriptions = empty( $_POST['memberful_subscription_acl'] ) ? array() : (array) $_POST['memberful_subscription_acl'];
		$marketing_content     = empty( $_POST['memberful_marketing_content'] ) ? '' : $_POST['memberful_marketing_content'];
		$things_to_protect     = empty($_POST['target_for_restriction']) ? '' : $_POST['target_for_restriction'];

		$product_acl_manager   = new Memberful_Post_ACL( 'product' );
		$subscription_acl_manager = new Memberful_Post_ACL( 'subscription' );
		

		$query_params = array('nopaging' => true, 'fields' => 'ids');

		switch ( $things_to_protect ) {
			case 'all_pages_and_posts':
				$query_params['post_type'] = array('post', 'page');
				break;
			case 'all_pages':
				$query_params['post_type'] = 'page';
				break;
			case 'all_posts':
				$query_params['post_type'] = 'post';
				break;
			case 'all_posts_from_category':
				$query_params['category__in'] = $categories_to_protect;
				break;
	
		}

		$query = new WP_Query($query_params);

		foreach($query->posts as $id) {
			$product_acl_manager->set_acl($id, $acl_for_products);
			$subscription_acl_manager->set_acl($id, $acl_for_subscriptions);
			memberful_wp_update_post_marketing_content($id, $marketing_content);
		}

		wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
	}

	memberful_wp_render(
		'bulk_protect',
		array(
			'products' => memberful_wp_metabox_acl_format( array(), 'product' ),
			'subscriptions' => memberful_wp_metabox_acl_format( array(), 'subscription' ),
			'marketing_content' => '',
			'form_target'       => memberful_wp_plugin_bulk_protect_url(TRUE),
		)
	);
}
